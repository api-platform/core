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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6041;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [
    new Get(uriTemplate: 'numeric-validated/{id}'),
    new GetCollection(uriTemplate: 'numeric-validated'),
])]
#[ORM\Entity]
class NumericValidated
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[Assert\Range(min: 1, max: 10)]
    #[ORM\Column]
    public int $range;

    #[Assert\GreaterThan(value: 10)]
    #[ORM\Column]
    public int $greaterThanMe;

    #[Assert\GreaterThanOrEqual(value: '10.99')]
    #[ORM\Column]
    public float $greaterThanOrEqualToMe;

    #[Assert\LessThan(value: 99)]
    #[ORM\Column]
    public int $lessThanMe;

    #[Assert\LessThanOrEqual(value: '99.33')]
    #[ORM\Column]
    public float $lessThanOrEqualToMe;

    #[Assert\Positive]
    #[ORM\Column]
    public int $positive;

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    public int $positiveOrZero;

    #[Assert\Negative]
    #[ORM\Column]
    public int $negative;

    #[Assert\NegativeOrZero]
    #[ORM\Column]
    public int $negativeOrZero;

    public function getId(): ?int
    {
        return $this->id;
    }
}
