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

final class Response
{
    use ExtensionTrait;

    public function __construct(private string $description = '', private ?\ArrayObject $content = null, private ?\ArrayObject $headers = null, private ?\ArrayObject $links = null)
    {
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getContent(): ?\ArrayObject
    {
        return $this->content;
    }

    public function getHeaders(): ?\ArrayObject
    {
        return $this->headers;
    }

    public function getLinks(): ?\ArrayObject
    {
        return $this->links;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withContent(\ArrayObject $content): self
    {
        $clone = clone $this;
        $clone->content = $content;

        return $clone;
    }

    public function withHeaders(\ArrayObject $headers): self
    {
        $clone = clone $this;
        $clone->headers = $headers;

        return $clone;
    }

    public function withLinks(\ArrayObject $links): self
    {
        $clone = clone $this;
        $clone->links = $links;

        return $clone;
    }
}
