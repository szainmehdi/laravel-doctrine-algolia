<?php

namespace Zain\LaravelDoctrine\Algolia\Fixtures\Normalizers;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Comment;

class CommentNormalizer implements NormalizerInterface
{
    public function normalize($object, string $format = null, array $context = [])
    {
        return [
            'content'    => $object->getContent(),
            'post_title' => $object->getPost()->getTitle(),
        ];
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Comment;
    }
}
