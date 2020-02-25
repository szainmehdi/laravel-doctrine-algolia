<?php

namespace Zain\LaravelDoctrine\Algolia\Fixtures\Normalizers;

use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Post;

class PostNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    private NormalizerInterface $normalizer;

    /**
     * @param Post $object
     *
     * @return array<string,mixed>
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        return [
            'id'    => $object->getId(),
            'title' => $object->getTitle(),
            'publishedAt' => $this->normalizer->normalize($object->getPublishedAt(), $format),
        ];
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Post;
    }

    public function setNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }
}
