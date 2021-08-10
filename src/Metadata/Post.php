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
class Post extends Operation
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        ?string $uriTemplate = null,
        ?string $shortName = null,
        ?string $description = null,
        array $types = [],
        $formats = null,
        $inputFormats = null,
        $outputFormats = null,
        $identifiers = [],
        string $routePrefix = '',
        ?string $routeName = null,
        array $defaults = [],
        array $requirements = [],
        array $options = [],
        ?bool $stateless = null,
        ?string $sunset = null,
        ?string $acceptPatch = null,
        ?int $status = null,
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
        $mercure = null,
        $messenger = null,
        $input = null,
        $output = null,
        array $order = [],
        ?bool $fetchPartial = null,
        ?bool $forceEager = null,
        ?bool $paginationClientEnabled = null,
        ?bool $paginationClientItemsPerPage = null,
        ?bool $paginationClientPartial = null,
        array $paginationViaCursor = [],
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
        ?bool $compositeIdentifier = null,
        array $exceptionToStatus = [],
        ?bool $queryParameterValidationEnabled = null,
        bool $read = true,
        bool $deserialize = true,
        bool $validate = true,
        bool $write = true,
        bool $serialize = true,
        bool $queryParameterValidate = true,
        int $priority = 0,
        string $name = '',
        array $extraProperties = []
    ) {
        parent::__construct(self::METHOD_POST, ...\func_get_args());
        $this->collection = true;
    }
}
