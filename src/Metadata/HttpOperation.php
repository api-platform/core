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

use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\State\OptionsInterface;

class HttpOperation extends Operation
{
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_OPTIONS = 'OPTIONS';

    /**
     * @param string[]|null                              $types         the RDF types of this property
     * @param array<string, string|string[]>|string|null $formats       {@see https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation}
     * @param array<string, string|string[]>|string|null $inputFormats  {@see https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation}
     * @param array<string, string|string[]>|string|null $outputFormats {@see https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation}
     * @param array<string,array{
     *     0: string,
     *     1: string
     * }|array{
     *     from_property?: string,
     *     to_property?: string,
     *     from_class?: string,
     *     to_class?: string,
     *     identifiers?: string[],
     *     composite_identifier?: bool,
     *     expanded_value?: string,
     * }|Link>|string[]|string|null $uriVariables {@see https://api-platform.com/docs/core/subresources/}
     * @param string|null     $routePrefix {@see https://api-platform.com/docs/core/operations/#prefixing-all-routes-of-all-operations}
     * @param string|null     $sunset      {@see https://api-platform.com/docs/core/deprecations/#setting-the-sunset-http-header-to-indicate-when-a-resource-or-an-operation-will-be-removed}
     * @param string|int|null $status      {@see https://api-platform.com/docs/core/operations/#configuring-operations}
     * @param array{
     *     max_age?: int,
     *     vary?: string|string[],
     *     public?: bool,
     *     shared_max_age?: int,
     *     stale_while_revalidate?: int,
     *     stale-if-error?: int,
     * }|null $cacheHeaders {@see https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers}
     * @param array{
     *     field: string,
     *     direction: string,
     * }|null $paginationViaCursor {@see https://api-platform.com/docs/core/pagination/#cursor-based-pagination}
     * @param array|null $normalizationContext   {@see https://api-platform.com/docs/core/serialization/#using-serialization-groups}
     * @param array|null $denormalizationContext {@see https://api-platform.com/docs/core/serialization/#using-serialization-groups}
     * @param array|null $hydraContext           {@see https://api-platform.com/docs/core/extending-jsonld-context/#hydra}
     * @param array|null $openapiContext         {@see https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts}
     * @param array|null $translation            {@see https://api-platform.com/docs/core/translation}
     * @param array{
     *     class?: string|null,
     *     name?: string,
     * }|string|false|null $input {@see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation}
     * @param array{
     *     class?: string|null,
     *     name?: string,
     * }|string|false|null $output {@see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation}
     * @param string|array|bool|null $mercure   {@see https://api-platform.com/docs/core/mercure}
     * @param string|bool|null       $messenger {@see https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus}
     * @param string|callable|null   $provider  {@see https://api-platform.com/docs/core/state-providers/#state-providers}
     * @param string|callable|null   $processor {@see https://api-platform.com/docs/core/state-processors/#state-processors}
     */
    public function __construct(
        protected string $method = self::METHOD_GET,
        protected ?string $uriTemplate = null,
        protected ?array $types = null,
        protected $formats = null,
        protected $inputFormats = null,
        protected $outputFormats = null,
        protected $uriVariables = null,
        protected ?string $routePrefix = null,
        protected ?string $routeName = null,
        protected ?array $defaults = null,
        protected ?array $requirements = null,
        protected ?array $options = null,
        protected ?bool $stateless = null,
        protected ?string $sunset = null,
        protected ?string $acceptPatch = null,
        protected $status = null,
        protected ?string $host = null,
        protected ?array $schemes = null,
        protected ?string $condition = null,
        protected ?string $controller = null,
        protected ?array $cacheHeaders = null,
        protected ?array $paginationViaCursor = null,
        protected ?array $hydraContext = null,
        protected ?array $openapiContext = null, // TODO Remove in 4.0
        protected bool|OpenApiOperation|null $openapi = null,
        protected ?array $exceptionToStatus = null,
        protected ?bool $queryParameterValidationEnabled = null,

        ?string $shortName = null,
        ?string $class = null,
        ?bool $paginationEnabled = null,
        ?string $paginationType = null,
        ?int $paginationItemsPerPage = null,
        ?int $paginationMaximumItemsPerPage = null,
        ?bool $paginationPartial = null,
        ?bool $paginationClientEnabled = null,
        ?bool $paginationClientItemsPerPage = null,
        ?bool $paginationClientPartial = null,
        ?bool $paginationFetchJoinCollection = null,
        ?bool $paginationUseOutputWalkers = null,
        ?array $order = null,
        ?string $description = null,
        ?array $normalizationContext = null,
        ?array $denormalizationContext = null,
        ?bool $collectDenormalizationErrors = null,
        ?string $security = null,
        ?string $securityMessage = null,
        ?string $securityPostDenormalize = null,
        ?string $securityPostDenormalizeMessage = null,
        ?string $securityPostValidation = null,
        ?string $securityPostValidationMessage = null,
        ?array $translation = null,
        ?string $deprecationReason = null,
        ?array $filters = null,
        ?array $validationContext = null,
        $input = null,
        $output = null,
        $mercure = null,
        $messenger = null,
        ?bool $elasticsearch = null,
        ?int $urlGenerationStrategy = null,
        ?bool $read = null,
        ?bool $deserialize = null,
        ?bool $validate = null,
        ?bool $write = null,
        ?bool $serialize = null,
        ?bool $fetchPartial = null,
        ?bool $forceEager = null,
        ?int $priority = null,
        ?string $name = null,
        $provider = null,
        $processor = null,
        ?OptionsInterface $stateOptions = null,
        array $extraProperties = [],
    ) {
        parent::__construct(
            shortName: $shortName,
            class: $class,
            paginationEnabled: $paginationEnabled,
            paginationType: $paginationType,
            paginationItemsPerPage: $paginationItemsPerPage,
            paginationMaximumItemsPerPage: $paginationMaximumItemsPerPage,
            paginationPartial: $paginationPartial,
            paginationClientEnabled: $paginationClientEnabled,
            paginationClientItemsPerPage: $paginationClientItemsPerPage,
            paginationClientPartial: $paginationClientPartial,
            paginationFetchJoinCollection: $paginationFetchJoinCollection,
            paginationUseOutputWalkers: $paginationUseOutputWalkers,
            order: $order,
            description: $description,
            normalizationContext: $normalizationContext,
            denormalizationContext: $denormalizationContext,
            collectDenormalizationErrors: $collectDenormalizationErrors,
            security: $security,
            securityMessage: $securityMessage,
            securityPostDenormalize: $securityPostDenormalize,
            securityPostDenormalizeMessage: $securityPostDenormalizeMessage,
            securityPostValidation: $securityPostValidation,
            securityPostValidationMessage: $securityPostValidationMessage,
            translation: $translation,
            deprecationReason: $deprecationReason,
            filters: $filters,
            validationContext: $validationContext,
            input: $input,
            output: $output,
            mercure: $mercure,
            messenger: $messenger,
            elasticsearch: $elasticsearch,
            urlGenerationStrategy: $urlGenerationStrategy,
            read: $read,
            deserialize: $deserialize,
            validate: $validate,
            write: $write,
            serialize: $serialize,
            fetchPartial: $fetchPartial,
            forceEager: $forceEager,
            priority: $priority,
            name: $name,
            provider: $provider,
            processor: $processor,
            stateOptions: $stateOptions,
            extraProperties: $extraProperties
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): self
    {
        $self = clone $this;
        $self->method = $method;

        return $self;
    }

    public function getUriTemplate(): ?string
    {
        return $this->uriTemplate;
    }

    public function withUriTemplate(?string $uriTemplate = null)
    {
        $self = clone $this;
        $self->uriTemplate = $uriTemplate;

        return $self;
    }

    public function getTypes(): ?array
    {
        return $this->types;
    }

    /**
     * @param string[]|string $types
     */
    public function withTypes($types): self
    {
        $self = clone $this;
        $self->types = (array) $types;

        return $self;
    }

    public function getFormats()
    {
        return $this->formats;
    }

    public function withFormats($formats = null): self
    {
        $self = clone $this;
        $self->formats = $formats;

        return $self;
    }

    public function getInputFormats()
    {
        return $this->inputFormats;
    }

    public function withInputFormats($inputFormats = null): self
    {
        $self = clone $this;
        $self->inputFormats = $inputFormats;

        return $self;
    }

    public function getOutputFormats()
    {
        return $this->outputFormats;
    }

    public function withOutputFormats($outputFormats = null): self
    {
        $self = clone $this;
        $self->outputFormats = $outputFormats;

        return $self;
    }

    public function getUriVariables()
    {
        return $this->uriVariables;
    }

    public function withUriVariables($uriVariables): self
    {
        $self = clone $this;
        $self->uriVariables = $uriVariables;

        return $self;
    }

    public function getRoutePrefix(): ?string
    {
        return $this->routePrefix;
    }

    public function withRoutePrefix(string $routePrefix): self
    {
        $self = clone $this;
        $self->routePrefix = $routePrefix;

        return $self;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function withRouteName(?string $routeName): self
    {
        $self = clone $this;
        $self->routeName = $routeName;

        return $self;
    }

    public function getDefaults(): ?array
    {
        return $this->defaults;
    }

    public function withDefaults(array $defaults): self
    {
        $self = clone $this;
        $self->defaults = $defaults;

        return $self;
    }

    public function getRequirements(): ?array
    {
        return $this->requirements;
    }

    public function withRequirements(array $requirements): self
    {
        $self = clone $this;
        $self->requirements = $requirements;

        return $self;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function withOptions(array $options): self
    {
        $self = clone $this;
        $self->options = $options;

        return $self;
    }

    public function getStateless(): ?bool
    {
        return $this->stateless;
    }

    public function withStateless($stateless): self
    {
        $self = clone $this;
        $self->stateless = $stateless;

        return $self;
    }

    public function getSunset(): ?string
    {
        return $this->sunset;
    }

    public function withSunset(string $sunset): self
    {
        $self = clone $this;
        $self->sunset = $sunset;

        return $self;
    }

    public function getAcceptPatch(): ?string
    {
        return $this->acceptPatch;
    }

    public function withAcceptPatch(string $acceptPatch): self
    {
        $self = clone $this;
        $self->acceptPatch = $acceptPatch;

        return $self;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function withStatus(int $status): self
    {
        $self = clone $this;
        $self->status = $status;

        return $self;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function withHost(string $host): self
    {
        $self = clone $this;
        $self->host = $host;

        return $self;
    }

    public function getSchemes(): ?array
    {
        return $this->schemes;
    }

    public function withSchemes(array $schemes): self
    {
        $self = clone $this;
        $self->schemes = $schemes;

        return $self;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function withCondition(string $condition): self
    {
        $self = clone $this;
        $self->condition = $condition;

        return $self;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function withController(string $controller): self
    {
        $self = clone $this;
        $self->controller = $controller;

        return $self;
    }

    public function getCacheHeaders(): ?array
    {
        return $this->cacheHeaders;
    }

    public function withCacheHeaders(array $cacheHeaders): self
    {
        $self = clone $this;
        $self->cacheHeaders = $cacheHeaders;

        return $self;
    }

    public function getPaginationViaCursor(): ?array
    {
        return $this->paginationViaCursor;
    }

    public function withPaginationViaCursor(array $paginationViaCursor): self
    {
        $self = clone $this;
        $self->paginationViaCursor = $paginationViaCursor;

        return $self;
    }

    public function getHydraContext(): ?array
    {
        return $this->hydraContext;
    }

    public function withHydraContext(array $hydraContext): self
    {
        $self = clone $this;
        $self->hydraContext = $hydraContext;

        return $self;
    }

    public function getOpenapiContext(): ?array
    {
        return $this->openapiContext;
    }

    public function withOpenapiContext(array $openapiContext): self
    {
        $self = clone $this;
        $self->openapiContext = $openapiContext;

        return $self;
    }

    public function getOpenapi(): bool|OpenApiOperation|null
    {
        return $this->openapi;
    }

    public function withOpenapi(bool|OpenApiOperation $openapi): self
    {
        $self = clone $this;
        $self->openapi = $openapi;

        return $self;
    }

    public function getExceptionToStatus(): ?array
    {
        return $this->exceptionToStatus;
    }

    public function withExceptionToStatus(array $exceptionToStatus): self
    {
        $self = clone $this;
        $self->exceptionToStatus = $exceptionToStatus;

        return $self;
    }

    public function getQueryParameterValidationEnabled(): ?bool
    {
        return $this->queryParameterValidationEnabled;
    }

    public function withQueryParameterValidationEnabled(bool $queryParameterValidationEnabled): self
    {
        $self = clone $this;
        $self->queryParameterValidationEnabled = $queryParameterValidationEnabled;

        return $self;
    }
}
