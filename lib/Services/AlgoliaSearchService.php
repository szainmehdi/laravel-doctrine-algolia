<?php

namespace Zain\LaravelDoctrine\Algolia\Services;

use Algolia\AlgoliaSearch\RequestOptions\RequestOptions;
use Algolia\AlgoliaSearch\Response\AbstractResponse as Response;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Zain\LaravelDoctrine\Algolia\AtomicSearchService;
use Zain\LaravelDoctrine\Algolia\Engine;
use Zain\LaravelDoctrine\Algolia\Entity\Aggregator;
use Zain\LaravelDoctrine\Algolia\Exception\ConfigurationException;
use Zain\LaravelDoctrine\Algolia\Responses\SearchServiceResponse;
use Zain\LaravelDoctrine\Algolia\SearchableEntity;
use Zain\LaravelDoctrine\Algolia\SearchService;
use Zain\LaravelDoctrine\Algolia\Serialization\SerializerFactory;

final class AlgoliaSearchService implements SearchService, AtomicSearchService
{
    private Engine $engine;

    /** @var array<string, array|int|string> */
    private array $configuration;

    private PropertyAccessor $propertyAccessor;

    /** @var array<int, string> */
    private array $searchableEntities;

    /** @var array<int, string> */
    private array $aggregators;

    /** @var array<string, array> */
    private array $entitiesAggregators;

    /** @var array<string, string> */
    private array $classToIndexMapping;

    /** @var array<string, string> */
    private array $normalizerMapping;

    /** @var array<string, boolean> */
    private array $classToSerializerGroupMapping;

    /** @var array<string, string|null> */
    private array $indexIfMapping;

    private SerializerFactory $serializerFactory;

    /**
     * @param array<string, array|int|string> $configuration
     */
    public function __construct(SerializerFactory $serializerFactory, Engine $engine, array $configuration)
    {
        $this->engine = $engine;
        $this->configuration = $configuration;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->serializerFactory = $serializerFactory;

        $this->setSearchableEntities();
        $this->setAggregatorsAndEntitiesAggregators();
        $this->setClassToIndexMapping();
        $this->setClassToSerializerGroupMapping();
        $this->setIndexIfMapping();
    }

    private function setSearchableEntities(): void
    {
        $searchable = [];

        foreach ($this->configuration['indices'] as $name => $index) {
            $searchable[] = $index['class'];
        }

        $this->searchableEntities = array_unique($searchable);
    }

    private function setAggregatorsAndEntitiesAggregators(): void
    {
        $this->entitiesAggregators = [];
        $this->aggregators = [];

        foreach ($this->configuration['indices'] as $name => $index) {
            if (is_subclass_of($index['class'], Aggregator::class)) {
                foreach ($index['class']::getEntities() as $entityClass) {
                    if (!isset($this->entitiesAggregators[$entityClass])) {
                        $this->entitiesAggregators[$entityClass] = [];
                    }

                    $this->entitiesAggregators[$entityClass][] = $index['class'];
                    $this->aggregators[] = $index['class'];
                }
            }
        }

        $this->aggregators = array_unique($this->aggregators);
    }

    private function setClassToIndexMapping(): void
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexName => $indexDetails) {
            $mapping[$indexDetails['class']] = $indexName;
        }

        $this->classToIndexMapping = $mapping;
    }

    private function setClassToSerializerGroupMapping(): void
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexDetails) {
            $mapping[$indexDetails['class']] = $indexDetails['enable_serializer_groups'];
        }

        $this->classToSerializerGroupMapping = $mapping;
    }

    private function setIndexIfMapping(): void
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexDetails) {
            $mapping[$indexDetails['class']] = $indexDetails['index_if'];
        }

        $this->indexIfMapping = $mapping;
    }

    /**
     * @return array<int, string>
     */
    public function getSearchables(): array
    {
        return $this->searchableEntities;
    }

    /**
     * @return array<string, array|int|string>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @param object|array<int, object> $searchables
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function index(ObjectManager $objectManager, $searchables, $requestOptions = []): Response
    {
        $searchables = is_array($searchables) ? $searchables : [$searchables];
        $searchables = array_merge($searchables, $this->getAggregatorsFromEntities($objectManager, $searchables));

        $searchablesToBeIndexed = array_filter(
            $searchables,
            function ($entity) {
                return $this->isSearchable($entity);
            }
        );

        $searchablesToBeRemoved = [];
        foreach ($searchablesToBeIndexed as $key => $entity) {
            if (!$this->shouldBeIndexed($entity)) {
                unset($searchablesToBeIndexed[$key]);
                $searchablesToBeRemoved[] = $entity;
            }
        }

        if (count($searchablesToBeRemoved) > 0) {
            $this->remove($objectManager, $searchablesToBeRemoved);
        }

        return $this->makeSearchServiceResponseFrom(
            $objectManager,
            $searchablesToBeIndexed,
            function ($chunk) use ($requestOptions) {
                return $this->engine->index($chunk, $requestOptions);
            }
        );
    }

    /**
     * Returns the aggregators instances of the provided entities.
     *
     * @param array<int, object> $entities
     *
     * @return array<int, object>
     */
    private function getAggregatorsFromEntities(ObjectManager $objectManager, array $entities): array
    {
        $aggregators = [];

        foreach ($entities as $entity) {
            $entityClassName = ClassUtils::getClass($entity);
            if (array_key_exists($entityClassName, $this->entitiesAggregators)) {
                foreach ($this->entitiesAggregators[$entityClassName] as $aggregator) {
                    $aggregators[] = new $aggregator(
                        $entity,
                        $objectManager->getClassMetadata($entityClassName)->getIdentifierValues($entity)
                    );
                }
            }
        }

        return $aggregators;
    }

    public function isSearchable($className): bool
    {
        if (is_object($className)) {
            $className = ClassUtils::getClass($className);
        }

        return in_array($className, $this->searchableEntities, true);
    }

    public function shouldBeIndexed(object $entity): bool
    {
        $className = ClassUtils::getClass($entity);
        $propertyPath = $this->indexIfMapping[$className];

        if ($propertyPath !== null) {
            if ($this->propertyAccessor->isReadable($entity, $propertyPath)) {
                return (bool)$this->propertyAccessor->getValue($entity, $propertyPath);
            }

            return false;
        }

        return true;
    }

    /**
     * @param object|array<int, object> $searchables
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     */
    public function remove(ObjectManager $objectManager, $searchables, $requestOptions = []): Response
    {
        $searchables = is_array($searchables) ? $searchables : [$searchables];
        $searchables = array_merge($searchables, $this->getAggregatorsFromEntities($objectManager, $searchables));

        $searchables = array_filter(
            $searchables,
            function ($entity) {
                return $this->isSearchable($entity);
            }
        );

        return $this->makeSearchServiceResponseFrom(
            $objectManager,
            $searchables,
            function ($chunk) use ($requestOptions) {
                return $this->engine->remove($chunk, $requestOptions);
            }
        );
    }

    /**
     * For each chunk performs the provided operation.
     *
     * @param array<int, object> $entities
     * @param callable $operation
     */
    private function makeSearchServiceResponseFrom(
        ObjectManager $objectManager,
        array $entities,
        callable $operation
    ): Response {
        $batch = [];
        foreach (array_chunk($entities, $this->configuration['batchSize']) as $chunk) {
            $searchableEntitiesChunk = [];
            foreach ($chunk as $entity) {
                $entityClassName = ClassUtils::getClass($entity);

                $searchableEntitiesChunk[] = new SearchableEntity(
                    $this->searchableAs($entityClassName),
                    $entity,
                    $objectManager->getClassMetadata($entityClassName),
                    $this->serializerFactory,
                    ['useSerializerGroup' => $this->canUseSerializerGroup($entityClassName)]
                );
            }

            $batch[] = $operation($searchableEntitiesChunk);
        }

        return new SearchServiceResponse($batch);
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function searchableAs(string $className): string
    {
        return $this->configuration['prefix'] . $this->classToIndexMapping[$className];
    }

    private function canUseSerializerGroup(string $className): bool
    {
        return $this->classToSerializerGroupMapping[$className];
    }

    /**
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     */
    public function clear(string $className, $requestOptions = []): Response
    {
        $this->assertIsSearchable($className);

        return $this->engine->clear($this->searchableAs($className), $requestOptions);
    }

    private function assertIsSearchable(string $className): void
    {
        if (!$this->isSearchable($className)) {
            throw new ConfigurationException('Class ' . $className . ' is not searchable.');
        }
    }

    /**
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     */
    public function delete(string $className, $requestOptions = []): Response
    {
        $this->assertIsSearchable($className);

        return $this->engine->delete($this->searchableAs($className), $requestOptions);
    }

    /**
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     */
    public function search(
        ObjectManager $objectManager,
        string $className,
        string $query = '',
        $requestOptions = []
    ): array {
        $this->assertIsSearchable($className);

        $ids = $this->engine->searchIds($query, $this->searchableAs($className), $requestOptions);

        $results = [];

        foreach ($ids as $objectID) {
            if (in_array($className, $this->aggregators, true)) {
                $entityClass = $className::getEntityClassFromObjectID($objectID);
                $id = $className::getEntityIdFromObjectID($objectID);
            } else {
                $id = $objectID;
                $entityClass = $className;
            }

            $repo = $objectManager->getRepository($entityClass);
            $entity = $repo->findOneBy(['id' => $id]);

            if ($entity !== null) {
                $results[] = $entity;
            }
        }

        return $results;
    }

    /**
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return array<string, int|string|bool|array>
     */
    public function rawSearch(string $className, string $query = '', $requestOptions = []): array
    {
        $this->assertIsSearchable($className);

        return $this->engine->search($query, $this->searchableAs($className), $requestOptions);
    }

    /**
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     */
    public function count(string $className, string $query = '', $requestOptions = []): int
    {
        $this->assertIsSearchable($className);

        return $this->engine->count($query, $this->searchableAs($className), $requestOptions);
    }
}
