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

namespace ApiPlatform\Metadata\GraphQl;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Mutation extends Operation
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        ?string $resolver = null,
        bool $collection = false,
        ?array $args = null,
        ?string $shortName = null,
        ?string $class = null,
        $identifiers = [],
        ?bool $compositeIdentifier = null,
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
        array $order = [],
        ?string $description = null,
        array $normalizationContext = [],
        array $denormalizationContext = [],
        ?string $security = null,
        ?string $securityMessage = null,
        ?string $securityPostDenormalize = null,
        ?string $securityPostDenormalizeMessage = null,
        ?string $deprecationReason = null,
        array $filters = [],
        array $validationContext = [],
        $input = null,
        $output = null,
        $mercure = null,
        $messenger = null,
        ?bool $elasticsearch = null,
        ?int $urlGenerationStrategy = null,
        bool $read = true,
        bool $deserialize = true,
        bool $validate = true,
        bool $write = true,
        bool $serialize = true,
        ?bool $fetchPartial = null,
        ?bool $forceEager = null,
        int $priority = 0,
        string $name = '',
        array $extraProperties = []
    ) {
        parent::__construct(...\func_get_args());
    }
}
