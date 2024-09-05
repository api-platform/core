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

namespace ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use Doctrine\ORM\Mapping as ORM;

#[ApiFilter(DateFilter::class, strategy: DateFilter::EXCLUDE_NULL)]
#[ApiFilter(BooleanFilter::class)]
#[ApiFilter(PropertyFilter::class, arguments: ['parameterName' => 'foobar'])]
#[ApiFilter(GroupFilter::class, arguments: ['parameterName' => 'foobargroups'])]
#[ApiFilter(GroupFilter::class, arguments: ['parameterName' => 'foobargroups_override'], id: 'override')]
#[ApiResource(operations: [new Get(openapi: new OpenApiOperation(tags: [])), new Put(), new Delete(), new Post(), new GetCollection()], sunset: '2050-01-01', normalizationContext: ['groups' => ['colors']])]
#[ORM\Entity]
class DummyCar
{
    #[ORM\Id]
    public int $id;

    #[ApiFilter(SearchFilter::class, strategy: 'exact')]

    #[ApiFilter(SearchFilter::class, strategy: 'partial')]
    #[ORM\Column(type: 'string')]
    private string $name;
    #[ORM\Column(type: 'boolean')]
    private bool $canSell;
    #[ORM\Column(type: 'datetime')]
    private \DateTime $availableAt;
    #[ORM\Column]
    private string $brand = 'DummyBrand';

    public function __construct()
    {
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCanSell(): bool
    {
        return $this->canSell;
    }

    public function setCanSell(bool $canSell): void
    {
        $this->canSell = $canSell;
    }

    public function getAvailableAt(): \DateTime
    {
        return $this->availableAt;
    }

    public function setAvailableAt(\DateTime $availableAt): void
    {
        $this->availableAt = $availableAt;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }
}
