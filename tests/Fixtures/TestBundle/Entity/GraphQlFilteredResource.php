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
use ApiPlatform\Doctrine\Orm\Filter\SortFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Parameter-based (QueryParameter) mirror of the DummyCar <-> DummyCarColor relationship,
 * used to prove GraphQL filter-argument parity between the canonical
 * Operation::getParameters() path and the legacy Operation::getFilters() path.
 */
#[ApiResource(
    normalizationContext: ['groups' => ['graphql_filtered']],
    graphQlOperations: [
        new Query(),
        new QueryCollection(
            parameters: [
                'name' => new QueryParameter(filter: new ExactFilter()),
                'colors.prop' => new QueryParameter(filter: new PartialSearchFilter(), property: 'colors.prop'),
                'colors.price' => new QueryParameter(filter: new ComparisonFilter(new ExactFilter()), property: 'colors.price', nativeType: new BuiltinType(TypeIdentifier::INT)),
                'order[:property]' => new QueryParameter(filter: new SortFilter()),
            ],
        ),
    ],
)]
#[ORM\Entity]
class GraphQlFilteredResource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Serializer\Groups(['graphql_filtered'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    #[Serializer\Groups(['graphql_filtered'])]
    private string $name = '';

    /**
     * @var Collection<int, GraphQlFilteredResourceColor>
     */
    #[ORM\OneToMany(targetEntity: GraphQlFilteredResourceColor::class, mappedBy: 'resource')]
    #[Serializer\Groups(['graphql_filtered'])]
    private Collection $colors;

    public function __construct()
    {
        $this->colors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection<int, GraphQlFilteredResourceColor>
     */
    public function getColors(): Collection
    {
        return $this->colors;
    }

    public function setColors(Collection $colors): void
    {
        $this->colors = $colors;
    }

    public function addColor(GraphQlFilteredResourceColor $color): void
    {
        if (!$this->colors->contains($color)) {
            $this->colors->add($color);
            $color->setResource($this);
        }
    }
}
