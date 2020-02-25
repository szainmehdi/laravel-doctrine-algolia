<?php

namespace Zain\LaravelDoctrine\Algolia;

use Doctrine\ORM\Mapping\ClassMetadata;
use Zain\LaravelDoctrine\Algolia\Exception\ConfigurationException as Exception;
use Zain\LaravelDoctrine\Algolia\Serialization\SerializerFactory;

/**
 * @internal
 */
final class SearchableEntity
{
    private string $indexName;
    private object $entity;
    private ClassMetadata $entityMetadata;
    private SerializerFactory $serializerFactory;

    /** @var int|string */
    private $id;

    /**
     * @param array<string, int|string|array|bool> $extra
     */
    public function __construct(
        string $indexName,
        object $entity,
        ClassMetadata $entityMetadata,
        SerializerFactory $serializerFactory
    ) {
        $this->indexName = $indexName;
        $this->entity = $entity;
        $this->entityMetadata = $entityMetadata;
        $this->serializerFactory = $serializerFactory;

        $this->setId();
    }

    private function setId(): void
    {
        $ids = $this->entityMetadata->getIdentifierValues($this->entity);

        if (count($ids) === 0) {
            throw new Exception('Entity has no primary key');
        }

        if (count($ids) == 1) {
            $this->id = reset($ids);
        } else {
            $objectID = '';
            foreach ($ids as $key => $value) {
                $objectID .= $key . '-' . $value . '__';
            }

            $this->id = rtrim($objectID, '_');
        }
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    /**
     * @return array<string, int|string|array>|null
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function getSearchableArray(): ?array
    {
        $context = [
            'fieldsMapping' => $this->entityMetadata->fieldMappings,
        ];

        $serializer = $this->serializerFactory->create($this->entityMetadata->getName());
        return $serializer->normalize($this->entity, Searchable::NORMALIZATION_FORMAT, $context);
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
}
