<?php

declare(strict_types=1);

namespace Zain\LaravelDoctrine\Algolia\Serialization;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer;

class SerializerFactory
{
    private array $configuration;

    /** @var array<string,NormalizerInterface|null> */
    private array $mapping;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->mapEntityNormalizers();
    }

    /**
     * @param string $class
     *
     * @return NormalizerInterface|Serializer
     */
    public function create(?string $class = null): Serializer
    {
        $normalizers = $this->buildNormalizers();

        if ($class !== null && $normalizer = $this->entityNormalizer($class)) {
             $normalizers = [$normalizer, ...$normalizers];
        }

        return new Serializer($normalizers);
    }

    /**
     * @return array<\Symfony\Component\Serializer\Normalizer\NormalizerInterface>
     */
    private function buildNormalizers(): array
    {
        $normalizers = [];
        $classes = array_merge($this->configuredNormalizers(), $this->defaultNormalizers());
        foreach ($classes as $class) {
            $normalizers[] = app()->make($class);
        }
        return $normalizers;
    }

    /**
     * @return array<string>
     */
    private function configuredNormalizers(): array
    {
        return $this->configuration['normalizers'];
    }

    /**
     * @return array<string>
     */
    private function defaultNormalizers(): array
    {
        return [
            Normalizer\CustomNormalizer::class,
            Normalizer\ProblemNormalizer::class,
            Normalizer\JsonSerializableNormalizer::class,
            Normalizer\DateTimeNormalizer::class,
            Normalizer\ConstraintViolationListNormalizer::class,
            Normalizer\DateTimeZoneNormalizer::class,
            Normalizer\DateIntervalNormalizer::class,
            Normalizer\DataUriNormalizer::class,
            Normalizer\ArrayDenormalizer::class,
            Normalizer\ObjectNormalizer::class,
        ];
    }

    private function mapEntityNormalizers(): void
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexName => $indexDetails) {
            $normalizer = ($indexDetails['normalizer']) ? app()->make($indexDetails['normalizer']) : null;
            $mapping[$indexDetails['class']] = $normalizer;
        }
        $this->mapping = $mapping;
    }

    private function entityNormalizer(string $class): ?NormalizerInterface
    {
        return $this->mapping[$class] ?? null;
    }
}
