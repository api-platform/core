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

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface as LegacyConstraintViolationListAwareExceptionInterface;
use ApiPlatform\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalize validation exceptions.
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ValidationExceptionNormalizer implements NormalizerInterface
{
    public function __construct(private readonly array $exceptionToStatus = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $validationException = $object->getPrevious();
        if (!($validationException instanceof ConstraintViolationListAwareExceptionInterface || $validationException instanceof LegacyConstraintViolationListAwareExceptionInterface)) {
            throw new RuntimeException(sprintf('Object is not a "%s".', ConstraintViolationListAwareExceptionInterface::class));
        }

        $error = FormattedError::createFromException($object);
        $error['message'] = $validationException->getMessage();

        $exceptionClass = $validationException::class;
        $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

        foreach ($this->exceptionToStatus as $class => $status) {
            if (is_a($exceptionClass, $class, true)) {
                $statusCode = $status;

                break;
            }
        }
        $error['extensions']['status'] = $statusCode;
        // graphql-php < 15
        if (\defined(Error::class.'::CATEGORY_INTERNAL')) {
            $error['extensions']['category'] = 'user';
        }
        $error['extensions']['violations'] = [];

        foreach ($validationException->getConstraintViolationList() as $violation) {
            $error['extensions']['violations'][] = [
                'path' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $error;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Error && ($data->getPrevious() instanceof ConstraintViolationListAwareExceptionInterface);
    }

    public function getSupportedTypes($format): array
    {
        return [
            Error::class => false,
        ];
    }
}
