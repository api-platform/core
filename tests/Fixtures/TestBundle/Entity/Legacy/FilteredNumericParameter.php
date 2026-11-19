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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Legacy;

use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

/**
 * Legacy regression fixture: keeps the deprecated NumericFilter alive until 6.0.
 * The canonical replacement (ExactFilter + numeric nativeType) lives at
 * Entity\FilteredNumericParameter.
 */
#[ApiResource]
#[GetCollection(
    uriTemplate: 'legacy_filtered_numeric_parameters{._format}',
    paginationItemsPerPage: 5,
    parameters: [
        'quantity' => new QueryParameter(
            filter: new NumericFilter(),
        ),
        'amount' => new QueryParameter(
            filter: new NumericFilter(),
            property: 'quantity',
        ),
        'ratio' => new QueryParameter(
            filter: new NumericFilter(),
        ),
    ],
)]
#[ORM\Entity]
class FilteredNumericParameter
{
    public function __construct(
        #[ORM\Column]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'AUTO')]
        public ?int $id = null,

        #[ORM\Column(nullable: true)]
        public ?int $quantity = null,

        #[ORM\Column(nullable: true)]
        public ?float $ratio = null,
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

    public function getRatio(): ?float
    {
        return $this->ratio;
    }

    public function setRatio(?float $ratio): void
    {
        $this->ratio = $ratio;
    }
}
