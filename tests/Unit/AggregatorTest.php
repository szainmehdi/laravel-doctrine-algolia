<?php

namespace Zain\LaravelDoctrine\Algolia\TestCase;

use Zain\LaravelDoctrine\Algolia\Exception\EntityNotFoundInObjectID;
use Zain\LaravelDoctrine\Algolia\Exception\InvalidEntityForAggregator;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\ContentAggregator;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\EmptyAggregator;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Post;
use Zain\LaravelDoctrine\Algolia\TestCase;

class AggregatorTest extends TestCase
{
    public function testGetEntities()
    {
        $entites = EmptyAggregator::getEntities();

        $this->assertEquals([], $entites);
    }

    public function testGetEntityClassFromObjectID()
    {
        $this->expectException(EntityNotFoundInObjectID::class);
        EmptyAggregator::getEntityClassFromObjectID('test');
    }

    public function testContructor()
    {
        $this->expectException(InvalidEntityForAggregator::class);
        $post = new Post();
        $compositeAggregator = new ContentAggregator($post, ['objectId', 'url']);
    }
}
