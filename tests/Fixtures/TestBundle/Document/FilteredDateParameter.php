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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Doctrine\Common\Filter\DateFilterInterface;
use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource]
#[GetCollection(
    paginationItemsPerPage: 5,
    parameters: [
        'createdAt' => new QueryParameter(
            filter: new DateFilter(),
        ),
        'date' => new QueryParameter(
            filter: new DateFilter(),
            property: 'createdAt',
        ),
        'date_include_null_always' => new QueryParameter(
            filter: new DateFilter(),
            property: 'createdAt',
            filterContext: DateFilterInterface::INCLUDE_NULL_BEFORE_AND_AFTER,
        ),
        'date_old_way' => new QueryParameter(
            filter: new DateFilter(properties: ['createdAt' => DateFilterInterface::INCLUDE_NULL_BEFORE_AND_AFTER]),
            property: 'createdAt',
        ),
    ],
)]
#[ODM\Document]
class FilteredDateParameter
{
    public function __construct(
        #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
        public ?int $id = null,

        #[ODM\Field(type: 'date_immutable', nullable: true)]
        public ?\DateTimeImmutable $createdAt = null,
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

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
