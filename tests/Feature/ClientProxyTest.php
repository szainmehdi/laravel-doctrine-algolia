<?php

namespace Zain\LaravelDoctrine\Algolia\Feature;

use Algolia\AlgoliaSearch\SearchClient;
use ProxyManager\Proxy\ProxyInterface;
use Zain\LaravelDoctrine\Algolia\Exception\ConfigurationException;
use Zain\LaravelDoctrine\Algolia\TestCase;

class ClientProxyTest extends TestCase
{
    private static $values = [];

    public static function setUpBeforeClass(): void
    {
        // Unset env variables to make sure Algolia
        // Credentials are only required when the
        // client is used. Save them to restore them after.
        self::$values = [
            'env_id'  => getenv('ALGOLIA_APP_ID'),
            'env_key' => getenv('ALGOLIA_API_KEY'),
            '_env'    => $_ENV,
            '_server' => $_SERVER,
        ];

        putenv('ALGOLIA_APP_ID');
        putenv('ALGOLIA_API_KEY');
        unset($_ENV['ALGOLIA_APP_ID']);
        unset($_ENV['ALGOLIA_API_KEY']);
        unset($_SERVER['ALGOLIA_APP_ID']);
        unset($_SERVER['ALGOLIA_API_KEY']);
    }

    public static function tearDownAfterClass(): void
    {
        putenv('ALGOLIA_APP_ID=' . self::$values['env_id']);
        putenv('ALGOLIA_API_KEY=' . self::$values['env_key']);
        $_ENV    = self::$values['_env'];
        $_SERVER = self::$values['_server'];
    }

    public function testClientIsProxied()
    {
        $interfaces = class_implements(app(SearchClient::class));

        $this->assertTrue(in_array(ProxyInterface::class, $interfaces));
    }

    public function testProxiedClientFailIfNoEnvVarsFound()
    {
        $this->expectException(ConfigurationException::class);
        app(SearchClient::class)->listIndices();
    }
}
