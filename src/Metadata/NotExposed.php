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

/**
 * A NotExposed operation is an operation declared for internal usage,
 * for example to generate an IRI on a resource without item operations.
 * It is ignored from OpenApi documentation and must return a HTTP 404.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class NotExposed extends HttpOperation
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        string $method = 'GET',
        string $uriTemplate = null,
        array $types = null,
        $formats = null,
        $inputFormats = null,
        $outputFormats = null,
        $uriVariables = null,
        string $routePrefix = null,
        string $routeName = null,
        array $defaults = null,
        array $requirements = null,
        array $options = null,
        bool $stateless = null,
        string $sunset = null,
        string $acceptPatch = null,
        $status = 404,
        string $host = null,
        array $schemes = null,
        string $condition = null,
        ?string $controller = 'api_platform.action.not_exposed',
        array $cacheHeaders = null,
        array $paginationViaCursor = null,

        array $hydraContext = null,
        array $openapiContext = null,
        bool|OpenApiOperation|null $openapi = false,
        array $exceptionToStatus = null,

        bool $queryParameterValidationEnabled = null,
        array $links = null,

        string $shortName = null,
        string $class = null,
        bool $paginationEnabled = null,
        string $paginationType = null,
        int $paginationItemsPerPage = null,
        int $paginationMaximumItemsPerPage = null,
        bool $paginationPartial = null,
        bool $paginationClientEnabled = null,
        bool $paginationClientItemsPerPage = null,
        bool $paginationClientPartial = null,
        bool $paginationFetchJoinCollection = null,
        bool $paginationUseOutputWalkers = null,
        array $order = null,
        string $description = null,
        array $normalizationContext = null,
        array $denormalizationContext = null,
        bool $collectDenormalizationErrors = null,
        string $security = null,
        string $securityMessage = null,
        string $securityPostDenormalize = null,
        string $securityPostDenormalizeMessage = null,
        string $securityPostValidation = null,
        string $securityPostValidationMessage = null,
        string $deprecationReason = null,
        array $filters = null,
        array $validationContext = null,
        $input = null,
        $output = false,
        $mercure = null,
        $messenger = null,
        bool $elasticsearch = null,
        int $urlGenerationStrategy = null,
        ?bool $read = false,
        bool $deserialize = null,
        bool $validate = null,
        bool $write = null,
        bool $serialize = null,
        bool $fetchPartial = null,
        bool $forceEager = null,
        int $priority = null,
        string $name = null,
        $provider = null,
        $processor = null,
        array $extraProperties = [],
        OptionsInterface $stateOptions = null,
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
            cacheHeaders: $cacheHeaders,
            paginationViaCursor: $paginationViaCursor,
            hydraContext: $hydraContext,
            openapiContext: $openapiContext,
            openapi: $openapi,
            exceptionToStatus: $exceptionToStatus,
            queryParameterValidationEnabled: $queryParameterValidationEnabled,
            links: $links,
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
            extraProperties: $extraProperties,
        );
    }
}
