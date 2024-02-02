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

namespace ApiPlatform\Validator\Exception;

use ApiPlatform\Metadata\Exception\RuntimeException;

/**
 * Thrown when a validation error occurs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidationException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, protected readonly ?string $errorTitle = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorTitle(): ?string
    {
        return $this->errorTitle;
    }
}
