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

namespace ApiPlatform\Symfony\Validator\Exception;

use ApiPlatform\Validator\Exception\ValidationException as BaseValidationException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Thrown when a validation error occurs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidationException extends BaseValidationException implements ConstraintViolationListAwareExceptionInterface, \Stringable
{
    public function __construct(private readonly ConstraintViolationListInterface $constraintViolationList, string $message = '', int $code = 0, \Throwable $previous = null, string $errorTitle = null)
    {
        parent::__construct($message ?: $this->__toString(), $code, $previous, $errorTitle);
    }

    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->constraintViolationList;
    }

    public function __toString(): string
    {
        $message = '';
        foreach ($this->constraintViolationList as $violation) {
            if ('' !== $message) {
                $message .= "\n";
            }
            if ($propertyPath = $violation->getPropertyPath()) {
                $message .= "$propertyPath: ";
            }

            $message .= $violation->getMessage();
        }

        return $message;
    }
}
