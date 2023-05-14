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

final class Server
{
    use ExtensionTrait;

    public function __construct(private string $url, private string $description = '', private ?\ArrayObject $variables = null)
    {
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVariables(): ?\ArrayObject
    {
        return $this->variables;
    }

    public function withUrl(string $url): self
    {
        $clone = clone $this;
        $clone->url = $url;

        return $clone;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withVariables(\ArrayObject $variables): self
    {
        $clone = clone $this;
        $clone->variables = $variables;

        return $clone;
    }
}
