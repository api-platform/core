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

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation as AbstractOperation;

class Operation extends AbstractOperation
{
    /**
     * @param Link[]|null $links
     * @param array{
     *     class?: string|null,
     *     name?: string,
     * }|string|false|null $input {@see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation}
     * @param array{
     *     class?: string|null,
     *     name?: string,
     * }|string|false|null $output {@see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation}
     * @param string|array|bool|null $mercure   {@see https://api-platform.com/docs/core/mercure}
     * @param string|bool|null       $messenger {@see https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus}
     * @param string|callable|null   $provider  {@see https://api-platform.com/docs/core/state-providers/#state-providers}
     * @param string|callable|null   $processor {@see https://api-platform.com/docs/core/state-processors/#state-processors}
     */
    public function __construct(
        protected ?string $resolver = null,
        protected ?array $args = null,
        protected ?array $links = null,

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
        array $extraProperties = []
    ) {
        parent::__construct(
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
            $name,
            $provider,
            $processor,
            $extraProperties
        );
    }

    public function getResolver(): ?string
    {
        return $this->resolver;
    }

    public function withResolver(?string $resolver = null): self
    {
        $self = clone $this;
        $self->resolver = $resolver;

        return $self;
    }

    public function getArgs(): ?array
    {
        return $this->args;
    }

    public function withArgs(?array $args = null): self
    {
        $self = clone $this;
        $self->args = $args;

        return $self;
    }

    /**
     * @return Link[]|null
     */
    public function getLinks(): ?array
    {
        return $this->links;
    }

    /**
     * @param Link[] $links
     */
    public function withLinks(array $links): self
    {
        $self = clone $this;
        $self->links = $links;

        return $self;
    }
}
