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

namespace ApiPlatform\ParameterValidator\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;

/**
 * Filter validation exception.
 *
 * @deprecated use \ApiPlatform\Metadata\Parameter::$constraints instead
 *
 * @author Julien DENIAU <julien.deniau@gmail.com>
 */
final class ValidationException extends \Exception implements ValidationExceptionInterface, ProblemExceptionInterface
{
    /**
     * @param string[] $constraintViolationList
     */
    public function __construct(private readonly array $constraintViolationList, string $message = '', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message ?: $this->__toString(), $code, $previous);

        trigger_deprecation('api-platform/core', '3.4', 'The class "%s" is deprecated, use "\ApiPlatform\Metadata\Parameter::$constraints" instead.', __CLASS__);
    }

    public function getConstraintViolationList(): array
    {
        return $this->constraintViolationList;
    }

    public function __toString(): string
    {
        return implode("\n", $this->constraintViolationList);
    }

    public function getType(): string
    {
        return '/parameter_validation/'.$this->code;
    }

    public function getTitle(): ?string
    {
        return $this->message ?: $this->__toString();
    }

    public function getStatus(): ?int
    {
        return 400;
    }

    public function getDetail(): ?string
    {
        return $this->message ?: $this->__toString();
    }

    public function getInstance(): ?string
    {
        return null;
    }
}
