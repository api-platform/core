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

final class Encoding
{
    use ExtensionTrait;

    /**
     * @param array<int, Encoding>|null $prefixEncoding
     */
    public function __construct(private string $contentType = '', private ?\ArrayObject $headers = null, private string $style = '', private bool $explode = false, private bool $allowReserved = false, private ?\ArrayObject $encoding = null, private ?array $prefixEncoding = null, private ?self $itemEncoding = null)
    {
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getHeaders(): ?\ArrayObject
    {
        return $this->headers;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function canExplode(): bool
    {
        return $this->explode;
    }

    public function getExplode(): bool
    {
        return $this->explode;
    }

    public function canAllowReserved(): bool
    {
        return $this->allowReserved;
    }

    public function getAllowReserved(): bool
    {
        return $this->allowReserved;
    }

    public function getEncoding(): ?\ArrayObject
    {
        return $this->encoding;
    }

    /**
     * @return array<int, Encoding>|null
     */
    public function getPrefixEncoding(): ?array
    {
        return $this->prefixEncoding;
    }

    public function getItemEncoding(): ?self
    {
        return $this->itemEncoding;
    }

    public function withContentType(string $contentType): self
    {
        $clone = clone $this;
        $clone->contentType = $contentType;

        return $clone;
    }

    public function withHeaders(?\ArrayObject $headers): self
    {
        $clone = clone $this;
        $clone->headers = $headers;

        return $clone;
    }

    public function withStyle(string $style): self
    {
        $clone = clone $this;
        $clone->style = $style;

        return $clone;
    }

    public function withExplode(bool $explode): self
    {
        $clone = clone $this;
        $clone->explode = $explode;

        return $clone;
    }

    public function withAllowReserved(bool $allowReserved): self
    {
        $clone = clone $this;
        $clone->allowReserved = $allowReserved;

        return $clone;
    }

    public function withEncoding(?\ArrayObject $encoding): self
    {
        $clone = clone $this;
        $clone->encoding = $encoding;

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

    public function withItemEncoding(self $itemEncoding): self
    {
        $clone = clone $this;
        $clone->itemEncoding = $itemEncoding;

        return $clone;
    }
}
