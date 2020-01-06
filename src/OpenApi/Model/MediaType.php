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

class MediaType
{
    use ExtensionTrait;

    private $schema;
    private $example;
    private $examples;
    private $encoding;

    public function __construct(array $schema = [], $example = null, array $examples = [], array $encoding = [])
    {
        $this->schema = $schema;
        $this->example = $example;
        $this->examples = $examples;
        $this->encoding = $encoding;
    }

    public function getSchema(): array
    {
        return $this->schema;
    }

    public function getExample()
    {
        return $this->example;
    }

    public function getExamples(): array
    {
        return $this->examples;
    }

    public function getEncoding(): array
    {
        return $this->encoding;
    }

    public function withSchema(array $schema): self
    {
        $clone = clone $this;
        $clone->schema = $schema;

        return $clone;
    }

    public function withExample()
    {
        $clone = clone $this;
        $clone->example = $example;

        return $clone;
    }

    public function withExamples(array $examples): self
    {
        $clone = clone $this;
        $clone->examples = $examples;

        return $clone;
    }

    public function withEncoding(array $encoding): self
    {
        $clone = clone $this;
        $clone->encoding = $encoding;

        return $clone;
    }
}
