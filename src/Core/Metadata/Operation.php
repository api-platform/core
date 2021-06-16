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

/**
 * @psalm-immutable
 */
class Operation
{
    use AttributeTrait;

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';

    /**
     * @param string       $method
     * @param string       $uriTemplate
     * @param string       $shortName
     * @param string       $description
     * @param array        $types
     * @param array|string $formats                        https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string $inputFormats                   https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string $outputFormats                  https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array        $identifiers
     * @param array        $links
     * @param string       $routePrefix                    https://api-platform.com/docs/core/operations/#prefixing-all-routes-of-all-operations
     * @param string       $routeName
     * @param array        $defaults
     * @param array        $requirements
     * @param array        $options
     * @param bool         $stateless
     * @param string       $sunset                         https://api-platform.com/docs/core/deprecations/#setting-the-sunset-http-header-to-indicate-when-a-resource-or-an-operation-will-be-removed
     * @param string       $host
     * @param array        $schemes
     * @param string       $condition
     * @param string       $controller
     * @param string       $class
     * @param int          $urlGenerationStrategy
     * @param bool         $collection
     * @param string       $deprecationReason              https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @param array        $cacheHeaders                   https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers
     * @param array        $normalizationContext           https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param array        $denormalizationContext         https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param string[]     $hydraContext                   https://api-platform.com/docs/core/extending-jsonld-context/#hydra
     * @param array        $openapiContext                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param array        $validationGroups               https://api-platform.com/docs/core/validation/#using-validation-groups
     * @param string[]     $filters                        https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters
     * @param bool         $elasticsearch                  https://api-platform.com/docs/core/elasticsearch/
     * @param bool|array   $mercure                        https://api-platform.com/docs/core/mercure
     * @param bool         $messenger                      https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus
     * @param mixed        $input                          https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param mixed        $output                         https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param array        $order                          https://api-platform.com/docs/core/default-order/#overriding-default-order
     * @param bool         $fetchPartial                   https://api-platform.com/docs/core/performance/#fetch-partial
     * @param bool         $forceEager                     https://api-platform.com/docs/core/performance/#force-eager
     * @param bool         $paginationClientEnabled        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1
     * @param bool         $paginationClientItemsPerPage   https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3
     * @param bool         $paginationClientPartial        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6
     * @param array        $paginationViaCursor            https://api-platform.com/docs/core/pagination/#cursor-based-pagination
     * @param bool         $paginationEnabled              https://api-platform.com/docs/core/pagination/#for-a-specific-resource
     * @param bool         $paginationFetchJoinCollection  https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator
     * @param int          $paginationItemsPerPage         https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page
     * @param int          $paginationMaximumItemsPerPage  https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page
     * @param bool         $paginationPartial              https://api-platform.com/docs/core/performance/#partial-pagination
     * @param string       $paginationType                 https://api-platform.com/docs/core/graphql/#using-the-page-based-pagination
     * @param string       $security                       https://api-platform.com/docs/core/security
     * @param string       $securityMessage                https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param string       $securityPostDenormalize        https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @param string       $securityPostDenormalizeMessage https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param bool         $compositeIdentifier
     * @param GraphQl|null $graphQl
     */
    public function __construct(
        public string $method = self::METHOD_GET,
        public ?string $uriTemplate = null,
        public ?string $shortName = null,
        public ?string $description = null,
        public array $types = [],
        public mixed $formats = null,
        public mixed $inputFormats = null,
        public mixed $outputFormats = null,
        public array $identifiers = [],
        public array $links = [],
        public string $routePrefix = '',
        public ?string $routeName = null,
        public array $defaults = [],
        public array $requirements = [],
        public array $options = [],
        public ?bool $stateless = null,
        public ?string $sunset = null,
        public string $host = '',
        public array $schemes = [],
        public string $condition = '',
        public string $controller = 'api_platform.action.placeholder',
        public ?string $class = null,
        public ?int $urlGenerationStrategy = null,
        public bool $collection = false,
        public ?string $deprecationReason = null,
        public array $cacheHeaders = [],
        public array $normalizationContext = [],
        public array $denormalizationContext = [],
        public array $hydraContext = [],
        public array $openapiContext = [],
        // TODO: rename validationContext having ['groups' => []]
        public array $validationGroups = [],
        public array $filters = [],
        public ?bool $elasticsearch = null,
        public mixed $mercure = null,
        public mixed $messenger = null,
        public mixed $input = null,
        public mixed $output = null,
        public array $order = [],
        public ?bool $fetchPartial = null,
        public ?bool $forceEager = null,
        public ?bool $paginationClientEnabled = null,
        public ?bool $paginationClientItemsPerPage = null,
        public ?bool $paginationClientPartial = null,
        public array $paginationViaCursor = [],
        public ?bool $paginationEnabled = null,
        public ?bool $paginationFetchJoinCollection = null,
        public ?int $paginationItemsPerPage = null,
        public ?int $paginationMaximumItemsPerPage = null,
        public ?bool $paginationPartial = null,
        public ?string $paginationType = null,
        public ?string $security = null,
        public ?string $securityMessage = null,
        public ?string $securityPostDenormalize = null,
        public ?string $securityPostDenormalizeMessage = null,
        public ?bool $compositeIdentifier = null,
        public ?GraphQl $graphQl = null,
        ...$extraProperties
    ) {
        $this->extraProperties = $extraProperties;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string|null
     */
    public function getUriTemplate(): ?string
    {
        return $this->uriTemplate;
    }

    /**
     * @return string|null
     */
    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getFormats(): mixed
    {
        return $this->formats;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getInputFormats(): mixed
    {
        return $this->inputFormats;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getOutputFormats(): mixed
    {
        return $this->outputFormats;
    }

    /**
     * @return array
     */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    /**
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @return string
     */
    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    /**
     * @return string|null
     */
    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    /**
     * @return array
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @return array
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return bool|null
     */
    public function getStateless(): ?bool
    {
        return $this->stateless;
    }

    /**
     * @return string|null
     */
    public function getSunset(): ?string
    {
        return $this->sunset;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return array
     */
    public function getSchemes(): array
    {
        return $this->schemes;
    }

    /**
     * @return string
     */
    public function getCondition(): string
    {
        return $this->condition;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * @return int|null
     */
    public function getUrlGenerationStrategy(): ?int
    {
        return $this->urlGenerationStrategy;
    }

    /**
     * @return bool
     */
    public function isCollection(): bool
    {
        return $this->collection;
    }

    /**
     * @return string|null
     */
    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
    }

    /**
     * @return array
     */
    public function getCacheHeaders(): array
    {
        return $this->cacheHeaders;
    }

    /**
     * @return array
     */
    public function getNormalizationContext(): array
    {
        return $this->normalizationContext;
    }

    /**
     * @return array
     */
    public function getDenormalizationContext(): array
    {
        return $this->denormalizationContext;
    }

    /**
     * @return string[]
     */
    public function getHydraContext(): array
    {
        return $this->hydraContext;
    }

    /**
     * @return array
     */
    public function getOpenapiContext(): array
    {
        return $this->openapiContext;
    }

    /**
     * @return array
     */
    public function getValidationGroups(): array
    {
        return $this->validationGroups;
    }

    /**
     * @return string[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return bool|null
     */
    public function getElasticsearch(): ?bool
    {
        return $this->elasticsearch;
    }

    /**
     * @return array|bool|mixed|null
     */
    public function getMercure(): mixed
    {
        return $this->mercure;
    }

    /**
     * @return bool|mixed|null
     */
    public function getMessenger(): mixed
    {
        return $this->messenger;
    }

    /**
     * @return mixed
     */
    public function getInput(): mixed
    {
        return $this->input;
    }

    /**
     * @return mixed
     */
    public function getOutput(): mixed
    {
        return $this->output;
    }

    /**
     * @return array
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * @return bool|null
     */
    public function getFetchPartial(): ?bool
    {
        return $this->fetchPartial;
    }

    /**
     * @return bool|null
     */
    public function getForceEager(): ?bool
    {
        return $this->forceEager;
    }

    /**
     * @return bool|null
     */
    public function getPaginationClientEnabled(): ?bool
    {
        return $this->paginationClientEnabled;
    }

    /**
     * @return bool|null
     */
    public function getPaginationClientItemsPerPage(): ?bool
    {
        return $this->paginationClientItemsPerPage;
    }

    /**
     * @return bool|null
     */
    public function getPaginationClientPartial(): ?bool
    {
        return $this->paginationClientPartial;
    }

    /**
     * @return array
     */
    public function getPaginationViaCursor(): array
    {
        return $this->paginationViaCursor;
    }

    /**
     * @return bool|null
     */
    public function getPaginationEnabled(): ?bool
    {
        return $this->paginationEnabled;
    }

    /**
     * @return bool|null
     */
    public function getPaginationFetchJoinCollection(): ?bool
    {
        return $this->paginationFetchJoinCollection;
    }

    /**
     * @return int|null
     */
    public function getPaginationItemsPerPage(): ?int
    {
        return $this->paginationItemsPerPage;
    }

    /**
     * @return int|null
     */
    public function getPaginationMaximumItemsPerPage(): ?int
    {
        return $this->paginationMaximumItemsPerPage;
    }

    /**
     * @return bool|null
     */
    public function getPaginationPartial(): ?bool
    {
        return $this->paginationPartial;
    }

    /**
     * @return string|null
     */
    public function getPaginationType(): ?string
    {
        return $this->paginationType;
    }

    /**
     * @return string|null
     */
    public function getSecurity(): ?string
    {
        return $this->security;
    }

    /**
     * @return string|null
     */
    public function getSecurityMessage(): ?string
    {
        return $this->securityMessage;
    }

    /**
     * @return string|null
     */
    public function getSecurityPostDenormalize(): ?string
    {
        return $this->securityPostDenormalize;
    }

    /**
     * @return string|null
     */
    public function getSecurityPostDenormalizeMessage(): ?string
    {
        return $this->securityPostDenormalizeMessage;
    }

    /**
     * @return bool|null
     */
    public function getCompositeIdentifier(): ?bool
    {
        return $this->compositeIdentifier;
    }

    /**
     * @return GraphQl|null
     */
    public function getGraphQl(): ?GraphQl
    {
        return $this->graphQl;
    }

    public function __serialize(): array
    {
        return [
            'stateless' => $this->stateless,
            'identifiers' => $this->identifiers,
            'has_composite_identifier' => $this->compositeIdentifier,
            'normalization_context' => $this->normalizationContext,
            'denormalization_context' => $this->denormalizationContext,
            'collection' => $this->collection,
            'links' => $this->links,
            'uri_template' => $this->uriTemplate,
            'input' => $this->input,
            'output' => $this->output,
            'method' => $this->method,
        ];
    }
}
