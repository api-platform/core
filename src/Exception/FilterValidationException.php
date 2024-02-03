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

namespace ApiPlatform\Exception;

use ApiPlatform\ParameterValidator\Exception\ValidationExceptionInterface;

/**
 * Filter validation exception.
 *
 * @author Julien DENIAU <julien.deniau@gmail.com>
 *
 * @deprecated use \ApiPlatform\Metadata\Exception\ValidationException instead
 */
final class FilterValidationException extends \Exception implements ValidationExceptionInterface, ExceptionInterface, \Stringable
{
    public function __construct(private readonly array $constraintViolationList, string $message = '', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message ?: $this->__toString(), $code, $previous);
    }

    public function __toString(): string
    {
        return implode("\n", $this->constraintViolationList);
    }
}
