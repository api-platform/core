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

abstract class Operation
{
    use WithResourceTrait;
    /**
     * @var mixed|null
     */
    protected mixed $input;
    /**
     * @var mixed|null
     */
    protected mixed $output;
    /**
     * @var string|callable|null
     */
    protected $provider;
    /**
     * @var string|callable|null
     */
    protected $processor;

    /**
     * @param string[]   $filters
     * @param mixed|null $input
     * @param mixed|null $output
     * @param mixed|null $provider
     * @param mixed|null $processor
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
        protected ?array $paginationViaCursor = null,
        protected ?array $order = null,
        protected ?string $description = null,
        protected ?array $normalizationContext = null,
        protected ?array $denormalizationContext = null,
        protected ?string $security = null,
        protected ?string $securityMessage = null,
        protected ?string $securityPostDenormalize = null,
        protected ?string $securityPostDenormalizeMessage = null,
        protected ?string $securityPostValidation = null,
        protected ?string $securityPostValidationMessage = null,
        protected ?string $deprecationReason = null,
        protected ?array $filters = null,
        protected ?array $validationContext = null,
        $input = null,
        $output = null,
        /**
         * @var string|array|bool|null
         */
        protected $mercure = null,
        /**
         * @var string|bool|null
         */
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
        $provider = null,
        $processor = null,
        protected array $extraProperties = []
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->provider = $provider;
        $this->processor = $processor;
    }

    public function withOperation($operation)
    {
        return $this->copyFrom($operation);
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function withShortName(?string $shortName = null): self
    {
        $self = clone $this;
        $self->shortName = $shortName;

        return $self;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function withClass(?string $class = null): self
    {
        $self = clone $this;
        $self->class = $class;

        return $self;
    }

    public function getPaginationEnabled(): ?bool
    {
        return $this->paginationEnabled;
    }

    public function withPaginationEnabled(?bool $paginationEnabled = null): self
    {
        $self = clone $this;
        $self->paginationEnabled = $paginationEnabled;

        return $self;
    }

    public function getPaginationType(): ?string
    {
        return $this->paginationType;
    }

    public function withPaginationType(?string $paginationType = null): self
    {
        $self = clone $this;
        $self->paginationType = $paginationType;

        return $self;
    }

    public function getPaginationItemsPerPage(): ?int
    {
        return $this->paginationItemsPerPage;
    }

    public function withPaginationItemsPerPage(?int $paginationItemsPerPage = null): self
    {
        $self = clone $this;
        $self->paginationItemsPerPage = $paginationItemsPerPage;

        return $self;
    }

    public function getPaginationMaximumItemsPerPage(): ?int
    {
        return $this->paginationMaximumItemsPerPage;
    }

    public function withPaginationMaximumItemsPerPage(?int $paginationMaximumItemsPerPage = null): self
    {
        $self = clone $this;
        $self->paginationMaximumItemsPerPage = $paginationMaximumItemsPerPage;

        return $self;
    }

    public function getPaginationPartial(): ?bool
    {
        return $this->paginationPartial;
    }

    public function withPaginationPartial(?bool $paginationPartial = null): self
    {
        $self = clone $this;
        $self->paginationPartial = $paginationPartial;

        return $self;
    }

    public function getPaginationClientEnabled(): ?bool
    {
        return $this->paginationClientEnabled;
    }

    public function withPaginationClientEnabled(?bool $paginationClientEnabled = null): self
    {
        $self = clone $this;
        $self->paginationClientEnabled = $paginationClientEnabled;

        return $self;
    }

    public function getPaginationClientItemsPerPage(): ?bool
    {
        return $this->paginationClientItemsPerPage;
    }

    public function withPaginationClientItemsPerPage(?bool $paginationClientItemsPerPage = null): self
    {
        $self = clone $this;
        $self->paginationClientItemsPerPage = $paginationClientItemsPerPage;

        return $self;
    }

    public function getPaginationClientPartial(): ?bool
    {
        return $this->paginationClientPartial;
    }

    public function withPaginationClientPartial(?bool $paginationClientPartial = null): self
    {
        $self = clone $this;
        $self->paginationClientPartial = $paginationClientPartial;

        return $self;
    }

    public function getPaginationFetchJoinCollection(): ?bool
    {
        return $this->paginationFetchJoinCollection;
    }

    public function withPaginationFetchJoinCollection(?bool $paginationFetchJoinCollection = null): self
    {
        $self = clone $this;
        $self->paginationFetchJoinCollection = $paginationFetchJoinCollection;

        return $self;
    }

    public function getPaginationUseOutputWalkers(): ?bool
    {
        return $this->paginationUseOutputWalkers;
    }

    public function withPaginationUseOutputWalkers(?bool $paginationUseOutputWalkers = null): self
    {
        $self = clone $this;
        $self->paginationUseOutputWalkers = $paginationUseOutputWalkers;

        return $self;
    }

    public function getPaginationViaCursor(): ?array
    {
        return $this->paginationViaCursor;
    }

    public function withPaginationViaCursor(array $paginationViaCursor): self
    {
        $self = clone $this;
        $self->paginationViaCursor = $paginationViaCursor;

        return $self;
    }

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function withOrder(array $order = []): self
    {
        $self = clone $this;
        $self->order = $order;

        return $self;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(?string $description = null): self
    {
        $self = clone $this;
        $self->description = $description;

        return $self;
    }

    public function getNormalizationContext(): ?array
    {
        return $this->normalizationContext;
    }

    public function withNormalizationContext(array $normalizationContext = []): self
    {
        $self = clone $this;
        $self->normalizationContext = $normalizationContext;

        return $self;
    }

    public function getDenormalizationContext(): ?array
    {
        return $this->denormalizationContext;
    }

    public function withDenormalizationContext(array $denormalizationContext = []): self
    {
        $self = clone $this;
        $self->denormalizationContext = $denormalizationContext;

        return $self;
    }

    public function getSecurity(): ?string
    {
        return $this->security;
    }

    public function withSecurity(?string $security = null): self
    {
        $self = clone $this;
        $self->security = $security;

        return $self;
    }

    public function getSecurityMessage(): ?string
    {
        return $this->securityMessage;
    }

    public function withSecurityMessage(?string $securityMessage = null): self
    {
        $self = clone $this;
        $self->securityMessage = $securityMessage;

        return $self;
    }

    public function getSecurityPostDenormalize(): ?string
    {
        return $this->securityPostDenormalize;
    }

    public function withSecurityPostDenormalize(?string $securityPostDenormalize = null): self
    {
        $self = clone $this;
        $self->securityPostDenormalize = $securityPostDenormalize;

        return $self;
    }

    public function getSecurityPostDenormalizeMessage(): ?string
    {
        return $this->securityPostDenormalizeMessage;
    }

    public function withSecurityPostDenormalizeMessage(?string $securityPostDenormalizeMessage = null): self
    {
        $self = clone $this;
        $self->securityPostDenormalizeMessage = $securityPostDenormalizeMessage;

        return $self;
    }

    public function getSecurityPostValidation(): ?string
    {
        return $this->securityPostValidation;
    }

    public function withSecurityPostValidation(?string $securityPostValidation = null): self
    {
        $self = clone $this;
        $self->securityPostValidation = $securityPostValidation;

        return $self;
    }

    public function getSecurityPostValidationMessage(): ?string
    {
        return $this->securityPostValidationMessage;
    }

    public function withSecurityPostValidationMessage(?string $securityPostValidationMessage = null): self
    {
        $self = clone $this;
        $self->securityPostValidationMessage = $securityPostValidationMessage;

        return $self;
    }

    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
    }

    public function withDeprecationReason(?string $deprecationReason = null): self
    {
        $self = clone $this;
        $self->deprecationReason = $deprecationReason;

        return $self;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function withFilters(array $filters = []): self
    {
        $self = clone $this;
        $self->filters = $filters;

        return $self;
    }

    public function getValidationContext(): ?array
    {
        return $this->validationContext;
    }

    public function withValidationContext(array $validationContext = []): self
    {
        $self = clone $this;
        $self->validationContext = $validationContext;

        return $self;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function withInput($input = null): self
    {
        $self = clone $this;
        $self->input = $input;

        return $self;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function withOutput($output = null): self
    {
        $self = clone $this;
        $self->output = $output;

        return $self;
    }

    /**
     * @return bool|string|array|null
     */
    public function getMercure()
    {
        return $this->mercure;
    }

    /**
     * @param bool|string|array|null $mercure
     *
     * @return $this
     */
    public function withMercure($mercure = null): self
    {
        $self = clone $this;
        $self->mercure = $mercure;

        return $self;
    }

    /**
     * @return bool|string|null
     */
    public function getMessenger()
    {
        return $this->messenger;
    }

    /**
     * @param bool|string|null $messenger
     *
     * @return $this
     */
    public function withMessenger($messenger = null): self
    {
        $self = clone $this;
        $self->messenger = $messenger;

        return $self;
    }

    public function getElasticsearch(): ?bool
    {
        return $this->elasticsearch;
    }

    public function withElasticsearch(?bool $elasticsearch = null): self
    {
        $self = clone $this;
        $self->elasticsearch = $elasticsearch;

        return $self;
    }

    public function getUrlGenerationStrategy(): ?int
    {
        return $this->urlGenerationStrategy;
    }

    public function withUrlGenerationStrategy(?int $urlGenerationStrategy = null): self
    {
        $self = clone $this;
        $self->urlGenerationStrategy = $urlGenerationStrategy;

        return $self;
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

    public function getFetchPartial(): ?bool
    {
        return $this->fetchPartial;
    }

    public function withFetchPartial(?bool $fetchPartial = null): self
    {
        $self = clone $this;
        $self->fetchPartial = $fetchPartial;

        return $self;
    }

    public function getForceEager(): ?bool
    {
        return $this->forceEager;
    }

    public function withForceEager(?bool $forceEager = null): self
    {
        $self = clone $this;
        $self->forceEager = $forceEager;

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

    public function getProcessor(): callable|string|null
    {
        return $this->processor;
    }

    public function withProcessor(callable|string|null $processor): self
    {
        $self = clone $this;
        $self->processor = $processor;

        return $self;
    }

    public function getProvider(): callable|string|null
    {
        return $this->provider;
    }

    public function withProvider(callable|string|null $provider): self
    {
        $self = clone $this;
        $self->provider = $provider;

        return $self;
    }

    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    public function withExtraProperties(array $extraProperties = []): self
    {
        $self = clone $this;
        $self->extraProperties = $extraProperties;

        return $self;
    }
}
