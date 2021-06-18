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
        private string $method = self::METHOD_GET,
        private ?string $uriTemplate = null,
        private ?string $shortName = null,
        private ?string $description = null,
        private array $types = [],
        private mixed $formats = null,
        private mixed $inputFormats = null,
        private mixed $outputFormats = null,
        private array $identifiers = [],
        private array $links = [],
        private string $routePrefix = '',
        private ?string $routeName = null,
        private array $defaults = [],
        private array $requirements = [],
        private array $options = [],
        private ?bool $stateless = null,
        private ?string $sunset = null,
        private ?string $acceptPatch = null,
        private mixed $status = null,
        private string $host = '',
        private array $schemes = [],
        private string $condition = '',
        private string $controller = 'api_platform.action.placeholder',
        private ?string $class = null,
        private ?int $urlGenerationStrategy = null,
        private bool $collection = false,
        private ?string $deprecationReason = null,
        private array $cacheHeaders = [],
        private array $normalizationContext = [],
        private array $denormalizationContext = [],
        private array $hydraContext = [],
        private array $openapiContext = [],
        private array $swaggerContext = [],
        // TODO: rename validationContext having ['groups' => []]
        private array $validationGroups = [],
        private array $filters = [],
        private ?bool $elasticsearch = null,
        private mixed $mercure = null,
        private mixed $messenger = null,
        private mixed $input = null,
        private mixed $output = null,
        private array $order = [],
        private ?bool $fetchPartial = null,
        private ?bool $forceEager = null,
        private ?bool $paginationClientEnabled = null,
        private ?bool $paginationClientItemsPerPage = null,
        private ?bool $paginationClientPartial = null,
        private array $paginationViaCursor = [],
        private ?bool $paginationEnabled = null,
        private ?bool $paginationFetchJoinCollection = null,
        private ?int $paginationItemsPerPage = null,
        private ?int $paginationMaximumItemsPerPage = null,
        private ?bool $paginationPartial = null,
        private ?string $paginationType = null,
        private ?string $security = null,
        private ?string $securityMessage = null,
        private ?string $securityPostDenormalize = null,
        private ?string $securityPostDenormalizeMessage = null,
        private ?bool $compositeIdentifier = null,
        private ?GraphQl $graphQl = null,
        private bool $read = true,
        private bool $deserialize = true,
        private bool $validate = true,
        private bool $write = true,
        private bool $serialize = true,
        private int $priority = 0,
        private array $extraProperties = [],
    ) {
        parent::__construct(...\func_get_args());
        $this->method = self::METHOD_PATCH;
    }
}
