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

final class Operation
{
    use AttributeTrait;

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';

    /**
     * @readonly string       $method
     * @readonly string       $uriTemplate
     * @readonly string       $shortName
     * @readonly string       $description
     * @readonly array        $types
     * @readonly array|string $formats                        https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @readonly array|string $inputFormats                   https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @readonly array|string $outputFormats                  https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @readonly array        $identifiers
     * @readonly array        $links
     * @readonly string       $routePrefix                    https://api-platform.com/docs/core/operations/#prefixing-all-routes-of-all-operations
     * @readonly array        $defaults
     * @readonly array        $requirements
     * @readonly array        $options
     * @readonly bool         $stateless
     * @readonly string       $sunset                         https://api-platform.com/docs/core/deprecations/#setting-the-sunset-http-header-to-indicate-when-a-resource-or-an-operation-will-be-removed
     * @readonly string       $host
     * @readonly array        $schemes
     * @readonly string       $condition
     * @readonly string       $controller
     * @readonly string       $class
     * @readonly int          $urlGenerationStrategy
     * @readonly bool         $collection
     * @readonly string       $deprecationReason              https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @readonly array        $cacheHeaders                   https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers
     * @readonly array        $normalizationContext           https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @readonly array        $denormalizationContext         https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @readonly string[]     $hydraContext                   https://api-platform.com/docs/core/extending-jsonld-context/#hydra
     * @readonly array        $openapiContext                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @readonly array        $validationGroups               https://api-platform.com/docs/core/validation/#using-validation-groups
     * @readonly string[]     $filters                        https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters
     * @readonly bool         $elasticsearch                  https://api-platform.com/docs/core/elasticsearch/
     * @readonly bool|array   $mercure                        https://api-platform.com/docs/core/mercure
     * @readonly bool         $messenger                      https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus
     * @readonly mixed        $input                          https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @readonly mixed        $output                         https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @readonly array        $order                          https://api-platform.com/docs/core/default-order/#overriding-default-order
     *
     * @readonly bool         $fetchPartial                   https://api-platform.com/docs/core/performance/#fetch-partial
     * @readonly bool         $forceEager                     https://api-platform.com/docs/core/performance/#force-eager
     * @readonly bool         $paginationClientEnabled        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1
     * @readonly bool         $paginationClientItemsPerPage   https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3
     * @readonly bool         $paginationClientPartial        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6
     * @readonly array        $paginationViaCursor            https://api-platform.com/docs/core/pagination/#cursor-based-pagination
     * @readonly bool         $paginationEnabled              https://api-platform.com/docs/core/pagination/#for-a-specific-resource
     * @readonly bool         $paginationFetchJoinCollection  https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator
     * @readonly int          $paginationItemsPerPage         https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page
     * @readonly int          $paginationMaximumItemsPerPage  https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page
     * @readonly bool         $paginationPartial              https://api-platform.com/docs/core/performance/#partial-pagination
     * @readonly string       $paginationType                 https://api-platform.com/docs/core/graphql/#using-the-page-based-pagination
     * @readonly string       $security                       https://api-platform.com/docs/core/security
     * @readonly string       $securityMessage                https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @readonly string       $securityPostDenormalize        https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @readonly string       $securityPostDenormalizeMessage https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @readonly bool         $compositeIdentifier
     * @readonly array        $graphQl
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
          'output' => $this->output
        ];
    }
}
