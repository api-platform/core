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

namespace ApiPlatform\Toon\Serializer;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Normalizes an Hydra Entrypoint in the Toon Hydra format through composition.
 */
final class ToonHydraEntrypointNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public const FORMAT = 'hydra';

    public function __construct(
        private NormalizerInterface $decorated,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory
    ) {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return (\in_array($format, [self::FORMAT.'_toon', 'toon'], true) || 'text/ld+toon' === $format) && $this->decorated->supportsNormalization($data, self::FORMAT, $context);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return $this->decorated->normalize($object, self::FORMAT, $context);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->decorated->hasCacheableSupportsMethod();
    }

    /**
     * @return array<class-string|\string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return (\in_array($format, [self::FORMAT.'_toon', 'toon'], true) || 'text/ld+toon' === $format) ? $this->decorated->getSupportedTypes(self::FORMAT) : [];
    }
}
