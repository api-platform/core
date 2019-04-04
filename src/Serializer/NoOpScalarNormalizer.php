<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Serializer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * A no-op normalizer that passes through scalar values.
 *
 * When there are non-cacheable normalizers in use, and you don't need to normalize
 * scalar values, register this normalizer with a higher priority than the non-cacheable
 * normalizers. This allows caching supportsNormalization calls for scalar values.
 *
 * @internal
 */
final class NoOpScalarNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return is_scalar($data);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
