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
final class Get extends Operation
{
    public function __construct(
        public ?array $defaults = [],
        public ?array $requirements = [],
        public ?array $options = [],
        public ?string $host = '',
        public ?array $schemes = [],
        public ?string $condition = '',
        public ?string $controller = 'api_platform.action.placeholder',
        public ?string $uriTemplate = null,
        public ?string $shortName = null,
        public ?string $description = null,
        public ?string $class = null,
        public ?array $types = null,

        public ?array $cacheHeaders = null,
        public ?array $denormalizationContext = null,
        public ?string $deprecationReason = null,
        public ?bool $elasticsearch = null,
        public ?bool $fetchPartial = null,
        public ?bool $forceEager = null,
        public mixed $formats = null,
        public mixed $inputFormats = null,
        public mixed $outputFormats = null,
        public ?array $filters = null,
        public ?array $hydraContext = null,
        public mixed $input = null,
        public $mercure = null,
        public $messenger = null,
        public ?array $normalizationContext = null,
        public ?array $openapiContext = null,
        public ?array $order = null,
        public mixed $output = null,
        public ?bool $paginationClientEnabled = null,
        public ?bool $paginationClientItemsPerPage = null,
        public ?bool $paginationClientPartial = null,
        public ?array $paginationViaCursor = null,
        public ?bool $paginationEnabled = null,
        public ?bool $paginationFetchJoinCollection = null,
        public ?int $paginationItemsPerPage = null,
        public ?int $paginationMaximumItemsPerPage = null,
        public ?bool $paginationPartial = null,
        public ?string $routePrefix = null,
        public ?string $security = null,
        public ?string $securityMessage = null,
        public ?string $securityPostDenormalize = null,
        public ?string $securityPostDenormalizeMessage = null,
        public ?bool $stateless = null,
        public ?string $sunset = null,
        public ?array $swaggerContext = null,
        public ?array $validationGroups = null,
        public ?int $urlGenerationStrategy = null,
        public ?bool $compositeIdentifier = null,
        public ?array $identifiers = null,
        public ?array $graphQl = null,
        public bool $collection = false,
        ...$extraProperties
    ) {
        parent::__construct(...\func_get_args());
    }
}
