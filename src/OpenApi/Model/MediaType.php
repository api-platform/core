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

final class MediaType
{
    use ExtensionTrait;

    public function __construct(private ?\ArrayObject $schema = null, private $example = null, private ?\ArrayObject $examples = null, private ?Encoding $encoding = null)
    {
    }

    public function getSchema(): ?\ArrayObject
    {
        return $this->schema;
    }

    public function getExample()
    {
        return $this->example;
    }

    public function getExamples(): ?\ArrayObject
    {
        return $this->examples;
    }

    public function getEncoding(): ?Encoding
    {
        return $this->encoding;
    }

    public function withSchema(\ArrayObject $schema): self
    {
        $clone = clone $this;
        $clone->schema = $schema;

        return $clone;
    }

    public function withExample($example): self
    {
        $clone = clone $this;
        $clone->example = $example;

        return $clone;
    }

    public function withExamples(\ArrayObject $examples): self
    {
        $clone = clone $this;
        $clone->examples = $examples;

        return $clone;
    }

    public function withEncoding(Encoding $encoding): self
    {
        $clone = clone $this;
        $clone->encoding = $encoding;

        return $clone;
    }
}
