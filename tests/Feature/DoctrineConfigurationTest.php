<?php

declare(strict_types=1);

namespace Zain\LaravelDoctrine\Algolia\Feature;

use Doctrine\ORM\Mapping\ClassMetadata;
use Zain\LaravelDoctrine\Algolia\Entity\Aggregator;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\ContentAggregator;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Post;

class DoctrineConfigurationTest extends FeatureTest
{
    public function testEntityManagerLoadsAggregator(): void
    {
        $this->assertTrue(true);
        $this->assertInstanceOf(ClassMetadata::class, app('em')->getClassMetadata(Aggregator::class));
        $this->assertInstanceOf(ClassMetadata::class, app('em')->getClassMetadata(Post::class));
        $this->assertInstanceOf(ClassMetadata::class, app('em')->getClassMetadata(ContentAggregator::class));
    }
}
