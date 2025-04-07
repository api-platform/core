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

use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource]
#[GetCollection(
    paginationItemsPerPage: 5,
    parameters: [
        'quantity' => new QueryParameter(
            filter: new RangeFilter(),
            openApi: new Parameter('quantity', 'query', allowEmptyValue: true)
        ),
        'amount' => new QueryParameter(
            filter: new RangeFilter(),
            property: 'quantity',
            openApi: new Parameter('amount', 'query', allowEmptyValue: true)
        ),
    ],
)]
#[ODM\Document]
class FilteredRangeParameter
{
    public function __construct(
        #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
        public ?int $id = null,

        #[ODM\Field(type: 'int', nullable: true)]
        public ?int $quantity = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }
}
