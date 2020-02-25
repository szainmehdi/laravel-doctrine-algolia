<?php

namespace Zain\LaravelDoctrine\Algolia\Fixtures\Entities;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zain\LaravelDoctrine\Algolia\Searchable;

/**
 * @ORM\Entity
 * @ORM\Table(name="links")
 */
class Link implements NormalizableInterface
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $id;

    private $name;

    private $url;

    public function __construct(array $attributes = [])
    {
        $this->id = isset($attributes['id']) ? $attributes['id'] : null;
        $this->name = isset($attributes['name']) ? $attributes['name'] : 'This is a tag';
        $this->url = isset($attributes['url']) ? $attributes['url'] : null;
    }

    private function isSponsored()
    {
        return false;
    }

    public function normalize(NormalizerInterface $normalizer, string $format = null, array $context = [])
    {
        if (Searchable::NORMALIZATION_FORMAT === $format) {
            return [
                'id' => $this->id,
                'name' => 'this test is correct',
                'url' => 'https://algolia.com',
            ];
        }
    }
}
