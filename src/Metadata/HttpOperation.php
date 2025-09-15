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

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\OpenApi\Attributes\Webhook;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\State\OptionsInterface;
use Symfony\Component\WebLink\Link as WebLink;

class HttpOperation extends Operation
{
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_OPTIONS = 'OPTIONS';

    /** @var array<int|string, string|string[]>|null */
    protected $formats;
    /** @var array<int|string, string|string[]>|null */
    protected $inputFormats;
    /** @var array<int|string, string|string[]>|null */
    protected $outputFormats;

    /**
     * @param string[]|null                                  $types         the RDF types of this property
     * @param array<int|string, string|string[]>|string|null $formats       {@see https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation}
     * @param array<int|string, string|string[]>|string|null $inputFormats  {@see https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation}
     * @param array<int|string, string|string[]>|string|null $outputFormats {@see https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation}
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
     *     stale_if_error?: int,
     *     must_revalidate?: bool,
     *     proxy_revalidate?: bool,
     *     no_cache?: bool,
     *     no_store?: bool,
     *     no_transform?: bool,
     *     immutable?: bool,
     * }|null $cacheHeaders {@see https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers}
     * @param array<string, string>|null $headers
     * @param list<array{
     *     field: string,
     *     direction: string,
     * }>|null $paginationViaCursor {@see https://api-platform.com/docs/core/pagination/#cursor-based-pagination}
     * @param array|null $normalizationContext   {@see https://api-platform.com/docs/core/serialization/#using-serialization-groups}
     * @param array|null $denormalizationContext {@see https://api-platform.com/docs/core/serialization/#using-serialization-groups}
     * @param array|null $hydraContext           {@see https://api-platform.com/docs/core/extending-jsonld-context/#hydra}
     * @param array{
     *     class?: string|null,
     *     name?: string,
     * }|string|false|null $input {@see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation}
     * @param array{
     *     class?: string|null,
     *     name?: string,
     * }|string|false|null $output {@see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation}
     * @param string|array|bool|null                              $mercure   {@see https://api-platform.com/docs/core/mercure}
     * @param string|bool|null                                    $messenger {@see https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus}
     * @param string|callable|null                                $provider  {@see https://api-platform.com/docs/core/state-providers/#state-providers}
     * @param string|callable|null                                $processor {@see https://api-platform.com/docs/core/state-processors/#state-processors}
     * @param WebLink[]|null                                      $links
     * @param array<class-string<ProblemExceptionInterface>>|null $errors
     */
    public function __construct(
        protected string $method = 'GET',
        protected ?string $uriTemplate = null,
        protected ?array $types = null,
        $formats = null,
        $inputFormats = null,
        $outputFormats = null,
        protected $uriVariables = null,
        protected ?string $routePrefix = null,
        protected ?string $routeName = null,
        protected ?array $defaults = null,
        protected ?array $requirements = null,
        protected ?array $options = null,
        protected ?bool $stateless = null,
        /**
         * The `sunset` option indicates when a deprecated operation will be removed.
         *
         * <div data-code-selector>
         *
         * ```php
         * <?php
         * // api/src/Entity/Parchment.php
         * use ApiPlatform\Metadata\Get;
         *
         * #[Get(deprecationReason: 'Create a Book instead', sunset: '01/01/2020')]
         * class Parchment
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Parchment:
         *         - operations:
         *               ApiPlatform\Metadata\Get:
         *                   deprecationReason: 'Create a Book instead'
         *                   sunset: '01/01/2020'
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Parchment">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\Get" deprecationReason="Create a Book instead" sunset="01/01/2020" />
         *         <operations>
         *     </resource>
         * </resources>
         * ```
         *
         * </div>
         */
        protected ?string $sunset = null,
        protected ?string $acceptPatch = null,
        protected $status = null,
        protected ?string $host = null,
        protected ?array $schemes = null,
        protected ?string $condition = null,
        protected ?string $controller = null,
        protected ?array $headers = null,
        protected ?array $cacheHeaders = null,
        protected ?array $paginationViaCursor = null,
        protected ?array $hydraContext = null,
        protected bool|OpenApiOperation|Webhook|null $openapi = null,
        protected ?array $exceptionToStatus = null,
        protected ?array $links = null,
        protected ?array $errors = null,
        protected ?bool $strictQueryParameterValidation = null,
        protected ?bool $hideHydraOperation = null,

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
        string|\Stringable|null $security = null,
        ?string $securityMessage = null,
        string|\Stringable|null $securityPostDenormalize = null,
        ?string $securityPostDenormalizeMessage = null,
        string|\Stringable|null $securityPostValidation = null,
        ?string $securityPostValidationMessage = null,
        ?string $deprecationReason = null,
        ?array $filters = null,
        ?array $validationContext = null,
        $input = null,
        $output = null,
        $mercure = null,
        $messenger = null,
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
        array|Parameters|null $parameters = null,
        array|string|null $rules = null,
        ?string $policy = null,
        array|string|null $middleware = null,
        ?bool $queryParameterValidationEnabled = null,
        ?bool $jsonStream = null,
        array $extraProperties = [],
    ) {
        $this->formats = (null === $formats || \is_array($formats)) ? $formats : [$formats];
        $this->inputFormats = (null === $inputFormats || \is_array($inputFormats)) ? $inputFormats : [$inputFormats];
        $this->outputFormats = (null === $outputFormats || \is_array($outputFormats)) ? $outputFormats : [$outputFormats];

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
            paginationViaCursor: $paginationViaCursor,
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
            deprecationReason: $deprecationReason,
            filters: $filters,
            validationContext: $validationContext,
            input: $input,
            output: $output,
            mercure: $mercure,
            messenger: $messenger,
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
            parameters: $parameters,
            rules: $rules,
            policy: $policy,
            middleware: $middleware,
            queryParameterValidationEnabled: $queryParameterValidationEnabled,
            strictQueryParameterValidation: $strictQueryParameterValidation,
            hideHydraOperation: $hideHydraOperation,
            jsonStream: $jsonStream,
            extraProperties: $extraProperties
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $self = clone $this;
        $self->method = $method;

        return $self;
    }

    public function getUriTemplate(): ?string
    {
        return $this->uriTemplate;
    }

    public function withUriTemplate(?string $uriTemplate = null): static
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
    public function withTypes($types): static
    {
        $self = clone $this;
        $self->types = (array) $types;

        return $self;
    }

    /**
     * @return array<int|string, string|string[]>|null
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * @param array<int|string, string|string[]>|string|null $formats
     */
    public function withFormats($formats = null): static
    {
        $self = clone $this;
        $self->formats = (null === $formats || \is_array($formats)) ? $formats : [$formats];

        return $self;
    }

    /**
     * @return array<int|string, string|string[]>|null
     */
    public function getInputFormats()
    {
        return $this->inputFormats;
    }

    /**
     * @param array<int|string, string|string[]>|string|null $inputFormats
     */
    public function withInputFormats($inputFormats = null): static
    {
        $self = clone $this;
        $self->inputFormats = (null === $inputFormats || \is_array($inputFormats)) ? $inputFormats : [$inputFormats];

        return $self;
    }

    /**
     * @return array<int|string, string|string[]>|null
     */
    public function getOutputFormats()
    {
        return $this->outputFormats;
    }

    /**
     * @param array<int|string, string|string[]>|string|null $outputFormats
     */
    public function withOutputFormats($outputFormats = null): static
    {
        $self = clone $this;
        $self->outputFormats = (null === $outputFormats || \is_array($outputFormats)) ? $outputFormats : [$outputFormats];

        return $self;
    }

    /**
     * @return array<string, mixed>|array<int, Link>|list<string>|null
     */
    public function getUriVariables()
    {
        return $this->uriVariables;
    }

    /**
     * @param array<string, mixed>|array<int, Link>|list<string> $uriVariables
     */
    public function withUriVariables($uriVariables): static
    {
        $self = clone $this;
        $self->uriVariables = $uriVariables;

        return $self;
    }

    public function getRoutePrefix(): ?string
    {
        return $this->routePrefix;
    }

    public function withRoutePrefix(string $routePrefix): static
    {
        $self = clone $this;
        $self->routePrefix = $routePrefix;

        return $self;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function withRouteName(?string $routeName): static
    {
        $self = clone $this;
        $self->routeName = $routeName;

        return $self;
    }

    public function getDefaults(): ?array
    {
        return $this->defaults;
    }

    public function withDefaults(array $defaults): static
    {
        $self = clone $this;
        $self->defaults = $defaults;

        return $self;
    }

    public function getRequirements(): ?array
    {
        return $this->requirements;
    }

    public function withRequirements(array $requirements): static
    {
        $self = clone $this;
        $self->requirements = $requirements;

        return $self;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function withOptions(array $options): static
    {
        $self = clone $this;
        $self->options = $options;

        return $self;
    }

    public function getStateless(): ?bool
    {
        return $this->stateless;
    }

    /**
     * @param bool $stateless
     */
    public function withStateless($stateless): static
    {
        $self = clone $this;
        $self->stateless = $stateless;

        return $self;
    }

    public function getSunset(): ?string
    {
        return $this->sunset;
    }

    public function withSunset(string $sunset): static
    {
        $self = clone $this;
        $self->sunset = $sunset;

        return $self;
    }

    public function getAcceptPatch(): ?string
    {
        return $this->acceptPatch;
    }

    public function withAcceptPatch(string $acceptPatch): static
    {
        $self = clone $this;
        $self->acceptPatch = $acceptPatch;

        return $self;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function withStatus(int $status): static
    {
        $self = clone $this;
        $self->status = $status;

        return $self;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function withHost(string $host): static
    {
        $self = clone $this;
        $self->host = $host;

        return $self;
    }

    public function getSchemes(): ?array
    {
        return $this->schemes;
    }

    public function withSchemes(array $schemes): static
    {
        $self = clone $this;
        $self->schemes = $schemes;

        return $self;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function withCondition(string $condition): static
    {
        $self = clone $this;
        $self->condition = $condition;

        return $self;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function withController(string $controller): static
    {
        $self = clone $this;
        $self->controller = $controller;

        return $self;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function withHeaders(array $headers): static
    {
        $self = clone $this;
        $self->headers = $headers;

        return $self;
    }

    public function getCacheHeaders(): ?array
    {
        return $this->cacheHeaders;
    }

    public function withCacheHeaders(array $cacheHeaders): static
    {
        $self = clone $this;
        $self->cacheHeaders = $cacheHeaders;

        return $self;
    }

    public function getPaginationViaCursor(): ?array
    {
        return $this->paginationViaCursor;
    }

    public function withPaginationViaCursor(array $paginationViaCursor): static
    {
        $self = clone $this;
        $self->paginationViaCursor = $paginationViaCursor;

        return $self;
    }

    public function getHydraContext(): ?array
    {
        return $this->hydraContext;
    }

    public function withHydraContext(array $hydraContext): static
    {
        $self = clone $this;
        $self->hydraContext = $hydraContext;

        return $self;
    }

    public function getOpenapi(): bool|OpenApiOperation|Webhook|null
    {
        return $this->openapi;
    }

    public function withOpenapi(bool|OpenApiOperation|Webhook $openapi): static
    {
        $self = clone $this;
        $self->openapi = $openapi;

        return $self;
    }

    public function getExceptionToStatus(): ?array
    {
        return $this->exceptionToStatus;
    }

    public function withExceptionToStatus(array $exceptionToStatus): static
    {
        $self = clone $this;
        $self->exceptionToStatus = $exceptionToStatus;

        return $self;
    }

    public function getLinks(): ?array
    {
        return $this->links;
    }

    /**
     * @param WebLink[] $links
     */
    public function withLinks(array $links): static
    {
        $self = clone $this;
        $self->links = $links;

        return $self;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * @param class-string<ProblemExceptionInterface>[] $errors
     */
    public function withErrors(array $errors): static
    {
        $self = clone $this;
        $self->errors = $errors;

        return $self;
    }
}
