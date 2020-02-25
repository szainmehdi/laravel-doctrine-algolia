<?php

use Zain\LaravelDoctrine\Algolia\Fixtures\Entities;
use Zain\LaravelDoctrine\Algolia\Fixtures\Normalizers;

return [
    'nbResults' => 12,
    'batchSize' => 100,
    'settingsDirectory' => storage_path('algolia'),
    'indices' => [
        'posts' => [
            'class' => Entities\Post::class,
        ],
        'comments' => [
            'class' => Entities\Comment::class,
        ],
        'contents' => [
            'class' => Entities\ContentAggregator::class,
            'index_if' => 'isVisible',
        ],
        'tags' => [
            'class' => Entities\Tag::class,
            'index_if' => 'isPublic',
        ],
        'links' => [
            'class' => Entities\Link::class,
            'index_if' => 'isSponsored',
        ],
    ],
    'normalizers' => [
        Normalizers\CommentNormalizer::class,
        Normalizers\PostNormalizer::class,
    ]
];
