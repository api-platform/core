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

namespace ApiPlatform\OpenApi\Model;

final class Example
{
    use ExtensionTrait;

    public function __construct(private ?string $summary = null, private ?string $description = null, private mixed $value = null, private ?string $externalValue = null)
    {
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function withSummary(string $summary): self
    {
        $clone = clone $this;
        $clone->summary = $summary;

        return $clone;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function withValue(mixed $value): self
    {
        $clone = clone $this;
        $clone->value = $value;

        return $clone;
    }

    public function getExternalValue(): ?string
    {
        return $this->externalValue;
    }

    public function withExternalValue(string $externalValue): self
    {
        $clone = clone $this;
        $clone->externalValue = $externalValue;

        return $clone;
    }
}
