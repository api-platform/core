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

use ApiPlatform\OpenApi;
use ApiPlatform\State\ProviderInterface;

abstract class Parameter
{
    /**
     * @param array<string, mixed>          $extraProperties
     * @param ProviderInterface|string|null $provider
     * @param FilterInterface|string|null   $filter
     */
    public function __construct(
        protected ?string $key = null,
        protected ?\ArrayObject $schema = null,
        protected ?OpenApi\Model\Parameter $openApi = null,
        protected mixed $provider = null,
        protected mixed $filter = null,
        protected array $extraProperties = [],
    ) {
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getSchema(): ?\ArrayObject
    {
        return $this->schema;
    }

    public function getOpenApi(): ?OpenApi\Model\Parameter
    {
        return $this->openApi;
    }

    public function getProvider(): mixed
    {
        return $this->provider;
    }

    public function getFilter(): mixed
    {
        return $this->filter;
    }

    public function getExtraProperties(): ?array
    {
        return $this->extraProperties;
    }

    public function withKey(?string $key): static
    {
        $self = clone $this;
        $self->key = $key;

        return $self;
    }

    /**
     * @param \ArrayObject<string,mixed> $schema
     */
    public function withSchema(\ArrayObject $schema): static
    {
        $self = clone $this;
        $self->schema = $schema;

        return $self;
    }

    public function withOpenApi(OpenApi\Model\Parameter $openApi): static
    {
        $self = clone $this;
        $self->openApi = $openApi;

        return $self;
    }

    public function withProvider(mixed $provider): static
    {
        $self = clone $this;
        $self->provider = $provider;

        return $self;
    }

    public function withFilter(mixed $filter): static
    {
        $self = clone $this;
        $self->filter = $filter;

        return $self;
    }

    /**
     * @param array<string, mixed> $extraProperties
     */
    public function withExtraProperties(array $extraProperties = []): static
    {
        $self = clone $this;
        $self->extraProperties = $extraProperties;

        return $self;
    }
}
