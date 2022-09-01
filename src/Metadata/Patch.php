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

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Patch extends HttpOperation
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
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
        ?array $cacheHeaders = null,

        ?array $hydraContext = null,
        ?array $openapiContext = null,
        ?bool $openapi = null,
        ?array $exceptionToStatus = null,

        ?bool $queryParameterValidationEnabled = null,

        // abstract operation arguments
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
        ?array $paginationViaCursor = null,
        ?array $order = null,
        ?string $description = null,
        ?array $normalizationContext = null,
        ?array $denormalizationContext = null,
        ?string $security = null,
        ?string $securityMessage = null,
        ?string $securityPostDenormalize = null,
        ?string $securityPostDenormalizeMessage = null,
        ?string $securityPostValidation = null,
        ?string $securityPostValidationMessage = null,
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
        array $extraProperties = []
    ) {
        parent::__construct(self::METHOD_PATCH, ...\func_get_args());
    }
}
