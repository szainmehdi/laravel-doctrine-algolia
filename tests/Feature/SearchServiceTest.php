<?php

namespace Zain\LaravelDoctrine\Algolia\TestCase;

use Doctrine\ORM\EntityManager;
use Zain\LaravelDoctrine\Algolia\Feature\FeatureTest;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\{Comment, ContentAggregator, Image, Link, Post, Tag};
use Zain\LaravelDoctrine\Algolia\SearchService;
use Zain\LaravelDoctrine\Algolia\Services\AlgoliaSearchService;

class SearchServiceTest extends FeatureTest
{
    protected AlgoliaSearchService $service;
    protected EntityManager $em;

    public function setUp(): void
    {
        parent::setUp();

        /** @var AlgoliaSearchService $service */
        $service = app(SearchService::class);
        /** @var EntityManager $em */
        $em = app('em');

        $this->service = $service;
        $this->em = $em;
    }

    public function testIsSearchableMethod()
    {
        $this->assertTrue($this->service->isSearchable(Post::class));
        $this->assertTrue($this->service->isSearchable(Comment::class));
        $this->assertFalse($this->service->isSearchable(FeatureTest::class));
        $this->assertFalse($this->service->isSearchable(Image::class));
        $this->assertTrue($this->service->isSearchable(ContentAggregator::class));
        $this->assertTrue($this->service->isSearchable(Tag::class));
        $this->assertTrue($this->service->isSearchable(Link::class));
        $this->cleanUp();
    }

    public function cleanUp()
    {
        $this->service->delete(Post::class)->wait();
        $this->service->delete(Comment::class)->wait();
        $this->service->delete(ContentAggregator::class)->wait();
    }

    public function testGetSearchableEntities()
    {
        $result = $this->service->getSearchables();
        $this->assertEquals([
            Post::class,
            Comment::class,
            ContentAggregator::class,
            Tag::class,
            Link::class,
        ], $result);
        $this->cleanUp();
    }

    public function testExceptionIfNoId()
    {
        $this->expectException(\Exception::class);
        $this->service->index($this->em, new Post());
        $this->cleanUp();
    }

    public function testIndexedDataAreSearchable()
    {
        $posts = [];
        for ($i = 0; $i < 3; $i++) {
            $posts[] = $this->createPost($i);
        }

        // index Data
        $this->service->index($this->em, $this->createPost(10));
        $this->service->index(
            $this->em,
            array_merge(
                $posts,
                [$this->createComment(1), $this->createImage(1)]
            )
        )->wait();

        // RawSearch
        $searchPost = $this->service->rawSearch(Post::class);
        $this->assertCount(4, $searchPost['hits']);
        $searchPost = $this->service->rawSearch(
            Post::class,
            '',
            [
                'page' => 0,
                'hitsPerPage' => 1,
            ]
        );
        $this->assertCount(1, $searchPost['hits']);

        $searchPostEmpty = $this->service->rawSearch(Post::class, 'with no result');
        $this->assertCount(0, $searchPostEmpty['hits']);

        $searchComment = $this->service->rawSearch(Comment::class);
        $this->assertCount(1, $searchComment['hits']);

        $searchPost = $this->service->rawSearch(ContentAggregator::class, 'test');
        $this->assertCount(4, $searchPost['hits']);

        $searchPost = $this->service->rawSearch(ContentAggregator::class, 'Comment content');
        $this->assertCount(1, $searchPost['hits']);

        // Count
        $this->assertEquals(4, $this->service->count(Post::class, 'test'));
        $this->assertEquals(1, $this->service->count(Comment::class, 'content'));
        $this->assertEquals(6, $this->service->count(ContentAggregator::class));

        // Cleanup
        $this->service->delete(Post::class);
        $this->service->delete(Comment::class);
        $this->service->delete(ContentAggregator::class);
        $this->cleanUp();
    }

    public function testIndexedDataCanBeRemoved()
    {
        $posts = [];
        for ($i = 0; $i < 3; $i++) {
            $posts[] = $this->createPost($i);
        }

        $comment = $this->createComment(1);
        $image = $this->createImage(1);

        // index Data
        $this->service->index(
            $this->em,
            array_merge($posts, [$comment, $image])
        )->wait();

        // Remove the last post.
        $this->service->remove($this->em, end($posts))->wait();

        // Expects 2 posts and 1 comment.
        $this->assertEquals(2, $this->service->count(Post::class));
        $this->assertEquals(1, $this->service->count(Comment::class));

        // The content aggregator expects 2 + 1 + 1.
        $this->assertEquals(4, $this->service->count(ContentAggregator::class));

        // Remove the only comment that exists.
        $this->service->remove($this->em, $comment)->wait();

        // Expects 2 posts and 0 comments.
        $this->assertEquals(2, $this->service->count(Post::class));
        $this->assertEquals(0, $this->service->count(Comment::class));

        // The content aggregator expects 2 + 0 + 1.
        $this->assertEquals(3, $this->service->count(ContentAggregator::class));

        // Remove the only image that exists.
        $this->service->remove($this->em, $image)->wait();

        // The content aggregator expects 2 + 0 + 0.
        $this->assertEquals(2, $this->service->count(ContentAggregator::class));
        $this->cleanUp();
    }

    public function testRawSearchRawContent()
    {
        $postIndexed = $this->createPost(10);
        $postIndexed->setTitle('Foo Bar');

        $this->service->index($this->em, $postIndexed)->wait();

        // Using entity.
        $results = $this->service->rawSearch(Post::class, 'Foo Bar');
        $this->assertEquals($results['hits'][0]['title'], $postIndexed->getTitle());

        // Using aggregator.
        $results = $this->service->rawSearch(ContentAggregator::class, 'Foo Bar');
        $this->assertEquals($results['hits'][0]['title'], $postIndexed->getTitle());
        $this->cleanUp();
    }

    public function testIndexIfCondition()
    {
        $posts = [];
        for ($i = 0; $i < 3; $i++) {
            $posts[] = $this->createPost($i);
        }

        $post = $this->createPost(10);
        $post->setTitle('Foo');

        $posts[] = $post;

        // index Data: Total 4 posts.
        $this->service->index($this->em, $posts)->wait();

        // The content aggregator expects 3 ( not 4, because of the index_if condition ).
        $this->assertEquals(3, $this->service->count(ContentAggregator::class));
        $this->cleanUp();
    }

    public function testClearUnsearchableEntity()
    {
        $this->expectException(\Exception::class);
        $image = $this->createSearchableImage();

        $this->service->index($this->em, [$image]);
        $this->service->clear(Image::class);
        $this->cleanUp();
    }

    public function testShouldNotBeIndexed()
    {
        $link = new Link();
        $this->assertFalse($this->service->shouldBeIndexed($link));
        $this->cleanUp();
    }
}
