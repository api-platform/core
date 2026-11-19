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
use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * QueryParameter-based "color" collection resource mirroring DummyCarColor.
 * Declares its filters via QueryParameter so the nested `colors(prop: ...)` /
 * `colors(price: {gt: ...})` arguments must be derived from getParameters().
 */
#[ApiResource(
    normalizationContext: ['groups' => ['graphql_filtered']],
    graphQlOperations: [
        new Query(),
        new QueryCollection(
            parameters: [
                'prop' => new QueryParameter(filter: new PartialSearchFilter(), property: 'prop'),
                'price' => new QueryParameter(filter: new ComparisonFilter(new ExactFilter()), property: 'price', nativeType: new BuiltinType(TypeIdentifier::INT)),
            ],
        ),
    ],
)]
#[ORM\Entity]
class GraphQlFilteredResourceColor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Serializer\Groups(['graphql_filtered'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GraphQlFilteredResource::class, inversedBy: 'colors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?GraphQlFilteredResource $resource = null;

    #[ORM\Column(type: 'string')]
    #[Serializer\Groups(['graphql_filtered'])]
    private string $prop = '';

    #[ORM\Column(type: 'integer')]
    #[Serializer\Groups(['graphql_filtered'])]
    private int $price = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResource(): ?GraphQlFilteredResource
    {
        return $this->resource;
    }

    public function setResource(?GraphQlFilteredResource $resource): void
    {
        $this->resource = $resource;
    }

    public function getProp(): string
    {
        return $this->prop;
    }

    public function setProp(string $prop): void
    {
        $this->prop = $prop;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }
}
