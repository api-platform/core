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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[ApiResource]
#[GetCollection(
    paginationItemsPerPage: 5,
    parameters: [
        'createdAt' => new QueryParameter(
            filter: new OrderFilter(),
            nativeType: new BuiltinType(TypeIdentifier::STRING)
        ),
        'date' => new QueryParameter(
            filter: new OrderFilter(),
            property: 'createdAt',
            nativeType: new BuiltinType(TypeIdentifier::STRING)
        ),
        'date_null_always_first' => new QueryParameter(
            filter: new OrderFilter(),
            property: 'createdAt',
            filterContext: OrderFilterInterface::NULLS_ALWAYS_FIRST,
            nativeType: new BuiltinType(TypeIdentifier::STRING)
        ),
        'date_null_always_first_old_way' => new QueryParameter(
            filter: new OrderFilter(properties: ['createdAt' => OrderFilterInterface::NULLS_ALWAYS_FIRST]),
            property: 'createdAt',
            nativeType: new BuiltinType(TypeIdentifier::STRING)
        ),
        'order[:property]' => new QueryParameter(
            filter: new OrderFilter(),
            filterContext: OrderFilterInterface::NULLS_ALWAYS_FIRST,
        ),
    ],
)]
#[ORM\Entity]
class FilteredOrderParameter
{
    public function __construct(
        #[ORM\Column]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'AUTO')]
        public ?int $id = null,

        #[ORM\Column(nullable: true)]
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
