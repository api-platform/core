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

namespace ApiPlatform\Metadata;

use ApiPlatform\OpenApi\Model\Header as OpenApiHeader;
use ApiPlatform\State\ResponseHeaderProviderInterface;

/**
 * Declares an HTTP response header for an operation. Used for OpenAPI documentation
 * and to set, replace or remove a response header at runtime.
 *
 * - A static value can be provided through the `$value` argument.
 * - A `$provider` (a callable or a service ID resolving to a
 *   {@see ResponseHeaderProviderInterface}) can compute the value at runtime.
 * - Returning `null` from the provider removes the header from the response (an empty
 *   header value is represented by an empty string).
 * - Without `$value` or `$provider`, the header is documented only and not set at runtime.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class ResponseHeader
{
    /**
     * @param array<string, mixed>|null                       $schema
     * @param ResponseHeaderProviderInterface|callable|array|string|null $provider
     * @param array<string, mixed>                            $extraProperties
     */
    public function __construct(
        private ?string $key = null,
        private ?array $schema = null,
        private ?string $description = null,
        private string|null $value = null,
        private mixed $provider = null,
        private ?bool $required = null,
        private ?bool $deprecated = null,
        private bool|OpenApiHeader|null $openapi = null,
        private array $extraProperties = [],
    ) {
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function withKey(string $key): self
    {
        $self = clone $this;
        $self->key = $key;

        return $self;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSchema(): ?array
    {
        return $this->schema;
    }

    /**
     * @param array<string, mixed> $schema
     */
    public function withSchema(array $schema): self
    {
        $self = clone $this;
        $self->schema = $schema;

        return $self;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(string $description): self
    {
        $self = clone $this;
        $self->description = $description;

        return $self;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function withValue(?string $value): self
    {
        $self = clone $this;
        $self->value = $value;

        return $self;
    }

    public function getProvider(): mixed
    {
        return $this->provider;
    }

    /**
     * @param ResponseHeaderProviderInterface|callable|array|string|null $provider
     */
    public function withProvider(mixed $provider): self
    {
        $self = clone $this;
        $self->provider = $provider;

        return $self;
    }

    public function getRequired(): ?bool
    {
        return $this->required;
    }

    public function withRequired(bool $required): self
    {
        $self = clone $this;
        $self->required = $required;

        return $self;
    }

    public function getDeprecated(): ?bool
    {
        return $this->deprecated;
    }

    public function withDeprecated(bool $deprecated): self
    {
        $self = clone $this;
        $self->deprecated = $deprecated;

        return $self;
    }

    public function getOpenApi(): bool|OpenApiHeader|null
    {
        return $this->openapi;
    }

    public function withOpenApi(bool|OpenApiHeader $openapi): self
    {
        $self = clone $this;
        $self->openapi = $openapi;

        return $self;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    /**
     * @param array<string, mixed> $extraProperties
     */
    public function withExtraProperties(array $extraProperties): self
    {
        $self = clone $this;
        $self->extraProperties = $extraProperties;

        return $self;
    }
}
