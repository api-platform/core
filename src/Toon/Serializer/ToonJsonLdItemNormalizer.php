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

use ApiPlatform\JsonLd\Serializer\ItemNormalizer as DecoratedItemNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Normalizes an JSON-LD Item in the Toon JSON-LD format through composition.
 */
final class ToonJsonLdItemNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public const FORMAT = 'jsonld';

    public function __construct(private NormalizerInterface & DenormalizerInterface $decorated)
    {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return (\in_array($format, [self::FORMAT.'_toon', 'toon'], true) || 'text/ld+toon' === $format) && $this->decorated->supportsNormalization($data, self::FORMAT, $context);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return $this->decorated->normalize($object, self::FORMAT, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return (\in_array($format, [self::FORMAT.'_toon', 'toon'], true) || 'text/ld+toon' === $format) && $this->decorated->supportsDenormalization($data, $type, self::FORMAT, $context);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (\in_array($format, [self::FORMAT.'_toon', 'toon'], true) || 'text/ld+toon' === $format) {
            $format = self::FORMAT;
        }

        return $this->decorated->denormalize($data, $type, $format, $context);
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
