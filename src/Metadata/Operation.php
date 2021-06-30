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

use ApiPlatform\State\OptionsInterface;

/**
 * ⚠ This class and its children offer no backward compatibility regarding positional parameters.
 */
abstract class Operation extends Metadata
{
    use WithResourceTrait;

    /**
     * @param bool|null                           $paginationEnabled              {@see https://api-platform.com/docs/core/pagination/#for-a-specific-resource}
     * @param string|null                         $paginationType                 {@see https://api-platform.com/docs/core/graphql/#using-the-page-based-pagination}
     * @param int|null                            $paginationItemsPerPage         {@see https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page}
     * @param int|null                            $paginationMaximumItemsPerPage  {@see https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page}
     * @param bool|null                           $paginationPartial              {@see https://api-platform.com/docs/core/performance/#partial-pagination}
     * @param bool|null                           $paginationClientEnabled        {@see https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1}
     * @param bool|null                           $paginationClientItemsPerPage   {@see https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3}
     * @param bool|null                           $paginationClientPartial        {@see https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6}
     * @param bool|null                           $paginationFetchJoinCollection  {@see https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator}
     * @param array<string, string>|string[]|null $order                          {@see https://api-platform.com/docs/core/default-order/#overriding-default-order}
     * @param string|null                         $security                       {@see https://api-platform.com/docs/core/security}
     * @param string|null                         $securityMessage                {@see https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message}
     * @param string|null                         $securityPostDenormalize        {@see https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization}
     * @param string|null                         $securityPostDenormalizeMessage {@see https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message}
     * @param array|null                          $translation                    {@see https://api-platform.com/docs/core/translation}
     * @param string|null                         $deprecationReason              {@see https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties}
     * @param string[]|null                       $filters                        {@see https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters}
     * @param array{
     *     class?: string|null,
     *     name?: string,
     * }|string|false|null $input {@see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation}
     * @param array{
     *     class?: string|null,
     *     name?: string,
     * }|string|false|null $output {@see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation}
     * @param string|array|bool|null $mercure       {@see https://api-platform.com/docs/core/mercure}
     * @param string|bool|null       $messenger     {@see https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus}
     * @param bool|null              $elasticsearch {@see https://api-platform.com/docs/core/elasticsearch/}
     * @param bool|null              $read          {@see https://api-platform.com/docs/core/events/#the-event-system}
     * @param bool|null              $deserialize   {@see https://api-platform.com/docs/core/events/#the-event-system}
     * @param bool|null              $validate      {@see https://api-platform.com/docs/core/events/#the-event-system}
     * @param bool|null              $write         {@see https://api-platform.com/docs/core/events/#the-event-system}
     * @param bool|null              $serialize     {@see https://api-platform.com/docs/core/events/#the-event-system}
     * @param bool|null              $fetchPartial  {@see https://api-platform.com/docs/core/performance/#fetch-partial}
     * @param bool|null              $forceEager    {@see https://api-platform.com/docs/core/performance/#force-eager}
     * @param string|callable|null   $provider      {@see https://api-platform.com/docs/core/state-providers/#state-providers}
     * @param string|callable|null   $processor     {@see https://api-platform.com/docs/core/state-processors/#state-processors}
     */
    public function __construct(
        protected ?string $shortName = null,
        protected ?string $class = null,
        protected ?bool $paginationEnabled = null,
        protected ?string $paginationType = null,
        protected ?int $paginationItemsPerPage = null,
        protected ?int $paginationMaximumItemsPerPage = null,
        protected ?bool $paginationPartial = null,
        protected ?bool $paginationClientEnabled = null,
        protected ?bool $paginationClientItemsPerPage = null,
        protected ?bool $paginationClientPartial = null,
        protected ?bool $paginationFetchJoinCollection = null,
        protected ?bool $paginationUseOutputWalkers = null,
        protected ?array $order = null,
        protected ?string $description = null,
        protected ?array $normalizationContext = null,
        protected ?array $denormalizationContext = null,
        protected ?bool $collectDenormalizationErrors = null,
        protected ?string $security = null,
        protected ?string $securityMessage = null,
        protected ?string $securityPostDenormalize = null,
        protected ?string $securityPostDenormalizeMessage = null,
        protected ?string $securityPostValidation = null,
        protected ?string $securityPostValidationMessage = null,
        protected ?array $translation = null,
        protected ?string $deprecationReason = null,
        protected ?array $filters = null,
        protected ?array $validationContext = null,
        protected $input = null,
        protected $output = null,
        protected $mercure = null,
        protected $messenger = null,
        protected ?bool $elasticsearch = null,
        protected ?int $urlGenerationStrategy = null,
        protected ?bool $read = null,
        protected ?bool $deserialize = null,
        protected ?bool $validate = null,
        protected ?bool $write = null,
        protected ?bool $serialize = null,
        protected ?bool $fetchPartial = null,
        protected ?bool $forceEager = null,
        protected ?int $priority = null,
        protected ?string $name = null,
        protected $provider = null,
        protected $processor = null,
        protected ?OptionsInterface $stateOptions = null,
        protected array $extraProperties = [],
    ) {
        parent::__construct(
            shortName: $shortName,
            class: $class,
            description: $description,
            urlGenerationStrategy: $urlGenerationStrategy,
            deprecationReason: $deprecationReason,
            normalizationContext: $normalizationContext,
            denormalizationContext: $denormalizationContext,
            collectDenormalizationErrors: $collectDenormalizationErrors,
            validationContext: $validationContext,
            filters: $filters,
            elasticsearch: $elasticsearch,
            mercure: $mercure,
            messenger: $messenger,
            input: $input,
            output: $output,
            order: $order,
            fetchPartial: $fetchPartial,
            forceEager: $forceEager,
            paginationEnabled: $paginationEnabled,
            paginationType: $paginationType,
            paginationItemsPerPage: $paginationItemsPerPage,
            paginationMaximumItemsPerPage: $paginationMaximumItemsPerPage,
            paginationPartial: $paginationPartial,
            paginationClientEnabled: $paginationClientEnabled,
            paginationClientItemsPerPage: $paginationClientItemsPerPage,
            paginationClientPartial: $paginationClientPartial,
            paginationFetchJoinCollection: $paginationFetchJoinCollection,
            paginationUseOutputWalkers: $paginationUseOutputWalkers,
            security: $security,
            securityMessage: $securityMessage,
            securityPostDenormalize: $securityPostDenormalize,
            securityPostDenormalizeMessage: $securityPostDenormalizeMessage,
            securityPostValidation: $securityPostValidation,
            securityPostValidationMessage: $securityPostValidationMessage,
            translation: $translation,
            provider: $provider,
            processor: $processor,
            stateOptions: $stateOptions,
            extraProperties: $extraProperties,
        );
    }

    public function withOperation($operation)
    {
        return $this->copyFrom($operation);
    }

    public function canRead(): ?bool
    {
        return $this->read;
    }

    public function withRead(bool $read = true): self
    {
        $self = clone $this;
        $self->read = $read;

        return $self;
    }

    public function canDeserialize(): ?bool
    {
        return $this->deserialize;
    }

    public function withDeserialize(bool $deserialize = true): self
    {
        $self = clone $this;
        $self->deserialize = $deserialize;

        return $self;
    }

    public function canValidate(): ?bool
    {
        return $this->validate;
    }

    public function withValidate(bool $validate = true): self
    {
        $self = clone $this;
        $self->validate = $validate;

        return $self;
    }

    public function canWrite(): ?bool
    {
        return $this->write;
    }

    public function withWrite(bool $write = true): self
    {
        $self = clone $this;
        $self->write = $write;

        return $self;
    }

    public function canSerialize(): ?bool
    {
        return $this->serialize;
    }

    public function withSerialize(bool $serialize = true): self
    {
        $self = clone $this;
        $self->serialize = $serialize;

        return $self;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function withPriority(int $priority = 0): self
    {
        $self = clone $this;
        $self->priority = $priority;

        return $self;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function withName(string $name = ''): self
    {
        $self = clone $this;
        $self->name = $name;

        return $self;
    }
}
