<?php

namespace Zain\LaravelDoctrine\Algolia\TestCase;

use Algolia\AlgoliaSearch\SearchClient;
use Zain\LaravelDoctrine\Algolia\Feature\FeatureTest;
use Zain\LaravelDoctrine\Algolia\SearchService;
use Zain\LaravelDoctrine\Algolia\Settings\SettingsManager;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Post;

class SettingsTest extends FeatureTest
{
    private SearchClient $client;
    private SettingsManager $settings;
    private SearchService $service;
    private array $indices;
    private string $index;

    public function setUp(): void
    {
        parent::setUp();

        /** @var SearchClient $client */
        $client = app(SearchClient::class);
        /** @var SettingsManager $settings */
        $settings = app(SettingsManager::class);
        /** @var SearchService $service */
        $service = app(SearchService::class);

        $this->client = $client;
        $this->settings = $settings;
        $this->service = $service;
        $this->indices = $service->getConfiguration()['indices'];
        $this->index = 'posts';
    }

    public function testBackup()
    {
        $this->rrmdir($this->service->getConfiguration()['settingsDirectory']);
        $settingsToUpdate = [
            'hitsPerPage' => 51,
            'maxValuesPerFacet' => 99,
        ];
        $index = $this->client->initIndex($this->getPrefix() . $this->index);
        $index->setSettings($settingsToUpdate)->wait();

        $message = $this->settings->backup(['indices' => [$this->index]]);

        $this->assertStringContainsString('Saved settings for', $message[0]);
        $this->assertFileExists($this->getFileName($this->index, 'settings'));

        $savedSettings = json_decode(
            file_get_contents(
                $this->getFileName($this->index, 'settings')
            ),
            true
        );

        $this->assertEquals($settingsToUpdate['hitsPerPage'], $savedSettings['hitsPerPage']);
        $this->assertEquals($settingsToUpdate['maxValuesPerFacet'], $savedSettings['maxValuesPerFacet']);
    }

    public function testBackupWithoutIndices()
    {
        $this->rrmdir($this->service->getConfiguration()['settingsDirectory']);
        $settingsToUpdate = [
            'hitsPerPage' => 51,
            'maxValuesPerFacet' => 99,
        ];

        foreach ($this->indices as $indexName => $configIndex) {
            $index = $this->client->initIndex($this->getPrefix() . $indexName);
            $index->setSettings($settingsToUpdate)->wait();
        }

        $message = $this->settings->backup(['indices' => []]);

        $this->assertStringContainsString('Saved settings for', $message[0]);

        foreach ($this->indices as $indexName => $configIndex) {
            $this->assertFileExists($this->getFileName($this->index, 'settings'));

            $savedSettings = json_decode(
                file_get_contents(
                    $this->getFileName($indexName, 'settings')
                ),
                true
            );

            $this->assertEquals($settingsToUpdate['hitsPerPage'], $savedSettings['hitsPerPage']);
            $this->assertEquals($settingsToUpdate['maxValuesPerFacet'], $savedSettings['maxValuesPerFacet']);
        }
    }

    /**
     * @depends testBackup
     */
    public function testPush()
    {
        $settingsToUpdate = [
            'hitsPerPage' => 12,
            'maxValuesPerFacet' => 100,
        ];
        $index = $this->client->initIndex($this->getPrefix() . $this->index);
        $index->setSettings($settingsToUpdate)->wait();

        $message = $this->settings->push(['indices' => [$this->index]]);

        $this->assertStringContainsString('Pushed settings for', $message[0]);

        $savedSettings = json_decode(
            file_get_contents(
                $this->getFileName($this->index, 'settings')
            ),
            true
        );

        for ($i = 0; $i < 5; $i++) {
            sleep(1);
            $settings = $index->getSettings();
            if (12 != $settings['hitsPerPage']) {
                $this->assertEquals($savedSettings, $settings);
            }
        }
    }

    /**
     * @depends testBackupWithoutIndices
     */
    public function testPushWithoutIndices()
    {
        $settingsToUpdate = [
            'hitsPerPage' => 12,
            'maxValuesPerFacet' => 100,
        ];

        foreach ($this->indices as $indexName => $configIndex) {
            $index = $this->client->initIndex($this->getPrefix() . $indexName);
            $index->setSettings($settingsToUpdate)->wait();
        }

        $message = $this->settings->push(['indices' => []]);

        $this->assertStringContainsString('Pushed settings for', $message[0]);

        foreach ($this->indices as $indexName => $configIndex) {
            $savedSettings = json_decode(
                file_get_contents(
                    $this->getFileName($indexName, 'settings')
                ),
                true
            );

            for ($i = 0; $i < 5; $i++) {
                sleep(1);
                $settings = $index->getSettings();
                if (12 != $settings['hitsPerPage']) {
                    $this->assertEquals($savedSettings, $settings);
                }
            }
        }
        $this->cleanUp();
    }

    public function cleanUp()
    {
        $this->service->delete(Post::class)->wait();
    }

    /**
     * @see https://www.php.net/rmdir
     */
    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($dir . '/' . $object)) {
                        rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
