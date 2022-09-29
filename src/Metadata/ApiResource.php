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

/**
 * Resource metadata attribute.
 *
 * @Annotation
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class ApiResource
{
    use WithResourceTrait;

    protected $operations;
    protected $uriTemplate;
    protected $shortName;
    protected $description;
    /**
     * @var string|string[]|null
     */
    protected $types;
    /**
     * @var array|mixed|string|null
     */
    protected $formats;
    /**
     * @var array|mixed|string|null
     */
    protected $inputFormats;
    /**
     * @var array|mixed|string|null
     */
    protected $outputFormats;
    /**
     * @var array<string, Link>|array<string, array>|string[]|string|null
     */
    protected $uriVariables;
    protected $routePrefix;
    protected $defaults;
    protected $requirements;
    protected $options;
    protected $stateless;
    protected $sunset;
    protected $acceptPatch;

    protected $status;
    protected $host;
    protected $schemes;
    protected $condition;
    protected $controller;
    protected $class;
    protected $urlGenerationStrategy;
    protected $deprecationReason;
    protected $cacheHeaders;
    protected $normalizationContext;
    protected $denormalizationContext;
    /**
     * @var string[]|null
     */
    protected $hydraContext;
    protected $openapiContext;
    protected $validationContext;
    /**
     * @var string[]
     */
    protected $filters;
    protected $elasticsearch;
    /**
     * @var array|bool|mixed|null
     */
    protected $mercure;
    /**
     * @var bool|mixed|null
     */
    protected $messenger;
    protected $input;
    protected $output;
    protected $order;
    protected $fetchPartial;
    protected $forceEager;
    protected $paginationClientEnabled;
    protected $paginationClientItemsPerPage;
    protected $paginationClientPartial;
    protected $paginationViaCursor;
    protected $paginationEnabled;
    protected $paginationFetchJoinCollection;
    protected $paginationUseOutputWalkers;
    protected $paginationItemsPerPage;
    protected $paginationMaximumItemsPerPage;
    protected $paginationPartial;
    protected $paginationType;
    protected $security;
    protected $securityMessage;
    protected $securityPostDenormalize;
    protected $securityPostDenormalizeMessage;
    protected $securityPostValidation;
    protected $securityPostValidationMessage;
    protected $compositeIdentifier;
    protected $exceptionToStatus;
    protected $queryParameterValidationEnabled;
    protected $graphQlOperations;
    /**
     * @var string|callable|null
     */
    protected $provider;
    /**
     * @var string|callable|null
     */
    protected $processor;
    protected $extraProperties;

    /**
     * @param string|string[]|null $types                          The RDF types of this resource
     * @param mixed|null           $operations
     * @param array|string|null    $formats                        https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string|null    $inputFormats                   https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string|null    $outputFormats                  https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param mixed|null           $uriVariables
     * @param string|null          $routePrefix                    https://api-platform.com/docs/core/operations/#prefixing-all-routes-of-all-operations
     * @param string|null          $sunset                         https://api-platform.com/docs/core/deprecations/#setting-the-sunset-http-header-to-indicate-when-a-resource-or-an-operation-will-be-removed
     * @param string|null          $deprecationReason              https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @param array|null           $cacheHeaders                   https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers
     * @param array|null           $normalizationContext           https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param array|null           $denormalizationContext         https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param string[]|null        $hydraContext                   https://api-platform.com/docs/core/extending-jsonld-context/#hydra
     * @param array|null           $openapiContext                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param array|null           $validationContext              https://api-platform.com/docs/core/validation/#using-validation-groups
     * @param string[]|null        $filters                        https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters
     * @param bool|null            $elasticsearch                  https://api-platform.com/docs/core/elasticsearch/
     * @param mixed|null           $mercure                        https://api-platform.com/docs/core/mercure
     * @param mixed|null           $messenger                      https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus
     * @param mixed|null           $input                          https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param mixed|null           $output                         https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param array|null           $order                          https://api-platform.com/docs/core/default-order/#overriding-default-order
     * @param bool|null            $fetchPartial                   https://api-platform.com/docs/core/performance/#fetch-partial
     * @param bool|null            $forceEager                     https://api-platform.com/docs/core/performance/#force-eager
     * @param bool|null            $paginationClientEnabled        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1
     * @param bool|null            $paginationClientItemsPerPage   https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3
     * @param bool|null            $paginationClientPartial        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6
     * @param array|null           $paginationViaCursor            https://api-platform.com/docs/core/pagination/#cursor-based-pagination
     * @param bool|null            $paginationEnabled              https://api-platform.com/docs/core/pagination/#for-a-specific-resource
     * @param bool|null            $paginationFetchJoinCollection  https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator
     * @param int|null             $paginationItemsPerPage         https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page
     * @param int|null             $paginationMaximumItemsPerPage  https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page
     * @param bool|null            $paginationPartial              https://api-platform.com/docs/core/performance/#partial-pagination
     * @param string|null          $paginationType                 https://api-platform.com/docs/core/graphql/#using-the-page-based-pagination
     * @param string|null          $security                       https://api-platform.com/docs/core/security
     * @param string|null          $securityMessage                https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param string|null          $securityPostDenormalize        https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @param string|null          $securityPostDenormalizeMessage https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param string               $securityPostValidation         https://api-platform.com/docs/core/security/#executing-access-control-rules-after-validtion
     * @param string               $securityPostValidationMessage  https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param string|callable|null $provider
     * @param string|callable|null $processor
     */
    public function __construct(
        ?string $uriTemplate = null,
        ?string $shortName = null,
        ?string $description = null,
        $types = null,
        $operations = null,
        $formats = null,
        $inputFormats = null,
        $outputFormats = null,
        $uriVariables = null,
        ?string $routePrefix = null,
        ?array $defaults = null,
        ?array $requirements = null,
        ?array $options = null,
        ?bool $stateless = null,
        ?string $sunset = null,
        ?string $acceptPatch = null,
        ?int $status = null,
        ?string $host = null,
        ?array $schemes = null,
        ?string $condition = null,
        ?string $controller = null,
        ?string $class = null,
        ?int $urlGenerationStrategy = null,
        ?string $deprecationReason = null,
        ?array $cacheHeaders = null,
        ?array $normalizationContext = null,
        ?array $denormalizationContext = null,
        ?array $hydraContext = null,
        ?array $openapiContext = null,
        ?array $validationContext = null,
        ?array $filters = null,
        ?bool $elasticsearch = null,
        $mercure = null,
        $messenger = null,
        $input = null,
        $output = null,
        ?array $order = null,
        ?bool $fetchPartial = null,
        ?bool $forceEager = null,
        ?bool $paginationClientEnabled = null,
        ?bool $paginationClientItemsPerPage = null,
        ?bool $paginationClientPartial = null,
        ?array $paginationViaCursor = null,
        ?bool $paginationEnabled = null,
        ?bool $paginationFetchJoinCollection = null,
        ?bool $paginationUseOutputWalkers = null,
        ?int $paginationItemsPerPage = null,
        ?int $paginationMaximumItemsPerPage = null,
        ?bool $paginationPartial = null,
        ?string $paginationType = null,
        ?string $security = null,
        ?string $securityMessage = null,
        ?string $securityPostDenormalize = null,
        ?string $securityPostDenormalizeMessage = null,
        ?string $securityPostValidation = null,
        ?string $securityPostValidationMessage = null,
        ?bool $compositeIdentifier = null,
        ?array $exceptionToStatus = null,
        ?bool $queryParameterValidationEnabled = null,
        ?array $graphQlOperations = null,
        $provider = null,
        $processor = null,
        array $extraProperties = []
    ) {
        $this->operations = null === $operations ? null : new Operations($operations);
        $this->uriTemplate = $uriTemplate;
        $this->shortName = $shortName;
        $this->description = $description;
        $this->types = null === $types ? null : (array) $types;
        $this->formats = $formats;
        $this->inputFormats = $inputFormats;
        $this->outputFormats = $outputFormats;
        $this->uriVariables = $uriVariables;
        $this->routePrefix = $routePrefix;
        $this->defaults = $defaults;
        $this->requirements = $requirements;
        $this->options = $options;
        $this->stateless = $stateless;
        $this->sunset = $sunset;
        $this->acceptPatch = $acceptPatch;
        $this->status = $status;
        $this->host = $host;
        $this->schemes = $schemes;
        $this->condition = $condition;
        $this->controller = $controller;
        $this->class = $class;
        $this->urlGenerationStrategy = $urlGenerationStrategy;
        $this->deprecationReason = $deprecationReason;
        $this->cacheHeaders = $cacheHeaders;
        $this->normalizationContext = $normalizationContext;
        $this->denormalizationContext = $denormalizationContext;
        $this->hydraContext = $hydraContext;
        $this->openapiContext = $openapiContext;
        $this->validationContext = $validationContext;
        $this->filters = $filters;
        $this->elasticsearch = $elasticsearch;
        $this->mercure = $mercure;
        $this->messenger = $messenger;
        $this->input = $input;
        $this->output = $output;
        $this->order = $order;
        $this->fetchPartial = $fetchPartial;
        $this->forceEager = $forceEager;
        $this->paginationClientEnabled = $paginationClientEnabled;
        $this->paginationClientItemsPerPage = $paginationClientItemsPerPage;
        $this->paginationClientPartial = $paginationClientPartial;
        $this->paginationViaCursor = $paginationViaCursor;
        $this->paginationEnabled = $paginationEnabled;
        $this->paginationFetchJoinCollection = $paginationFetchJoinCollection;
        $this->paginationUseOutputWalkers = $paginationUseOutputWalkers;
        $this->paginationItemsPerPage = $paginationItemsPerPage;
        $this->paginationMaximumItemsPerPage = $paginationMaximumItemsPerPage;
        $this->paginationPartial = $paginationPartial;
        $this->paginationType = $paginationType;
        $this->security = $security;
        $this->securityMessage = $securityMessage;
        $this->securityPostDenormalize = $securityPostDenormalize;
        $this->securityPostDenormalizeMessage = $securityPostDenormalizeMessage;
        $this->securityPostValidation = $securityPostValidation;
        $this->securityPostValidationMessage = $securityPostValidationMessage;
        $this->compositeIdentifier = $compositeIdentifier;
        $this->exceptionToStatus = $exceptionToStatus;
        $this->queryParameterValidationEnabled = $queryParameterValidationEnabled;
        $this->graphQlOperations = $graphQlOperations;
        $this->provider = $provider;
        $this->processor = $processor;
        $this->extraProperties = $extraProperties;
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

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function withShortName(string $shortName): self
    {
        $self = clone $this;
        $self->shortName = $shortName;

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

    public function getStatus()
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

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function withClass(string $class): self
    {
        $self = clone $this;
        $self->class = $class;

        return $self;
    }

    public function getUrlGenerationStrategy(): ?int
    {
        return $this->urlGenerationStrategy;
    }

    public function withUrlGenerationStrategy(int $urlGenerationStrategy): self
    {
        $self = clone $this;
        $self->urlGenerationStrategy = $urlGenerationStrategy;

        return $self;
    }

    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
    }

    public function withDeprecationReason(string $deprecationReason): self
    {
        $self = clone $this;
        $self->deprecationReason = $deprecationReason;

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

    public function getNormalizationContext(): ?array
    {
        return $this->normalizationContext;
    }

    public function withNormalizationContext(array $normalizationContext): self
    {
        $self = clone $this;
        $self->normalizationContext = $normalizationContext;

        return $self;
    }

    public function getDenormalizationContext(): ?array
    {
        return $this->denormalizationContext;
    }

    public function withDenormalizationContext(array $denormalizationContext): self
    {
        $self = clone $this;
        $self->denormalizationContext = $denormalizationContext;

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

    public function getValidationContext(): ?array
    {
        return $this->validationContext;
    }

    public function withValidationContext(array $validationContext): self
    {
        $self = clone $this;
        $self->validationContext = $validationContext;

        return $self;
    }

    /**
     * @return string[]|null
     */
    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function withFilters(array $filters): self
    {
        $self = clone $this;
        $self->filters = $filters;

        return $self;
    }

    public function getElasticsearch(): ?bool
    {
        return $this->elasticsearch;
    }

    public function withElasticsearch(bool $elasticsearch): self
    {
        $self = clone $this;
        $self->elasticsearch = $elasticsearch;

        return $self;
    }

    /**
     * @return array|bool|mixed|null
     */
    public function getMercure()
    {
        return $this->mercure;
    }

    public function withMercure($mercure): self
    {
        $self = clone $this;
        $self->mercure = $mercure;

        return $self;
    }

    public function getMessenger()
    {
        return $this->messenger;
    }

    public function withMessenger($messenger): self
    {
        $self = clone $this;
        $self->messenger = $messenger;

        return $self;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function withInput($input): self
    {
        $self = clone $this;
        $self->input = $input;

        return $self;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function withOutput($output): self
    {
        $self = clone $this;
        $self->output = $output;

        return $self;
    }

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function withOrder(array $order): self
    {
        $self = clone $this;
        $self->order = $order;

        return $self;
    }

    public function getFetchPartial(): ?bool
    {
        return $this->fetchPartial;
    }

    public function withFetchPartial(bool $fetchPartial): self
    {
        $self = clone $this;
        $self->fetchPartial = $fetchPartial;

        return $self;
    }

    public function getForceEager(): ?bool
    {
        return $this->forceEager;
    }

    public function withForceEager(bool $forceEager): self
    {
        $self = clone $this;
        $self->forceEager = $forceEager;

        return $self;
    }

    public function getPaginationClientEnabled(): ?bool
    {
        return $this->paginationClientEnabled;
    }

    public function withPaginationClientEnabled(bool $paginationClientEnabled): self
    {
        $self = clone $this;
        $self->paginationClientEnabled = $paginationClientEnabled;

        return $self;
    }

    public function getPaginationClientItemsPerPage(): ?bool
    {
        return $this->paginationClientItemsPerPage;
    }

    public function withPaginationClientItemsPerPage(bool $paginationClientItemsPerPage): self
    {
        $self = clone $this;
        $self->paginationClientItemsPerPage = $paginationClientItemsPerPage;

        return $self;
    }

    public function getPaginationClientPartial(): ?bool
    {
        return $this->paginationClientPartial;
    }

    public function withPaginationClientPartial(bool $paginationClientPartial): self
    {
        $self = clone $this;
        $self->paginationClientPartial = $paginationClientPartial;

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

    public function getPaginationEnabled(): ?bool
    {
        return $this->paginationEnabled;
    }

    public function withPaginationEnabled(bool $paginationEnabled): self
    {
        $self = clone $this;
        $self->paginationEnabled = $paginationEnabled;

        return $self;
    }

    public function getPaginationFetchJoinCollection(): ?bool
    {
        return $this->paginationFetchJoinCollection;
    }

    public function withPaginationFetchJoinCollection(bool $paginationFetchJoinCollection): self
    {
        $self = clone $this;
        $self->paginationFetchJoinCollection = $paginationFetchJoinCollection;

        return $self;
    }

    public function getPaginationUseOutputWalkers(): ?bool
    {
        return $this->paginationUseOutputWalkers;
    }

    public function withPaginationUseOutputWalkers(bool $paginationUseOutputWalkers): self
    {
        $self = clone $this;
        $self->paginationUseOutputWalkers = $paginationUseOutputWalkers;

        return $self;
    }

    public function getPaginationItemsPerPage(): ?int
    {
        return $this->paginationItemsPerPage;
    }

    public function withPaginationItemsPerPage(int $paginationItemsPerPage): self
    {
        $self = clone $this;
        $self->paginationItemsPerPage = $paginationItemsPerPage;

        return $self;
    }

    public function getPaginationMaximumItemsPerPage(): ?int
    {
        return $this->paginationMaximumItemsPerPage;
    }

    public function withPaginationMaximumItemsPerPage(int $paginationMaximumItemsPerPage): self
    {
        $self = clone $this;
        $self->paginationMaximumItemsPerPage = $paginationMaximumItemsPerPage;

        return $self;
    }

    public function getPaginationPartial(): ?bool
    {
        return $this->paginationPartial;
    }

    public function withPaginationPartial(bool $paginationPartial): self
    {
        $self = clone $this;
        $self->paginationPartial = $paginationPartial;

        return $self;
    }

    public function getPaginationType(): ?string
    {
        return $this->paginationType;
    }

    public function withPaginationType(string $paginationType): self
    {
        $self = clone $this;
        $self->paginationType = $paginationType;

        return $self;
    }

    public function getSecurity(): ?string
    {
        return $this->security;
    }

    public function withSecurity(string $security): self
    {
        $self = clone $this;
        $self->security = $security;

        return $self;
    }

    public function getSecurityMessage(): ?string
    {
        return $this->securityMessage;
    }

    public function withSecurityMessage(string $securityMessage): self
    {
        $self = clone $this;
        $self->securityMessage = $securityMessage;

        return $self;
    }

    public function getSecurityPostDenormalize(): ?string
    {
        return $this->securityPostDenormalize;
    }

    public function withSecurityPostDenormalize(string $securityPostDenormalize): self
    {
        $self = clone $this;
        $self->securityPostDenormalize = $securityPostDenormalize;

        return $self;
    }

    public function getSecurityPostDenormalizeMessage(): ?string
    {
        return $this->securityPostDenormalizeMessage;
    }

    public function withSecurityPostDenormalizeMessage(string $securityPostDenormalizeMessage): self
    {
        $self = clone $this;
        $self->securityPostDenormalizeMessage = $securityPostDenormalizeMessage;

        return $self;
    }

    public function getSecurityPostValidation(): ?string
    {
        return $this->securityPostValidation;
    }

    public function withSecurityPostValidation(?string $securityPostValidation = null): self
    {
        $self = clone $this;
        $self->securityPostValidation = $securityPostValidation;

        return $self;
    }

    public function getSecurityPostValidationMessage(): ?string
    {
        return $this->securityPostValidationMessage;
    }

    public function withSecurityPostValidationMessage(?string $securityPostValidationMessage = null): self
    {
        $self = clone $this;
        $self->securityPostValidationMessage = $securityPostValidationMessage;

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

    /**
     * @return string|callable|null
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    public function withProcessor($processor): self
    {
        $self = clone $this;
        $self->processor = $processor;

        return $self;
    }

    /**
     * @return string|callable|null
     */
    public function getProvider()
    {
        return $this->provider;
    }

    public function withProvider($provider): self
    {
        $self = clone $this;
        $self->provider = $provider;

        return $self;
    }

    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    public function withExtraProperties(array $extraProperties): self
    {
        $self = clone $this;
        $self->extraProperties = $extraProperties;

        return $self;
    }
}
