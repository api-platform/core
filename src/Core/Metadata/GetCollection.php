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
class GetCollection extends Operation
{
    /**
     * {@inheritdoc}
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
        private ?string $status = null,
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
        public array $swaggerContext = [],
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
        private bool $read = true,
        private bool $deserialize = true,
        private bool $validate = true,
        private bool $write = true,
        private bool $serialize = true,
        private int $priority = 0,
        private array $extraProperties = []
    ) {
        parent::__construct(...\func_get_args());
        $this->collection = true;
    }
}
