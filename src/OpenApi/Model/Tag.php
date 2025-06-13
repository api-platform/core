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

final class Tag
{
    use ExtensionTrait;

    public function __construct(private string $name, private ?string $description = null, private ?string $externalDocs = null)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function getExternalDocs(): ?string
    {
        return $this->externalDocs;
    }

    public function withExternalDocs(string $externalDocs): self
    {
        $clone = clone $this;
        $clone->externalDocs = $externalDocs;

        return $clone;
    }
}
