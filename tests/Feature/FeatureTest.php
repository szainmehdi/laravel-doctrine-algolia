<?php

declare(strict_types=1);

namespace Zain\LaravelDoctrine\Algolia\Feature;

use Doctrine\ORM\EntityManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\Console\Kernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Zain\LaravelDoctrine\Algolia\SearchableEntity;
use Zain\LaravelDoctrine\Algolia\SearchService;
use Zain\LaravelDoctrine\Algolia\Serialization\SerializerFactory;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Comment;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Image;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Post;
use Zain\LaravelDoctrine\Algolia\TestCase;

abstract class FeatureTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app->config->set('algolia.search', require __DIR__ . '/_config.search.php');
        $app->config->set('doctrine', require __DIR__ . '/_config.doctrine.php');
    }

    protected function createSearchablePost()
    {
        $post = $this->createPost(rand(100, 300));

        return new SearchableEntity(
            $this->getPrefix() . 'posts',
            $post,
            app('em')->getClassMetadata(Post::class),
            app(SerializerFactory::class)
        );
    }

    protected function createPost($id = null)
    {
        $post = new Post();
        $post->setTitle('Test');
        $post->setContent('Test content');

        if (!is_null($id)) {
            $post->setId($id);
        }

        return $post;
    }

    protected function getPrefix()
    {
        return app(SearchService::class)->getConfiguration()['prefix'];
    }

    protected function createComment($id = null)
    {
        $comment = new Comment();
        $comment->setContent('Comment content');
        $comment->setPost(new Post(['title' => 'What a post!']));

        if (!is_null($id)) {
            $comment->setId($id);
        }

        return $comment;
    }

    protected function createSearchableImage()
    {
        $image = $this->createImage(rand(100, 300));

        return new SearchableEntity(
            $this->getPrefix() . 'image',
            $image,
            app('em')->getClassMetadata(Image::class),
            app(SerializerFactory::class)
        );
    }

    protected function createImage($id = null)
    {
        $image = new Image();

        if (!is_null($id)) {
            $image->setId($id);
        }

        return $image;
    }

    protected function refreshDb()
    {
        app()->make(Kernel::class)->call('doctrine:schema:drop', [
            '--full' => true,
            '--force' => true,
            '--quiet' => true,
        ]);
        app()->make(Kernel::class)->call('doctrine:schema:create', [
            '--quiet' => true,
        ]);
    }

    protected function getFileName($indexName, $type)
    {
        return sprintf(
            '%s/%s-%s.json',
            app(SearchService::class)->getConfiguration()['settingsDirectory'],
            $indexName,
            $type
        );
    }

    protected function getEntityManager(): EntityManager
    {
        return app('em');
    }

    protected function getDefaultConfig()
    {
        return [
            'hitsPerPage' => 20,
            'maxValuesPerFacet' => 100,
        ];
    }

    protected function makeCommand(string $class): Command
    {
        /** @var Command $command */
        $command = app($class);
        $command->setLaravel($this->app);
        return $command;
    }
}
