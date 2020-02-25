<?php

namespace Zain\LaravelDoctrine\Algolia\Unit;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Zain\LaravelDoctrine\Algolia\Configuration;
use Zain\LaravelDoctrine\Algolia\TestCase;

class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider dataTestConfigurationTree
     *
     * @param mixed $inputConfig
     * @param mixed $expectedConfig
     */
    public function testConfigurationTree($inputConfig, $expectedConfig)
    {
        $configuration = new Configuration();

        $finalizedConfig = (new Processor())->processConfiguration($configuration, [$inputConfig]);

        $this->assertEquals($expectedConfig, $finalizedConfig);
    }

    public function dataTestConfigurationTree()
    {
        return [
            'test empty config for default value' => [
                [],
                [
                    'prefix'                   => null,
                    'nbResults'                => 20,
                    'batchSize'                => 500,
                    'serializer'               => 'serializer',
                    'settingsDirectory'        => null,
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                    'indices'                  => [],
                    'normalizers'              => [],
                ],
            ],
            'Simple config' => [
                [
                    'prefix'    => 'sf_',
                    'nbResults' => 40,
                    'batchSize' => 100,
                ], [
                    'prefix'                   => 'sf_',
                    'nbResults'                => 40,
                    'batchSize'                => 100,
                    'serializer'               => 'serializer',
                    'settingsDirectory'        => null,
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                    'indices'                  => [],
                    'normalizers'              => [],
                ],
            ],
            'Index config' => [
                [
                    'prefix'  => 'sf_',
                    'indices' => [
                        ['name' => 'posts', 'class' => 'App\Entity\Post', 'index_if' => null],
                        ['name' => 'tags', 'class' => 'App\Entity\Tag', 'enable_serializer_groups' => true, 'index_if' => null],
                    ],
                ], [
                    'prefix'                   => 'sf_',
                    'nbResults'                => 20,
                    'batchSize'                => 500,
                    'serializer'               => 'serializer',
                    'settingsDirectory'        => null,
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                    'indices'                  => [
                        'posts' => [
                            'class'                    => 'App\Entity\Post',
                            'enable_serializer_groups' => false,
                            'index_if'                 => null,
                            'normalizer'               => null,
                        ],
                        'tags' => [
                            'class'                    => 'App\Entity\Tag',
                            'enable_serializer_groups' => true,
                            'index_if'                 => null,
                            'normalizer'               => null,
                        ],
                    ],
                    'normalizers'              => [],
                ],
            ],
            'Index config with Custom Normalizer' => [
                [
                    'prefix'  => 'sf_',
                    'indices' => [
                        ['name' => 'posts', 'class' => 'App\Entity\Post', 'normalizer' => 'App\Normalizer\PostNormalizer'],
                        ['name' => 'tags', 'class' => 'App\Entity\Tag', 'normalizer' => 'App\Normalizer\TagNormalizer'],
                    ],
                ], [
                    'prefix'                   => 'sf_',
                    'nbResults'                => 20,
                    'batchSize'                => 500,
                    'serializer'               => 'serializer',
                    'settingsDirectory'        => null,
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                    'indices'                  => [
                        'posts' => [
                            'class'                    => 'App\Entity\Post',
                            'enable_serializer_groups' => false,
                            'index_if'                 => null,
                            'normalizer'               => 'App\Normalizer\PostNormalizer'
                        ],
                        'tags' => [
                            'class'                    => 'App\Entity\Tag',
                            'enable_serializer_groups' => false,
                            'index_if'                 => null,
                            'normalizer'               => 'App\Normalizer\TagNormalizer'
                        ],
                    ],
                    'normalizers'              => [],
                ],
            ],
            'Index config in Hash Map format' => [
                [
                    'indices' => [
                        'posts' => ['class' => 'App\Entity\Post', 'normalizer' => 'App\Normalizer\PostNormalizer'],
                        'tags' =>  ['class' => 'App\Entity\Tag',  'normalizer' => 'App\Normalizer\TagNormalizer' ],
                    ],
                ], [
                    'prefix'                   => null,
                    'nbResults'                => 20,
                    'batchSize'                => 500,
                    'serializer'               => 'serializer',
                    'settingsDirectory'        => null,
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                    'indices'                  => [
                        'posts' => [
                            'class'                    => 'App\Entity\Post',
                            'enable_serializer_groups' => false,
                            'index_if'                 => null,
                            'normalizer'               => 'App\Normalizer\PostNormalizer'
                        ],
                        'tags' => [
                            'class'                    => 'App\Entity\Tag',
                            'enable_serializer_groups' => false,
                            'index_if'                 => null,
                            'normalizer'               => 'App\Normalizer\TagNormalizer'
                        ],
                    ],
                    'normalizers'              => [],
                ],
            ],
            'Simple confix with normalizers' => [
                [
                    'normalizers' => ['App\Normalizer\PostNormalizer', 'App\Normalizer\CommentNormalizer'],
                ],
                [
                    'prefix'                   => null,
                    'nbResults'                => 20,
                    'batchSize'                => 500,
                    'serializer'               => 'serializer',
                    'settingsDirectory'        => null,
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                    'indices'                  => [],
                    'normalizers'              => [
                        'App\Normalizer\PostNormalizer',
                        'App\Normalizer\CommentNormalizer'
                    ],
                ],
            ],
        ];
    }
}
