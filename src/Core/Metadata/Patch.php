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
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Patch extends Operation
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        string $method = self::METHOD_PATCH,
        ?string $uriTemplate = null,
        ?string $shortName = null,
        ?string $description = null,
        array $types = [],
        mixed $formats = null,
        mixed $inputFormats = null,
        mixed $outputFormats = null,
        array $identifiers = [],
        array $links = [],
        string $routePrefix = '',
        ?string $routeName = null,
        array $defaults = [],
        array $requirements = [],
        array $options = [],
        ?bool $stateless = null,
        ?string $sunset = null,
        ?string $acceptPatch = null,
        mixed $status = null,
        string $host = '',
        array $schemes = [],
        string $condition = '',
        string $controller = 'api_platform.action.placeholder',
        ?string $class = null,
        ?int $urlGenerationStrategy = null,
        bool $collection = false,
        ?string $deprecationReason = null,
        array $cacheHeaders = [],
        array $normalizationContext = [],
        array $denormalizationContext = [],
        array $hydraContext = [],
        array $openapiContext = [],
        array $swaggerContext = [],
        array $validationContext = [],
        array $filters = [],
        ?bool $elasticsearch = null,
        mixed $mercure = null,
        mixed $messenger = null,
        mixed $input = null,
        mixed $output = null,
        array $order = [],
        ?bool $fetchPartial = null,
        ?bool $forceEager = null,
        ?bool $paginationClientEnabled = null,
        ?bool $paginationClientItemsPerPage = null,
        ?bool $paginationClientPartial = null,
        array $paginationViaCursor = [],
        ?bool $paginationEnabled = null,
        ?bool $paginationFetchJoinCollection = null,
        ?int $paginationItemsPerPage = null,
        ?int $paginationMaximumItemsPerPage = null,
        ?bool $paginationPartial = null,
        ?string $paginationType = null,
        ?string $security = null,
        ?string $securityMessage = null,
        ?string $securityPostDenormalize = null,
        ?string $securityPostDenormalizeMessage = null,
        ?bool $compositeIdentifier = null,
        ?GraphQl $graphQl = null,
        bool $read = true,
        bool $deserialize = true,
        bool $validate = true,
        bool $write = true,
        bool $serialize = true,
        bool $queryParameterValidate = true,
        int $priority = 0,
        array $extraProperties = [],
    ) {
        parent::__construct(...\func_get_args());
        $this->method = self::METHOD_PATCH;
    }
}
