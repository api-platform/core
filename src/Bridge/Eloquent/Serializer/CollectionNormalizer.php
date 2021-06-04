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

namespace ApiPlatform\Core\Bridge\Eloquent\Serializer;

use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Collection normalizer for non-mapped data model Eloquent resources.
 * It exists to be called before the JsonSerializableNormalizer.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class CollectionNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $collectionNormalizer;

    public function __construct(NormalizerInterface $collectionNormalizer)
    {
        $this->collectionNormalizer = $collectionNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Collection && $this->collectionNormalizer->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return $this->collectionNormalizer instanceof CacheableSupportsMethodInterface && $this->collectionNormalizer->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->collectionNormalizer->normalize($object, $format, $context);
    }
}
