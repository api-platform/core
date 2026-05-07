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

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Normalizes an JSON:API Entrypoint in the Toon JSON:API format through composition.
 */
final class ToonJsonApiEntrypointNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public const FORMAT = 'jsonapi';

    public function __construct(private NormalizerInterface $decorated)
    {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return (\in_array($format, [self::FORMAT.'_toon', 'toon'], true) || 'text/vnd.api+toon' === $format) && $this->decorated->supportsNormalization($data, self::FORMAT, $context);
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
        return (\in_array($format, [self::FORMAT.'_toon', 'toon'], true) || 'text/vnd.api+toon' === $format) ? $this->decorated->getSupportedTypes(self::FORMAT) : [];
    }
}
