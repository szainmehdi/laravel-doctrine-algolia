<?php

namespace Zain\LaravelDoctrine\Algolia\Services;

use Algolia\AlgoliaSearch\RequestOptions\RequestOptions;
use Algolia\AlgoliaSearch\Response\AbstractResponse as Response;
use Algolia\AlgoliaSearch\Response\NullResponse;
use Doctrine\Common\Persistence\ObjectManager;
use Zain\LaravelDoctrine\Algolia\AtomicSearchService;
use Zain\LaravelDoctrine\Algolia\SearchService;

/**
 * This class aims to be used in dev or testing environments. It may
 * be subject to breaking changes.
 */
class NullSearchService implements SearchService, AtomicSearchService
{
    /**
     * @param string $className
     *
     * @return bool
     */
    public function isSearchable($className): bool
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    public function getSearchables(): array
    {
        return [];
    }

    /**
     * @return array<string, array|int|string>
     */
    public function getConfiguration(): array
    {
        return [
            'batchSize' => 200,
        ];
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function searchableAs(string $className): string
    {
        return $className;
    }

    /**
     * @param object|array<int, object> $searchables
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function index(ObjectManager $objectManager, $searchables, $requestOptions = []): Response
    {
        return new NullResponse();
    }

    /**
     * @param object|array<int, object> $searchables
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function remove(ObjectManager $objectManager, $searchables, $requestOptions = []): Response
    {
        return new NullResponse();
    }

    /**
     * @param string $className
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function clear(string $className, $requestOptions = []): Response
    {
        return new NullResponse();
    }

    /**
     * @param string $className
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function delete(string $className, $requestOptions = []): Response
    {
        return new NullResponse();
    }

    /**
     * @param string $className
     * @param string $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return array<int, object>
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function search(ObjectManager $objectManager, $className, $query = '', $requestOptions = [])
    {
        return [
            new \stdClass(),
        ];
    }

    /**
     * @param string $className
     * @param string $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return array<string, int|string|bool|array>
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function rawSearch($className, $query = '', $requestOptions = []): array
    {
        return [
            'hits' => [],
            'nbHits' => 0,
            'page' => 0,
            'nbPages' => 1,
            'hitsPerPage' => 0,
            'exhaustiveNbHits' => true,
            'query' => '',
            'params' => '',
            'processingTimeMS' => 1,
        ];
    }

    /**
     * @param string $className
     * @param string $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return int
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function count($className, $query = '', $requestOptions = []): int
    {
        return 0;
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    public function shouldBeIndexed($entity)
    {
        return false;
    }
}
