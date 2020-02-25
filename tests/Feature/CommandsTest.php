<?php

namespace Zain\LaravelDoctrine\Algolia\TestCase;

use Algolia\AlgoliaSearch\SearchClient;
use Algolia\AlgoliaSearch\SearchIndex;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Tester\CommandTester;
use Zain\LaravelDoctrine\Algolia\Console\SearchClearCommand;
use Zain\LaravelDoctrine\Algolia\Console\SearchImportCommand;
use Zain\LaravelDoctrine\Algolia\Console\SearchSettingsBackupCommand;
use Zain\LaravelDoctrine\Algolia\Console\SearchSettingsPushCommand;
use Zain\LaravelDoctrine\Algolia\Feature\FeatureTest;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Comment;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\ContentAggregator;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Post;
use Zain\LaravelDoctrine\Algolia\SearchService;

class CommandsTest extends FeatureTest
{
    protected SearchService $searchService;
    protected SearchClient $client;
    protected EntityManager $em;
    protected Connection $connection;
    protected string $indexName;
    protected ?AbstractPlatform $platform;
    protected SearchIndex $index;

    public function setUp(): void
    {
        parent::setUp();
        $this->searchService = app()->make(SearchService::class);
        $this->client = app()->make(SearchClient::class);
        $this->em = app()->make(EntityManager::class);
        $this->connection = $this->em->getConnection();
        $this->platform = $this->connection->getDatabasePlatform();
        $this->indexName = 'posts';
        $this->index = $this->client->initIndex($this->getPrefix() . $this->indexName);
        $this->index->setSettings($this->getDefaultConfig())->wait();

        $contentsIndexName = 'contents';
        $contentsIndex = $this->client->initIndex($this->getPrefix() . $contentsIndexName);
        $contentsIndex->setSettings($this->getDefaultConfig())->wait();

        $this->refreshDb();
    }

    public function testSearchClearUnknownIndex()
    {
        $unknownIndexName = 'test';

        $command = $this->makeCommand(SearchClearCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--indices' => $unknownIndexName,
        ]);

        // Checks output and ensure it failed
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No index named ' . $unknownIndexName, $output);
        $this->cleanUp();
    }

    public function cleanUp()
    {
        $this->searchService->delete(Post::class)->wait();
        $this->searchService->delete(Comment::class)->wait();
        $this->searchService->delete(ContentAggregator::class)->wait();
    }

    public function testSearchClear()
    {
        $this->searchService->index($this->em, $this->createPost(10))->wait();

        // Checks that post was created and indexed
        $searchPost = $this->searchService->rawSearch(Post::class);
        $this->assertCount(1, $searchPost['hits']);

        $command = $this->makeCommand(SearchClearCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Checks output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Cleared posts', $output);
        $this->cleanUp();
    }

    public function testSearchImportAggregator()
    {
        $now = new \DateTime();
        $this->connection->insert(
            $this->indexName,
            [
                'title' => 'Test',
                'content' => 'Test content',
                'published_at' => $now->format('Y-m-d H:i:s'),
            ]
        );
        $this->connection->insert(
            $this->indexName,
            [
                'title' => 'Test2',
                'content' => 'Test content2',
                'published_at' => $now->format('Y-m-d H:i:s'),
            ]
        );
        $this->connection->insert(
            $this->indexName,
            [
                'title' => 'Test3',
                'content' => 'Test content3',
                'published_at' => $now->format('Y-m-d H:i:s'),
            ]
        );

        $command = $this->makeCommand(SearchImportCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--indices' => 'contents']);

        // Checks output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Done!', $output);

        $iteration = 0;
        $expectedResult = 3;
        do {
            $searchPost = $this->searchService->rawSearch(ContentAggregator::class);
            sleep(1);
            $iteration++;
        } while (count($searchPost['hits']) !== $expectedResult || $iteration < 10);

        // Ensure posts were imported into contents index
        $searchPost = $this->searchService->rawSearch(ContentAggregator::class);
        $this->assertCount($expectedResult, $searchPost['hits']);
        // clearup table
        $this->connection->executeUpdate($this->platform->getTruncateTableSQL($this->indexName, true));
        $this->cleanUp();
    }

    /**
     * @testWith [true, false]
     */
    public function testSearchImport($isAtomic)
    {
        $now = new \DateTime();
        $this->connection->insert(
            $this->indexName,
            [
                'title' => 'Test',
                'content' => 'Test content',
                'published_at' => $now->format('Y-m-d H:i:s'),
            ]
        );
        $this->connection->insert(
            $this->indexName,
            [
                'title' => 'Test2',
                'content' => 'Test content2',
                'published_at' => $now->format('Y-m-d H:i:s'),
            ]
        );
        $this->connection->insert(
            $this->indexName,
            [
                'title' => 'Test3',
                'content' => 'Test content3',
                'published_at' => $now->format('Y-m-d H:i:s'),
            ]
        );

        $command = $this->makeCommand(SearchImportCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--indices' => $this->indexName,
            '--atomic' => $isAtomic,
        ]);

        // Checks output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Done!', $output);

        // Ensure posts were imported
        $iteration = 0;
        $expectedResult = 3;
        do {
            $searchPost = $this->searchService->rawSearch(Post::class);
            sleep(1);
            $iteration++;
        } while (count($searchPost['hits']) !== $expectedResult || $iteration < 10);

        $this->assertCount($expectedResult, $searchPost['hits']);
        // cleanup table
        $this->connection->executeUpdate($this->platform->getTruncateTableSQL($this->indexName, true));
        $this->cleanUp();
    }

    public function testSearchSettingsBackupCommand()
    {
        $settingsToUpdate = [
            'hitsPerPage' => 51,
            'maxValuesPerFacet' => 99,
        ];
        $this->index->setSettings($settingsToUpdate)->wait();
        $command = $this->makeCommand(SearchSettingsBackupCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--indices' => $this->indexName,
        ]);

        // Checks output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Saved settings', $output);

        $settingsFile = $this->getFileName($this->indexName, 'settings');

        $settingsFileContent = json_decode(file_get_contents($settingsFile), true);
        $this->assertContains($settingsToUpdate['hitsPerPage'], $settingsFileContent);
        $this->assertContains($settingsToUpdate['maxValuesPerFacet'], $settingsFileContent);
    }

    public function testSearchSettingsPushCommand()
    {
        $settingsToUpdate = [
            'hitsPerPage' => 50,
            'maxValuesPerFacet' => 100,
        ];
        $this->index->setSettings($settingsToUpdate)->wait();
        $settings = $this->index->getSettings();
        $settingsFile = $this->getFileName($this->indexName, 'settings');

        $settingsFileContent = json_decode(file_get_contents($settingsFile), true);
        $this->assertNotEquals($settings['hitsPerPage'], $settingsFileContent['hitsPerPage']);
        $this->assertNotEquals($settings['maxValuesPerFacet'], $settingsFileContent['maxValuesPerFacet']);

        $command = $this->makeCommand(SearchSettingsPushCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--indices' => $this->indexName,
        ]);

        // Checks output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Pushed settings', $output);

        // check if the settings were imported
        $iteration = 0;
        do {
            $newSettings = $this->index->getSettings();
            sleep(1);
            $iteration++;
        } while ($newSettings['hitsPerPage'] !== $settingsFileContent['hitsPerPage'] || $iteration < 10);

        $this->assertEquals($newSettings['hitsPerPage'], $settingsFileContent['hitsPerPage']);
        $this->assertEquals($newSettings['maxValuesPerFacet'], $settingsFileContent['maxValuesPerFacet']);
        $this->cleanUp();
    }
}
