<?php

declare(strict_types=1);

namespace Zain\LaravelDoctrine\Algolia\Fixtures\Normalizers;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zain\LaravelDoctrine\Algolia\Fixtures\Entities\Image;

class ImageNormalizer implements NormalizerInterface
{
    public function normalize($object, string $format = null, array $context = [])
    {
        return null;
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Image;
    }
}
