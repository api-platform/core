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

    /**
     * @param array<int, Encoding>|null $prefixEncoding
     */
    public function __construct(private ?\ArrayObject $schema = null, private mixed $example = null, private ?\ArrayObject $examples = null, private ?Encoding $encoding = null, private ?\ArrayObject $itemSchema = null, private ?array $prefixEncoding = null, private ?Encoding $itemEncoding = null)
    {
    }

    public function getSchema(): ?\ArrayObject
    {
        return $this->schema;
    }

    public function getExample(): mixed
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

    public function getItemSchema(): ?\ArrayObject
    {
        return $this->itemSchema;
    }

    /**
     * @return array<int, Encoding>|null
     */
    public function getPrefixEncoding(): ?array
    {
        return $this->prefixEncoding;
    }

    public function getItemEncoding(): ?Encoding
    {
        return $this->itemEncoding;
    }

    public function withSchema(\ArrayObject $schema): self
    {
        $clone = clone $this;
        $clone->schema = $schema;

        return $clone;
    }

    public function withExample(mixed $example): self
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

    public function withItemSchema(\ArrayObject $itemSchema): self
    {
        $clone = clone $this;
        $clone->itemSchema = $itemSchema;

        return $clone;
    }

    /**
     * @param array<int, Encoding>|null $prefixEncoding
     */
    public function withPrefixEncoding(?array $prefixEncoding): self
    {
        $clone = clone $this;
        $clone->prefixEncoding = $prefixEncoding;

        return $clone;
    }

    public function withItemEncoding(Encoding $itemEncoding): self
    {
        $clone = clone $this;
        $clone->itemEncoding = $itemEncoding;

        return $clone;
    }
}
