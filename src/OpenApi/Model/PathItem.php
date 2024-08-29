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

final class PathItem
{
    use ExtensionTrait;

    public static array $methods = ['GET', 'PUT', 'POST', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH', 'TRACE'];

    public function __construct(private ?string $ref = null, private ?string $summary = null, private ?string $description = null, private ?Operation $get = null, private ?Operation $put = null, private ?Operation $post = null, private ?Operation $delete = null, private ?Operation $options = null, private ?Operation $head = null, private ?Operation $patch = null, private ?Operation $trace = null, private ?array $servers = null, private ?array $parameters = null)
    {
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getGet(): ?Operation
    {
        return $this->get;
    }

    public function getPut(): ?Operation
    {
        return $this->put;
    }

    public function getPost(): ?Operation
    {
        return $this->post;
    }

    public function getDelete(): ?Operation
    {
        return $this->delete;
    }

    public function getOptions(): ?Operation
    {
        return $this->options;
    }

    public function getHead(): ?Operation
    {
        return $this->head;
    }

    public function getPatch(): ?Operation
    {
        return $this->patch;
    }

    public function getTrace(): ?Operation
    {
        return $this->trace;
    }

    public function getServers(): ?array
    {
        return $this->servers;
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function withRef(string $ref): self
    {
        $clone = clone $this;
        $clone->ref = $ref;

        return $clone;
    }

    public function withSummary(string $summary): self
    {
        $clone = clone $this;
        $clone->summary = $summary;

        return $clone;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withGet(?Operation $get): self
    {
        $clone = clone $this;
        $clone->get = $get;

        return $clone;
    }

    public function withPut(?Operation $put): self
    {
        $clone = clone $this;
        $clone->put = $put;

        return $clone;
    }

    public function withPost(?Operation $post): self
    {
        $clone = clone $this;
        $clone->post = $post;

        return $clone;
    }

    public function withDelete(?Operation $delete): self
    {
        $clone = clone $this;
        $clone->delete = $delete;

        return $clone;
    }

    public function withOptions(Operation $options): self
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }

    public function withHead(Operation $head): self
    {
        $clone = clone $this;
        $clone->head = $head;

        return $clone;
    }

    public function withPatch(?Operation $patch): self
    {
        $clone = clone $this;
        $clone->patch = $patch;

        return $clone;
    }

    public function withTrace(Operation $trace): self
    {
        $clone = clone $this;
        $clone->trace = $trace;

        return $clone;
    }

    public function withServers(?array $servers = null): self
    {
        $clone = clone $this;
        $clone->servers = $servers;

        return $clone;
    }

    public function withParameters(?array $parameters = null): self
    {
        $clone = clone $this;
        $clone->parameters = $parameters;

        return $clone;
    }
}
