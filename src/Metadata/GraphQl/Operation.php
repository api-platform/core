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
    protected $resolver;
    protected $args;
    /** @var Link[]|null */
    protected $links;

    /**
     * @param string     $resolver
     * @param mixed|null $input
     * @param mixed|null $output
     * @param mixed|null $mercure
     * @param mixed|null $messenger
     */
    public function __construct(
        ?string $resolver = null,
        ?array $args = null,
        ?array $links = null,

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
        ?bool $paginationViaCursor = null,
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
        ?string $provider = null,
        ?string $processor = null,
        array $extraProperties = []
    ) {
        $this->resolver = $resolver;
        $this->args = $args;
        $this->links = $links;

        // Abstract operation properties
        $this->shortName = $shortName;
        $this->class = $class;
        $this->paginationEnabled = $paginationEnabled;
        $this->paginationType = $paginationType;
        $this->paginationItemsPerPage = $paginationItemsPerPage;
        $this->paginationMaximumItemsPerPage = $paginationMaximumItemsPerPage;
        $this->paginationPartial = $paginationPartial;
        $this->paginationClientEnabled = $paginationClientEnabled;
        $this->paginationClientItemsPerPage = $paginationClientItemsPerPage;
        $this->paginationClientPartial = $paginationClientPartial;
        $this->paginationFetchJoinCollection = $paginationFetchJoinCollection;
        $this->paginationUseOutputWalkers = $paginationUseOutputWalkers;
        $this->paginationViaCursor = $paginationViaCursor;
        $this->order = $order;
        $this->description = $description;
        $this->normalizationContext = $normalizationContext;
        $this->denormalizationContext = $denormalizationContext;
        $this->security = $security;
        $this->securityMessage = $securityMessage;
        $this->securityPostDenormalize = $securityPostDenormalize;
        $this->securityPostDenormalizeMessage = $securityPostDenormalizeMessage;
        $this->securityPostValidation = $securityPostValidation;
        $this->securityPostValidationMessage = $securityPostValidationMessage;
        $this->deprecationReason = $deprecationReason;
        $this->filters = $filters;
        $this->validationContext = $validationContext;
        $this->input = $input;
        $this->output = $output;
        $this->mercure = $mercure;
        $this->messenger = $messenger;
        $this->elasticsearch = $elasticsearch;
        $this->urlGenerationStrategy = $urlGenerationStrategy;
        $this->read = $read;
        $this->deserialize = $deserialize;
        $this->validate = $validate;
        $this->write = $write;
        $this->serialize = $serialize;
        $this->fetchPartial = $fetchPartial;
        $this->forceEager = $forceEager;
        $this->priority = $priority;
        $this->name = $name;
        $this->provider = $provider;
        $this->processor = $processor;
        $this->extraProperties = $extraProperties;
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
    public function getLinks()
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
