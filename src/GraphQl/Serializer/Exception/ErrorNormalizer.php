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

namespace ApiPlatform\GraphQl\Serializer\Exception;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalize GraphQL error (fallback).
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ErrorNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return FormattedError::createFromException($object);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Error;
    }

    public function getSupportedTypes($format): array
    {
        return [
            Error::class => true,
        ];
    }
}
