<?php

namespace Zain\LaravelDoctrine\Algolia;

use Algolia\AlgoliaSearch\RequestOptions\RequestOptions;
use Algolia\AlgoliaSearch\Response\AbstractResponse as Response;
use Doctrine\Common\Persistence\ObjectManager;

interface SearchService
{
    /**
     * @param string $className
     *
     * @return bool
     */
    public function isSearchable($className): bool;

    /**
     * @return array<int, string>
     */
    public function getSearchables(): array;

    /**
     * @return array<string, array|int|string>
     */
    public function getConfiguration(): array;

    /**
     * Get the index name for the given `$className`.
     *
     * @param string $className
     *
     * @return string
     */
    public function searchableAs(string $className): string;

    /**
     * @param object|array<int, object> $searchables
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function index(ObjectManager $objectManager, $searchables, $requestOptions = []): Response;

    /**
     * @param object|array<int, object> $searchables
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function remove(ObjectManager $objectManager, $searchables, $requestOptions = []): Response;

    /**
     * @param string $className
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function clear(string $className, $requestOptions = []): Response;

    /**
     * @param string $className
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function delete(string $className, $requestOptions = []): Response;

    /**
     * @param string $className
     * @param string $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return array<int, object>
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function search(ObjectManager $objectManager, string $className, string $query = '', $requestOptions = []);

    /**
     * @param string $className
     * @param string $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return array<string, int|string|bool|array>
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function rawSearch(string $className, string $query = '', $requestOptions = []): array;

    /**
     * @param string $className
     * @param string $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return int
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function count(string $className, string $query = '', $requestOptions = []): int;
}
