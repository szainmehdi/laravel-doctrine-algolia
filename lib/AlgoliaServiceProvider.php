<?php

declare(strict_types=1);

namespace Zain\LaravelDoctrine\Algolia;

use Algolia\AlgoliaSearch\{SearchClient, Support\UserAgent};
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Illuminate\Support\ServiceProvider;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use Symfony\Component\Config\Definition\Processor;
use Zain\LaravelDoctrine\Algolia\{
    Console\SearchClearCommand,
    Console\SearchImportCommand,
    Console\SearchSettingsBackupCommand,
    Console\SearchSettingsPushCommand,
    EventListener\SearchIndexerSubscriber,
    Exception\ConfigurationException,
    Serialization\SerializerFactory,
    Services\AlgoliaSearchService,
    Settings\SettingsManager
};

class AlgoliaServiceProvider extends ServiceProvider
{
    public const VERSION = '0.1';

    public function boot(): void
    {
        UserAgent::addCustomUserAgent('Laravel Doctrine Algolia', self::VERSION);
        UserAgent::addCustomUserAgent('Laravel', $this->app->version());

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/algolia.php' => config_path('algolia.php'),
            ], 'config');

            $this->commands([
                SearchImportCommand::class,
                SearchClearCommand::class,
                SearchSettingsBackupCommand::class,
                SearchSettingsPushCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/algolia.php', 'algolia');

        $this->app->bind(SerializerFactory::class, function () {
            return new SerializerFactory($this->getSearchConfiguration());
        });

        $this->app->bind(SettingsManager::class, function () {
            return new SettingsManager($this->app->make(SearchClient::class), $this->getSearchConfiguration());
        });

        $this->app->bind(SearchClient::class, function () {
            $factory = new LazyLoadingValueHolderFactory();
            return $factory->createProxy(
                SearchClient::class,
                function (&$client, &$initializer) {
                    $app = config('algolia.app');
                    $secret = config('algolia.secret');

                    if (!$app || !$secret) {
                        throw new ConfigurationException('Algolia app ID and secret are missing. Check your config.');
                    }

                    $client = SearchClient::create($app, $secret);
                    $initializer = null; // turning off further lazy initialization
                }
            );
        });

        $this->app->bind(SearchService::class, function () {
            return new AlgoliaSearchService(
                $this->app->make(SerializerFactory::class),
                $this->app->make(Engine::class),
                $this->getSearchConfiguration()
            );
        });

        $this->app->bind(AtomicSearchService::class, function () {
            $config = $this->getSearchConfiguration();
            $config = [
                'prefix' => 'atomic_temporary_' . uniqid('php_', true) . $config['prefix']
            ] + $config;
            return new AlgoliaSearchService(
                $this->app->make(SerializerFactory::class),
                $this->app->make(Engine::class),
                $config
            );
        });

        $this->app->bind(SearchIndexerSubscriber::class, function() {
            return new SearchIndexerSubscriber(
                $this->app->make(SearchService::class),
                $this->getSearchConfiguration()['doctrineSubscribedEvents']
            );
        });

        $this->addDoctrineMappings();
    }

    protected function addDoctrineMappings(): void
    {
        $this->app['events']->listen('doctrine.extensions.booting', function () {
            $registry = $this->app->make('registry');

            foreach ($registry->getManagers() as $manager) {
                $chain = $manager->getConfiguration()->getMetadataDriverImpl();

                $chain->addDriver(new SimplifiedXmlDriver([
                    __DIR__ . '/Resources/config/doctrine' => 'Zain\LaravelDoctrine\Algolia\Entity',
                ]), 'Zain\LaravelDoctrine\Algolia\Entity');
            }
        });
    }

    public function getSearchConfiguration(): array
    {
        $definition = new Configuration();
        $processor = new Processor();

        return $processor->processConfiguration($definition, [config('algolia.search')]);
    }
}
