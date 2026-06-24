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

final class SecurityScheme
{
    use ExtensionTrait;

    public function __construct(private ?string $type = null, private string $description = '', private ?string $name = null, private ?string $in = null, private ?string $scheme = null, private ?string $bearerFormat = null, private ?OAuthFlows $flows = null, private ?string $openIdConnectUrl = null, private ?string $oauth2MetadataUrl = null, private ?bool $deprecated = null)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getIn(): ?string
    {
        return $this->in;
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function getBearerFormat(): ?string
    {
        return $this->bearerFormat;
    }

    public function getFlows(): ?OAuthFlows
    {
        return $this->flows;
    }

    public function getOpenIdConnectUrl(): ?string
    {
        return $this->openIdConnectUrl;
    }

    public function getOauth2MetadataUrl(): ?string
    {
        return $this->oauth2MetadataUrl;
    }

    public function getDeprecated(): ?bool
    {
        return $this->deprecated;
    }

    public function withType(string $type): self
    {
        $clone = clone $this;
        $clone->type = $type;

        return $clone;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function withIn(string $in): self
    {
        $clone = clone $this;
        $clone->in = $in;

        return $clone;
    }

    public function withScheme(string $scheme): self
    {
        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    public function withBearerFormat(string $bearerFormat): self
    {
        $clone = clone $this;
        $clone->bearerFormat = $bearerFormat;

        return $clone;
    }

    public function withFlows(OAuthFlows $flows): self
    {
        $clone = clone $this;
        $clone->flows = $flows;

        return $clone;
    }

    public function withOpenIdConnectUrl(string $openIdConnectUrl): self
    {
        $clone = clone $this;
        $clone->openIdConnectUrl = $openIdConnectUrl;

        return $clone;
    }

    public function withOauth2MetadataUrl(string $oauth2MetadataUrl): self
    {
        $clone = clone $this;
        $clone->oauth2MetadataUrl = $oauth2MetadataUrl;

        return $clone;
    }

    public function withDeprecated(bool $deprecated): self
    {
        $clone = clone $this;
        $clone->deprecated = $deprecated;

        return $clone;
    }
}
