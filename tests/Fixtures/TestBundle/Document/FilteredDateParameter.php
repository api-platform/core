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
use ApiPlatform\OpenApi\Model\Parameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource]
#[GetCollection(
    paginationItemsPerPage: 5,
    parameters: [
        'createdAt' => new QueryParameter(
            filter: new DateFilter(),
            openApi: new Parameter('createdAt', 'query', allowEmptyValue: true)
        ),
        'date' => new QueryParameter(
            filter: new DateFilter(),
            property: 'createdAt',
            openApi: new Parameter('date', 'query', allowEmptyValue: true)
        ),
        'date_include_null_always' => new QueryParameter(
            filter: new DateFilter(),
            property: 'createdAt',
            filterContext: DateFilterInterface::INCLUDE_NULL_BEFORE_AND_AFTER,
            openApi: new Parameter('date_include_null_always', 'query', allowEmptyValue: true)
        ),
        'date_old_way' => new QueryParameter(
            filter: new DateFilter(properties: ['createdAt' => DateFilterInterface::INCLUDE_NULL_BEFORE_AND_AFTER]),
            property: 'createdAt',
            openApi: new Parameter('date_old_way', 'query', allowEmptyValue: true)
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
