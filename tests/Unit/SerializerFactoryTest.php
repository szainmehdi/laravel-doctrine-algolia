<?php

declare(strict_types=1);

namespace Zain\LaravelDoctrine\Algolia\Unit;

use ReflectionClass;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Post;
use Zain\LaravelDoctrine\Algolia\Serialization\SerializerFactory;
use Zain\LaravelDoctrine\Algolia\TestCase;

class SerializerFactoryTest extends TestCase
{
    public function testCreatesDefaultNormalizers(): void
    {
        $serializer = app(SerializerFactory::class)->create();

        $reflection = new ReflectionClass($serializer);
        $normalizersProperty = $reflection->getProperty('normalizers');
        $normalizersProperty->setAccessible(true);
        $normalizers = $normalizersProperty->getValue($serializer);

        $classes = array_map(fn ($value) => get_class($value), $normalizers);

        $this->assertStringContainsString('ObjectNormalizer', end($classes));
        $this->assertStringContainsString('CustomNormalizer', reset($classes));
        $this->assertGreaterThan(2, count($classes));
    }

    /**
     * @environment-setup configureCommentNormalizer
     */
    public function testPrependsConfiguredNormalizers(): void
    {
        $serializer = app(SerializerFactory::class)->create();

        $reflection = new ReflectionClass($serializer);
        $normalizersProperty = $reflection->getProperty('normalizers');
        $normalizersProperty->setAccessible(true);
        $normalizers = $normalizersProperty->getValue($serializer);

        $classes = array_map(fn ($value) => get_class($value), $normalizers);

        $this->assertStringContainsString('ObjectNormalizer', end($classes));
        $this->assertStringContainsString('CommentNormalizer', reset($classes));
        $this->assertGreaterThan(2, count($classes));
    }

    /**
     * @environment-setup configurePostEntityIndex
     */
    public function testPrependsIndexNormalizers(): void
    {
        $serializer = app(SerializerFactory::class)->create(Post::class);

        $reflection = new ReflectionClass($serializer);
        $normalizersProperty = $reflection->getProperty('normalizers');
        $normalizersProperty->setAccessible(true);
        $normalizers = $normalizersProperty->getValue($serializer);

        $classes = array_map(fn ($value) => get_class($value), $normalizers);

        $this->assertStringContainsString('ObjectNormalizer', end($classes));
        $this->assertStringContainsString('PostNormalizer', reset($classes));
        $this->assertGreaterThan(2, count($classes));
    }
}
