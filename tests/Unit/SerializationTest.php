<?php

namespace Zain\LaravelDoctrine\Algolia\Unit;

use ReflectionClass;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Comment;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Post;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Tag;
use Zain\LaravelDoctrine\Algolia\Fixtures\Normalizers\CommentNormalizer;
use Zain\LaravelDoctrine\Algolia\Fixtures\Normalizers\PostNormalizer;
use Zain\LaravelDoctrine\Algolia\Searchable;
use Zain\LaravelDoctrine\Algolia\SearchableEntity;
use Zain\LaravelDoctrine\Algolia\Serialization\SerializerFactory;
use Zain\LaravelDoctrine\Algolia\TestCase;

class SerializationTest extends TestCase
{
    /**
     * @environment-setup configureCommentNormalizer
     */
    public function testSerializerHasRequiredNormalizers()
    {
        $serializer = app(SerializerFactory::class)->create();

        $reflection = new ReflectionClass($serializer);
        $normalizersProperty = $reflection->getProperty('normalizers');
        $normalizersProperty->setAccessible(true);
        $normalizers = $normalizersProperty->getValue($serializer);

        $classes = array_map(function ($value) {
            return get_class($value);
        }, $normalizers);

        $this->assertStringContainsString('ObjectNormalizer', end($classes));
        $this->assertStringContainsString('CustomNormalizer', $classes[1]);
        $this->assertEquals(CommentNormalizer::class, $classes[0]);
        $this->assertGreaterThan(3, count($classes));
    }

    /**
     * @environment-setup configurePostEntityIndex
     */
    public function testConfiguredNormalizer()
    {
        $datetime = new \DateTime();
        $dateSerializer = new Serializer([new DateTimeNormalizer()]);
        // This way we can test that DateTime's are serialized with DateTimeNormalizer
        // And not the default ObjectNormalizer
        $serializedDateTime = $dateSerializer->normalize($datetime, Searchable::NORMALIZATION_FORMAT);

        $post = new Post([
            'id' => 12,
            'title' => 'a simple post',
            'content' => 'some text',
            'publishedAt' => $datetime,
        ]);

        $post->addComment(new Comment([
            'content' => 'a great comment',
            'publishedAt' => $datetime,
            'post' => $post,
        ]));

        $postMeta = app('em')->getClassMetadata(Post::class);

        $searchablePost = new SearchableEntity(
            'posts',
            $post,
            $postMeta,
            app(SerializerFactory::class),
        );

        $expected = [
            'id' => 12,
            'title' => 'a simple post',
            'publishedAt' => $serializedDateTime,
        ];

        $this->assertEquals($expected, $searchablePost->getSearchableArray());
    }

    public function testNormalizableEntityToSearchableArray()
    {
        $datetime = new \DateTime();
        $dateSerializer = new Serializer([new DateTimeNormalizer()]);
        // This way we can test that DateTime's are serialized with DateTimeNormalizer
        // And not the default ObjectNormalizer
        $serializedDateTime = $dateSerializer->normalize($datetime, Searchable::NORMALIZATION_FORMAT);

        $tag = new Tag(
            [
                'id' => 123,
                'publishedAt' => $datetime,
            ]
        );
        $tagMeta = app('em')->getClassMetadata(Tag::class);

        $searchableTag = new SearchableEntity(
            'tags',
            $tag,
            $tagMeta,
            app(SerializerFactory::class)
        );

        $expected = [
            'id' => 123,
            'name' => 'this test is correct',
            'count' => 10,
            'publishedAt' => $serializedDateTime,
        ];

        $this->assertEquals($expected, $searchableTag->getSearchableArray());
    }

    /**
     * @environment-setup configureCommentNormalizer
     */
    public function testDedicatedNormalizer()
    {
        $comment = new Comment(
            [
                'id' => 99,
                'content' => 'hey, this is a comment',
                'post' => new Post(['title' => 'Another super post']),
            ]
        );

        $searchableComment = new SearchableEntity(
            'comments',
            $comment,
            app('em')->getClassMetadata(Comment::class),
            app(SerializerFactory::class),
        );
        $expected = [
            'content' => 'hey, this is a comment',
            'post_title' => 'Another super post',
        ];

        $this->assertEquals($expected, $searchableComment->getSearchableArray());
    }
}
