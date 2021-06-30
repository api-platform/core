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

use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\State\OptionsInterface;

/**
 * Resource metadata attribute.
 *
 * @Annotation
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class ApiResource extends Metadata
{
    use WithResourceTrait;

    protected ?Operations $operations;

    /**
     * @param array|string|null                                               $types                          The RDF types of this resource
     * @param mixed|null                                                      $operations
     * @param array|string|null                                               $formats                        https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string|null                                               $inputFormats                   https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string|null                                               $outputFormats                  https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array<string, Link>|array<string, mixed[]>|string[]|string|null $uriVariables
     * @param string|null                                                     $routePrefix                    https://api-platform.com/docs/core/operations/#prefixing-all-routes-of-all-operations
     * @param string|null                                                     $sunset                         https://api-platform.com/docs/core/deprecations/#setting-the-sunset-http-header-to-indicate-when-a-resource-or-an-operation-will-be-removed
     * @param string|null                                                     $deprecationReason              https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @param array|null                                                      $cacheHeaders                   https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers
     * @param array|null                                                      $normalizationContext           https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param array|null                                                      $denormalizationContext         https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param string[]|null                                                   $hydraContext                   https://api-platform.com/docs/core/extending-jsonld-context/#hydra
     * @param array|null                                                      $openapiContext                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param bool|OpenApiOperation|null                                      $openapi                        https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param array|null                                                      $validationContext              https://api-platform.com/docs/core/validation/#using-validation-groups
     * @param string[]                                                        $filters                        https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters
     * @param bool|null                                                       $elasticsearch                  https://api-platform.com/docs/core/elasticsearch/
     * @param mixed|null                                                      $mercure                        https://api-platform.com/docs/core/mercure
     * @param mixed|null                                                      $messenger                      https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus
     * @param mixed|null                                                      $input                          https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param mixed|null                                                      $output                         https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param array|null                                                      $order                          https://api-platform.com/docs/core/default-order/#overriding-default-order
     * @param bool|null                                                       $fetchPartial                   https://api-platform.com/docs/core/performance/#fetch-partial
     * @param bool|null                                                       $forceEager                     https://api-platform.com/docs/core/performance/#force-eager
     * @param bool|null                                                       $paginationClientEnabled        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1
     * @param bool|null                                                       $paginationClientItemsPerPage   https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3
     * @param bool|null                                                       $paginationClientPartial        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6
     * @param array|null                                                      $paginationViaCursor            https://api-platform.com/docs/core/pagination/#cursor-based-pagination
     * @param bool|null                                                       $paginationEnabled              https://api-platform.com/docs/core/pagination/#for-a-specific-resource
     * @param bool|null                                                       $paginationFetchJoinCollection  https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator
     * @param int|null                                                        $paginationItemsPerPage         https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page
     * @param int|null                                                        $paginationMaximumItemsPerPage  https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page
     * @param bool|null                                                       $paginationPartial              https://api-platform.com/docs/core/performance/#partial-pagination
     * @param string|null                                                     $paginationType                 https://api-platform.com/docs/core/graphql/#using-the-page-based-pagination
     * @param string|null                                                     $security                       https://api-platform.com/docs/core/security
     * @param string|null                                                     $securityMessage                https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param string|null                                                     $securityPostDenormalize        https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @param string|null                                                     $securityPostDenormalizeMessage https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param string                                                          $securityPostValidation         https://api-platform.com/docs/core/security/#executing-access-control-rules-after-validtion
     * @param string                                                          $securityPostValidationMessage  https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param array|null                                                      $translation                    https://api-platform.com/docs/core/translation
     * @param mixed|null                                                      $provider
     * @param mixed|null                                                      $processor
     */
    public function __construct(
        protected ?string $uriTemplate = null,
        protected ?string $shortName = null,
        protected ?string $description = null,
        protected string|array|null $types = null,
        $operations = null,
        protected $formats = null,
        protected $inputFormats = null,
        protected $outputFormats = null,
        protected $uriVariables = null,
        protected ?string $routePrefix = null,
        protected ?array $defaults = null,
        protected ?array $requirements = null,
        protected ?array $options = null,
        protected ?bool $stateless = null,
        protected ?string $sunset = null,
        protected ?string $acceptPatch = null,
        protected ?int $status = null,
        protected ?string $host = null,
        protected ?array $schemes = null,
        protected ?string $condition = null,
        protected ?string $controller = null,
        protected ?string $class = null,
        protected ?int $urlGenerationStrategy = null,
        protected ?string $deprecationReason = null,
        protected ?array $cacheHeaders = null,
        protected ?array $normalizationContext = null,
        protected ?array $denormalizationContext = null,
        protected ?bool $collectDenormalizationErrors = null,
        protected ?array $hydraContext = null,
        protected ?array $openapiContext = null, // TODO Remove in 4.0
        protected bool|OpenApiOperation|null $openapi = null,
        protected ?array $validationContext = null,
        protected ?array $filters = null,
        protected ?bool $elasticsearch = null,
        protected $mercure = null,
        protected $messenger = null,
        protected $input = null,
        protected $output = null,
        protected ?array $order = null,
        protected ?bool $fetchPartial = null,
        protected ?bool $forceEager = null,
        protected ?bool $paginationClientEnabled = null,
        protected ?bool $paginationClientItemsPerPage = null,
        protected ?bool $paginationClientPartial = null,
        protected ?array $paginationViaCursor = null,
        protected ?bool $paginationEnabled = null,
        protected ?bool $paginationFetchJoinCollection = null,
        protected ?bool $paginationUseOutputWalkers = null,
        protected ?int $paginationItemsPerPage = null,
        protected ?int $paginationMaximumItemsPerPage = null,
        protected ?bool $paginationPartial = null,
        protected ?string $paginationType = null,
        protected ?string $security = null,
        protected ?string $securityMessage = null,
        protected ?string $securityPostDenormalize = null,
        protected ?string $securityPostDenormalizeMessage = null,
        protected ?string $securityPostValidation = null,
        protected ?string $securityPostValidationMessage = null,
        protected ?array $translation = null,
        protected ?bool $compositeIdentifier = null,
        protected ?array $exceptionToStatus = null,
        protected ?bool $queryParameterValidationEnabled = null,
        protected ?array $graphQlOperations = null,
        $provider = null,
        $processor = null,
        protected ?OptionsInterface $stateOptions = null,
        protected array $extraProperties = [],
    ) {
        parent::__construct(
            shortName: $shortName,
            class: $class,
            description: $description,
            urlGenerationStrategy: $urlGenerationStrategy,
            deprecationReason: $deprecationReason,
            normalizationContext: $normalizationContext,
            denormalizationContext: $denormalizationContext,
            collectDenormalizationErrors: $collectDenormalizationErrors,
            validationContext: $validationContext,
            filters: $filters,
            elasticsearch: $elasticsearch,
            mercure: $mercure,
            messenger: $messenger,
            input: $input,
            output: $output,
            order: $order,
            fetchPartial: $fetchPartial,
            forceEager: $forceEager,
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
            security: $security,
            securityMessage: $securityMessage,
            securityPostDenormalize: $securityPostDenormalize,
            securityPostDenormalizeMessage: $securityPostDenormalizeMessage,
            securityPostValidation: $securityPostValidation,
            securityPostValidationMessage: $securityPostValidationMessage,
            translation: $translation,
            provider: $provider,
            processor: $processor,
            stateOptions: $stateOptions,
            extraProperties: $extraProperties
        );

        $this->operations = null === $operations ? null : new Operations($operations);
        $this->provider = $provider;
        $this->processor = $processor;
        if (\is_string($types)) {
            $this->types = (array) $types;
        }
    }

    public function getOperations(): ?Operations
    {
        return $this->operations;
    }

    public function withOperations(Operations $operations): self
    {
        $self = clone $this;
        $self->operations = $operations;

        return $self;
    }

    public function getUriTemplate(): ?string
    {
        return $this->uriTemplate;
    }

    public function withUriTemplate(string $uriTemplate): self
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
    public function withTypes(array|string $types): self
    {
        $self = clone $this;
        $self->types = (array) $types;

        return $self;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * @param mixed|null $formats
     */
    public function withFormats($formats): self
    {
        $self = clone $this;
        $self->formats = $formats;

        return $self;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getInputFormats()
    {
        return $this->inputFormats;
    }

    /**
     * @param mixed|null $inputFormats
     */
    public function withInputFormats($inputFormats): self
    {
        $self = clone $this;
        $self->inputFormats = $inputFormats;

        return $self;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getOutputFormats()
    {
        return $this->outputFormats;
    }

    /**
     * @param mixed|null $outputFormats
     */
    public function withOutputFormats($outputFormats): self
    {
        $self = clone $this;
        $self->outputFormats = $outputFormats;

        return $self;
    }

    /**
     * @return array<string, Link>|array<string, array>|string[]|string|null
     */
    public function getUriVariables()
    {
        return $this->uriVariables;
    }

    /**
     * @param array<string, Link>|array<string, array>|string[]|string|null $uriVariables
     */
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

    public function withStateless(bool $stateless): self
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

    public function withStatus($status): self
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

    /**
     * @return string[]|null
     */
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

    /**
     * TODO Remove in 4.0.
     *
     * @deprecated
     */
    public function getOpenapiContext(): ?array
    {
        return $this->openapiContext;
    }

    /**
     * TODO Remove in 4.0.
     *
     * @deprecated
     */
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

    /**
     * @return GraphQlOperation[]
     */
    public function getGraphQlOperations(): ?array
    {
        return $this->graphQlOperations;
    }

    public function withGraphQlOperations(array $graphQlOperations): self
    {
        $self = clone $this;
        $self->graphQlOperations = $graphQlOperations;

        return $self;
    }
}
