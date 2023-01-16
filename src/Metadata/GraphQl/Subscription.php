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
final class Subscription extends Operation
{
    public function __construct(
        ?string $resolver = null,
        ?array $args = null,
        ?array $links = null,

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
        array $extraProperties = [],
    ) {
        parent::__construct(
            $resolver,
            $args,
            $links,
            $shortName,
            $class,
            $paginationEnabled,
            $paginationType,
            $paginationItemsPerPage,
            $paginationMaximumItemsPerPage,
            $paginationPartial,
            $paginationClientEnabled,
            $paginationClientItemsPerPage,
            $paginationClientPartial,
            $paginationFetchJoinCollection,
            $paginationUseOutputWalkers,
            $order,
            $description,
            $normalizationContext,
            $denormalizationContext,
            $security,
            $securityMessage,
            $securityPostDenormalize,
            $securityPostDenormalizeMessage,
            $securityPostValidation,
            $securityPostValidationMessage,
            $deprecationReason,
            $filters,
            $validationContext,
            $input,
            $output,
            $mercure,
            $messenger,
            $elasticsearch,
            $urlGenerationStrategy,
            $read,
            $deserialize,
            $validate,
            $write,
            $serialize,
            $fetchPartial,
            $forceEager,
            $priority,
            $name ?: 'update_subscription',
            $provider,
            $processor,
            $extraProperties,
        );
    }
}
