<?php

namespace ApiPlatform\State\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;

final class ParameterNotSupportedException extends RuntimeException implements ProblemExceptionInterface
{
    public function __construct(private readonly string $parameter, string $message = "Parameter not supported", int $code = 0, \Throwable|null $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getType(): string
    {
        return '/error/400';
    }

    public function getTitle(): ?string
    {
        return $this->message;
    }

    public function getStatus(): ?int
    {
        return 400;
    }

    public function getDetail(): ?string
    {
        return sprintf('Parameter "%s" not supported', $this->parameter);
    }

    public function getInstance(): ?string
    {
        return $this->parameter;
    }
}
