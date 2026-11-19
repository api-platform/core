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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\Legacy;

use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use ApiPlatform\Doctrine\Odm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Legacy regression fixture: Date/Range/Exists filters survive into 5.0 (rewritten standalone),
 * so the deprecated #[ApiFilter] attribute declaration must keep working for users. The canonical
 * QueryParameter form lives at Document\Filtered{Date,Range,Exists}Parameter. Remove the
 * #[ApiFilter] coverage here once the attribute is gone (6.0).
 */
#[ApiResource]
#[GetCollection(uriTemplate: 'legacy_filtered_attribute_parameters{._format}')]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(RangeFilter::class, properties: ['quantity'])]
#[ApiFilter(ExistsFilter::class, properties: ['description'])]
#[ODM\Document]
class FilteredAttributeParameter
{
    public function __construct(
        #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
        public ?int $id = null,

        #[ODM\Field(type: 'date_immutable', nullable: true)]
        public ?\DateTimeImmutable $createdAt = null,

        #[ODM\Field(type: 'int', nullable: true)]
        public ?int $quantity = null,

        #[ODM\Field(type: 'string', nullable: true)]
        public ?string $description = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
