<?php

return [
    'managers' => [
        'default' => [
            'dev' => false,
            'meta' => 'annotations',
            'connection' => env('DB_CONNECTION', 'testing'),
            'namespaces' => [],
            'paths' => [
                __DIR__ . '/../Fixtures/Entities',
            ],
            'repository' => Doctrine\ORM\EntityRepository::class,
            'proxies' => [
                'namespace' => false,
                'path' => storage_path('proxies'),
                'auto_generate' => true,
            ],
            'events' => [
                'listeners' => [],
                'subscribers' => []
            ],
            'filters' => [],
            'mapping_types' => []
        ]
    ],
    'extensions' => [
        Zain\LaravelDoctrine\Algolia\AlgoliaExtension::class,
    ],
    'custom_types' => [],
    'custom_datetime_functions' => [],
    'custom_numeric_functions' => [],
    'custom_string_functions' => [],
    'custom_hydration_modes' => [],
    'logger' => false,
    'cache' => [
        'second_level' => false,
        'default' => 'array',
        'namespace' => null,
        'metadata' => [
            'driver' => 'array',
            'namespace' => null,
        ],
        'query' => [
            'driver' => 'array',
            'namespace' => null,
        ],
        'result' => [
            'driver' => 'array',
            'namespace' => null,
        ],
    ],
    'gedmo' => [
        'all_mappings' => false
    ],
    'doctrine_presence_verifier' => true,
    'notifications' => [
        'channel' => 'database'
    ]
];
