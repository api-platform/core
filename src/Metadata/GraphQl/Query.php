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

use ApiPlatform\Metadata\PersistenceMeansInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Query extends Operation
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        protected ?string $resolver = null,
        protected ?array $args = null,
        protected ?array $links = null,

        // abstract operation arguments
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
        protected mixed $input = null,
        protected mixed $output = null,
        /** @var array|bool|string|null $mercure */
        protected $mercure = null,
        /** @var bool|string|null $messenger */
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
        /** @var (callable(): mixed)|string|null $provider */
        protected $provider = null,
        /** @var (callable(): mixed)|string|null $processor */
        protected $processor = null,
        protected array $extraProperties = [],
        protected ?bool $nested = null,
        protected ?PersistenceMeansInterface $persistenceMeans = null,
    ) {
        $this->name = $name ?: 'item_query';
    }

    public function getNested(): ?bool
    {
        return $this->nested;
    }

    public function withNested(?bool $nested = null): self
    {
        $self = clone $this;
        $self->nested = $nested;

        return $self;
    }
}
