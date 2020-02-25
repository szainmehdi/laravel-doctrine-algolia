<?php

namespace Zain\LaravelDoctrine\Algolia\TestCase;

use Algolia\AlgoliaSearch\SearchClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Zain\LaravelDoctrine\Algolia\Feature\FeatureTest;
use Zain\LaravelDoctrine\Algolia\SearchService;
use Zain\LaravelDoctrine\Algolia\Services\AlgoliaSearchService;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Comment;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\ContentAggregator;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Post;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Tag;

class DoctrineTest extends FeatureTest
{
    protected AlgoliaSearchService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->refreshDb();

        /** @var AlgoliaSearchService $service */
        $service = app(SearchService::class);
        $this->service = $service;

        /** @var SearchClient $client */
        $client = app(SearchClient::class);
        $indexName = 'posts';
        $index = $client->initIndex($this->getPrefix() . $indexName);
        $index->setSettings($this->getDefaultConfig())->wait();
    }

    public function testDoctrineEventManagement()
    {
        $em = $this->getEntityManager();
        for ($i = 0; $i < 5; $i++) {
            $post = $this->createPost();
            $em->persist($post);
        }
        $em->flush();

        $iteration = 0;
        $expectedCount = 5;
        do {
            $count = $this->service->count(Post::class);
            sleep(1);
            $iteration++;
        } while ($count !== $expectedCount && $iteration < 10);

        $this->assertEquals($expectedCount, $count);

        $raw = $this->service->rawSearch(Post::class);
        $this->assertArrayHasKey('query', $raw);
        $this->assertArrayHasKey('nbHits', $raw);
        $this->assertArrayHasKey('page', $raw);
        $this->assertTrue(is_array($raw['hits']));

        $posts = $this->service->search($em, Post::class);
        $this->assertTrue(is_array($posts));
        foreach ($posts as $p) {
            $this->assertInstanceOf(Post::class, $p);
        }

        $posts = $this->service->search($em, ContentAggregator::class);
        foreach ($posts as $p) {
            $this->assertInstanceOf(Post::class, $p);
        }

        $postToUpdate = $posts[4];
        $postToUpdate->setTitle('New Title');
        $em->flush();
        $posts = $this->service->search($em, ContentAggregator::class);
        $this->assertEquals($posts[4]->getTitle(), 'New Title');

        $em->remove($posts[0]);

        $iteration = 0;
        $expectedCount = 4;
        do {
            $count = $this->service->count(Post::class);
            sleep(1);
            $iteration++;
        } while ($count !== $expectedCount && $iteration < 10);

        $this->assertEquals($count, $expectedCount);
        $this->cleanUp();
    }

    public function cleanUp()
    {
        $this->service->delete(Post::class)->wait();
        $this->service->delete(Comment::class)->wait();
        $this->service->delete(Tag::class)->wait();
    }

    public function testIndexIfFeature()
    {
        $tags = [
            new Tag(['id' => 1, 'name' => 'Tag #1']),
            new Tag(['id' => 2, 'name' => 'Tag #2']),
            new Tag(['id' => rand(10, 42), 'name' => 'Tag #3', 'public' => false]),
        ];
        $em = $this->getEntityManager();

        $this->service->index($em, $tags)->wait();

        $this->assertEquals(2, $this->service->count(Tag::class));

        $this->service->index($em, $tags[2]->setPublic(true))->wait();

        $this->assertEquals(3, $this->service->count(Tag::class));
        $this->cleanUp();
    }
}
