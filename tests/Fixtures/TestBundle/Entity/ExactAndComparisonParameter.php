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

use ApiPlatform\Doctrine\Orm\Filter\ComparisonFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Parameter\OperatorMapQueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(openapi: false)]
#[GetCollection(
    uriTemplate: 'exact_and_comparison_parameter{._format}',
    parameters: [
        'quantity' => new QueryParameter(filter: new ExactFilter(), property: 'quantity'),
        'quantityComparison' => new OperatorMapQueryParameter(key: 'quantity', filter: new ComparisonFilter(new ExactFilter()), property: 'quantity'),
    ]
)]
#[ORM\Entity]
class ExactAndComparisonParameter
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $quantity = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }
}
