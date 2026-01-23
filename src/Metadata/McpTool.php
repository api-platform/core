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

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class McpTool extends HttpOperation
{
    /**
     * @param string|null                                    $name              The name of the tool (defaults to the method name)
     * @param string|null                                    $description       The description of the tool (defaults to the DocBlock/inferred)
     * @param bool|null                                      $structuredContent Whether to include structured content in the response (defaults to true)
     * @param mixed|null                                     $annotations       Optional annotations describing tool behavior
     * @param array|null                                     $icons             Optional list of icon URLs representing the tool
     * @param array<string, mixed>|null                      $meta              Optional metadata
     * @param string[]|null                                  $types             the RDF types of this property
     * @param array<int|string, string|string[]>|string|null $formats           {@see https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation}
     * @param array<int|string, string|string[]>|string|null $inputFormats      {@see https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation}
     * @param array<int|string, string|string[]>|string|null $outputFormats     {@see https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation}
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
        ?string $name = null,
        ?string $description = null,
        protected ?bool $structuredContent = null,
        protected mixed $annotations = null,
        protected ?array $icons = null,
        protected ?array $meta = null,

        string $method = self::METHOD_GET,
        ?string $uriTemplate = null,
        ?array $types = null,
        $formats = null,
        $inputFormats = null,
        $outputFormats = null,
        $uriVariables = null,
        ?string $routePrefix = null,
        ?string $routeName = null,
        ?array $defaults = null,
        ?array $requirements = null,
        ?array $options = null,
        ?bool $stateless = null,
        ?string $sunset = null,
        ?string $acceptPatch = null,
        $status = null,
        ?string $host = null,
        ?array $schemes = null,
        ?string $condition = null,
        ?string $controller = null,
        ?array $headers = null,
        ?array $cacheHeaders = null,
        ?array $paginationViaCursor = null,
        ?array $hydraContext = null,
        bool|OpenApiOperation|Webhook|null $openapi = null,
        ?array $exceptionToStatus = null,
        ?array $links = null,
        ?array $errors = null,
        ?bool $strictQueryParameterValidation = null,
        ?bool $hideHydraOperation = null,

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
        $provider = null,
        $processor = null,
        ?OptionsInterface $stateOptions = null,
        ?Parameters $parameters = null,
        array|string|null $rules = null,
        ?string $policy = null,
        array|string|null $middleware = null,
        ?bool $queryParameterValidationEnabled = null,
        ?bool $jsonStream = null,
        array $extraProperties = [],
        ?bool $map = null,
    ) {
        parent::__construct(
            method: $method,
            uriTemplate: $uriTemplate,
            types: $types,
            formats: $formats,
            inputFormats: $inputFormats,
            outputFormats: $outputFormats,
            uriVariables: $uriVariables,
            routePrefix: $routePrefix,
            routeName: $routeName,
            defaults: $defaults,
            requirements: $requirements,
            options: $options,
            stateless: $stateless,
            sunset: $sunset,
            acceptPatch: $acceptPatch,
            status: $status,
            host: $host,
            schemes: $schemes,
            condition: $condition,
            controller: $controller,
            headers: $headers,
            cacheHeaders: $cacheHeaders,
            paginationViaCursor: $paginationViaCursor,
            hydraContext: $hydraContext,
            openapi: $openapi,
            exceptionToStatus: $exceptionToStatus,
            links: $links,
            errors: $errors,
            strictQueryParameterValidation: $strictQueryParameterValidation,
            hideHydraOperation: $hideHydraOperation,
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
            jsonStream: $jsonStream,
            extraProperties: $extraProperties,
            map: $map,
        );
    }

    public function getAnnotations(): mixed
    {
        return $this->annotations;
    }

    public function withAnnotations(mixed $annotations): static
    {
        $self = clone $this;
        $self->annotations = $annotations;

        return $self;
    }

    public function getIcons(): ?array
    {
        return $this->icons;
    }

    public function withIcons(?array $icons): static
    {
        $self = clone $this;
        $self->icons = $icons;

        return $self;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    public function withMeta(?array $meta): static
    {
        $self = clone $this;
        $self->meta = $meta;

        return $self;
    }

    public function getStructuredContent(): ?bool
    {
        return $this->structuredContent;
    }

    public function withStructuredContent(?bool $structuredContent): static
    {
        $self = clone $this;
        $self->structuredContent = $structuredContent;

        return $self;
    }
}
