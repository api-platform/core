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

use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ParameterProviderInterface;
use Symfony\Component\TypeInfo\Type;

abstract class Parameter
{
    /**
     * @param (array<string, mixed>&array{type?: string, default?: mixed})|null $schema
     * @param array<string, mixed>                                              $extraProperties
     * @param ParameterProviderInterface|callable|string|null                   $provider
     * @param list<string>                                                      $properties       a list of properties this parameter applies to (works with the :property placeholder)
     * @param FilterInterface|string|null                                       $filter
     * @param mixed                                                             $constraints      an array of Symfony constraints, or an array of Laravel rules
     * @param Type                                                              $nativeType       the PHP native type, we cast values to an array if its a CollectionType, if not and it's an array with a single value we use it (eg: HTTP Header)
     * @param ?bool                                                             $castToNativeType whether API Platform should cast your parameter to the nativeType declared
     * @param ?callable(mixed): mixed                                           $castFn           the closure used to cast your parameter, this gets called only when $castToNativeType is set
     */
    public function __construct(
        protected ?string $key = null,
        protected ?array $schema = null,
        protected OpenApiParameter|array|false|null $openApi = null,
        protected mixed $provider = null,
        protected mixed $filter = null,
        protected ?string $property = null,
        protected ?string $description = null,
        protected ?array $properties = null,
        protected ?bool $required = null,
        protected ?int $priority = null,
        protected ?false $hydra = null,
        protected mixed $constraints = null,
        protected string|\Stringable|null $security = null,
        protected ?string $securityMessage = null,
        protected ?array $extraProperties = [],
        protected array|string|null $filterContext = null,
        protected ?Type $nativeType = null,
        protected ?bool $castToArray = null,
        protected ?bool $castToNativeType = null,
        protected mixed $castFn = null,
    ) {
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @return (array<string, mixed>&array{type?: string, default?: mixed})|null $schema
     */
    public function getSchema(): ?array
    {
        return $this->schema;
    }

    /**
     * @return OpenApiParameter[]|OpenApiParameter|bool|null
     */
    public function getOpenApi(): OpenApiParameter|array|bool|null
    {
        return $this->openApi;
    }

    public function getProvider(): mixed
    {
        return $this->provider;
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }

    public function getFilter(): mixed
    {
        return $this->filter;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getRequired(): ?bool
    {
        return $this->required;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function getHydra(): ?bool
    {
        return $this->hydra;
    }

    public function getConstraints(): mixed
    {
        return $this->constraints;
    }

    public function getSecurity(): string|\Stringable|null
    {
        return $this->security;
    }

    public function getSecurityMessage(): ?string
    {
        return $this->securityMessage;
    }

    /**
     * The computed value of this parameter, located into extraProperties['_api_values'].
     */
    public function getValue(mixed $default = new ParameterNotFound()): mixed
    {
        return $this->extraProperties['_api_values'] ?? $default;
    }

    /**
     * Only use this in a parameter provider, the ApiPlatform\State\Provider\ParameterProvider
     * resets this value to extract the correct value on each request.
     * It's also possible to set the `_api_query_parameters` request attribute directly and
     * API Platform will extract the value from there.
     */
    public function setValue(mixed $value): static
    {
        $this->extraProperties['_api_values'] = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    public function getFilterContext(): array|string|null
    {
        return $this->filterContext;
    }

    public function withKey(string $key): static
    {
        $self = clone $this;
        $self->key = $key;

        return $self;
    }

    public function withPriority(int $priority): static
    {
        $self = clone $this;
        $self->priority = $priority;

        return $self;
    }

    /**
     * @param array{type?: string} $schema
     */
    public function withSchema(array $schema): static
    {
        $self = clone $this;
        $self->schema = $schema;

        return $self;
    }

    /**
     * @param OpenApiParameter[]|OpenApiParameter|bool $openApi
     */
    public function withOpenApi(OpenApiParameter|array|bool $openApi): static
    {
        $self = clone $this;
        $self->openApi = $openApi;

        return $self;
    }

    /**
     * @param ParameterProviderInterface|string $provider
     */
    public function withProvider(mixed $provider): static
    {
        $self = clone $this;
        $self->provider = $provider;

        return $self;
    }

    /**
     * @param FilterInterface|string $filter
     */
    public function withFilter(mixed $filter): static
    {
        $self = clone $this;
        $self->filter = $filter;

        return $self;
    }

    public function withFilterContext(array|string $filterContext): static
    {
        $self = clone $this;
        $self->filterContext = $filterContext;

        return $self;
    }

    public function withProperty(string $property): static
    {
        $self = clone $this;
        $self->property = $property;

        return $self;
    }

    public function withDescription(string $description): static
    {
        $self = clone $this;
        $self->description = $description;

        return $self;
    }

    public function withRequired(bool $required): static
    {
        $self = clone $this;
        $self->required = $required;

        return $self;
    }

    public function withHydra(false $hydra): static
    {
        $self = clone $this;
        $self->hydra = $hydra;

        return $self;
    }

    public function withConstraints(mixed $constraints): static
    {
        $self = clone $this;
        $self->constraints = $constraints;

        return $self;
    }

    public function withSecurity(string|\Stringable|null $security): static
    {
        $self = clone $this;
        $self->security = $security;

        return $self;
    }

    public function withSecurityMessage(?string $securityMessage): static
    {
        $self = clone $this;
        $self->securityMessage = $securityMessage;

        return $self;
    }

    /**
     * @param array<string, mixed> $extraProperties
     */
    public function withExtraProperties(array $extraProperties): static
    {
        $self = clone $this;
        $self->extraProperties = $extraProperties;

        return $self;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function withProperties(?array $properties): self
    {
        $self = clone $this;
        $self->properties = $properties;

        return $self;
    }

    public function getNativeType(): ?Type
    {
        return $this->nativeType;
    }

    public function withNativeType(Type $nativeType): self
    {
        $self = clone $this;
        $self->nativeType = $nativeType;

        return $self;
    }

    public function getCastToArray(): ?bool
    {
        return $this->castToArray;
    }

    public function withCastToArray(bool $castToArray): self
    {
        $self = clone $this;
        $self->castToArray = $castToArray;

        return $self;
    }

    public function getCastToNativeType(): ?bool
    {
        return $this->castToNativeType;
    }

    public function withCastToNativeType(bool $castToNativeType): self
    {
        $self = clone $this;
        $self->castToNativeType = $castToNativeType;

        return $self;
    }

    public function getCastFn(): ?callable
    {
        return $this->castFn;
    }

    /**
     * @param callable(mixed): mixed $castFn
     */
    public function withCastFn(mixed $castFn): self
    {
        $self = clone $this;
        $self->castFn = $castFn;

        return $self;
    }
}
