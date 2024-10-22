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

namespace ApiPlatform\JsonLd\Serializer;

use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ErrorNormalizer implements NormalizerInterface
{
    use HydraPrefixTrait;

    public function __construct(private readonly NormalizerInterface $inner, private readonly array $defaultContext = [])
    {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context += $this->defaultContext;
        $normalized = $this->inner->normalize($object, $format, $context);
        $hydraPrefix = $this->getHydraPrefix($context);
        if (!$hydraPrefix) {
            return $normalized;
        }

        if ('Error' === $normalized['@type']) {
            $normalized['@type'] = 'hydra:Error';
        }

        if (isset($normalized['description'])) {
            $normalized['hydra:description'] = $normalized['description'];
        }

        if (isset($normalized['title'])) {
            $normalized['hydra:title'] = $normalized['title'];
        }

        return $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->inner->supportsNormalization($data, $format, $context)
            && (is_a($data, Error::class) || is_a($data, ValidationException::class));
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->inner->getSupportedTypes($format);
    }
}
