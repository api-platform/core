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
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalize HTTP exceptions.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class HttpExceptionNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        /** @var HttpExceptionInterface */
        $httpException = $object->getPrevious();
        $error = FormattedError::createFromException($object);
        $error['message'] = $httpException->getMessage();
        $error['extensions']['status'] = $statusCode = $httpException->getStatusCode();
        // graphql-php < 15
        if (\defined(Error::class.'::CATEGORY_INTERNAL')) {
            $error['extensions']['category'] = $statusCode < 500 ? 'user' : Error::CATEGORY_INTERNAL;
        }

        return $error;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Error && $data->getPrevious() instanceof HttpExceptionInterface;
    }

    public function getSupportedTypes($format): array
    {
        return [
            Error::class => false,
        ];
    }
}
