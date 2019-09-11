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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\GraphQl\ExceptionFormatter;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\GraphQl\Exception\ExceptionFormatterInterface;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Formats Validation exception.
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
class ValidationExceptionFormatter implements ExceptionFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(Error $error): array
    {
        /**
         * @var ValidationException
         */
        $validationException = $error->getPrevious();
        $error = FormattedError::createFromException($error);
        $error['message'] = 'Validation Exception';
        $error['status'] = Response::HTTP_BAD_REQUEST;
        $error['extensions']['category'] = Error::CATEGORY_GRAPHQL;
        $error['violations'] = [];

        /** @var ConstraintViolation $violation */
        foreach ($validationException->getConstraintViolationList() as $violation) {
            $error['violations'][] = [
                'path' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $error;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ValidationException;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return 1;
    }
}
