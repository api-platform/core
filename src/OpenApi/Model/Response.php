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

namespace ApiPlatform\Core\OpenApi\Model;

class Response
{
    use ExtensionTrait;

    private $description;
    private $content;
    private $headers;
    private $links;

    public function __construct(string $description = '', array $content = [], array $headers = [], array $links = [])
    {
        $this->description = $description;
        $this->content = $content;
        $this->headers = $headers;
        $this->links = $links;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getLinks(): array
    {
        return $this->links;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withContent(array $content): self
    {
        $clone = clone $this;
        $clone->content = $content;

        return $clone;
    }

    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = $headers;

        return $clone;
    }

    public function withLinks(array $links): self
    {
        $clone = clone $this;
        $clone->links = $links;

        return $clone;
    }
}
