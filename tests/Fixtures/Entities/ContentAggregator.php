<?php

namespace Zain\LaravelDoctrine\Algolia\Fixtures\Entities;

use Doctrine\ORM\Mapping as ORM;
use Zain\LaravelDoctrine\Algolia\Entity\Aggregator;

/**
 * @ORM\Entity
 */
class ContentAggregator extends Aggregator
{
    public function getIsVisible()
    {
        if ($this->entity instanceof Post) {
            return $this->entity->getTitle() !== 'Foo';
        }

        return true;
    }

    public static function getEntities()
    {
        return [
            Post::class,
            Comment::class,
            Image::class,
        ];
    }
}
