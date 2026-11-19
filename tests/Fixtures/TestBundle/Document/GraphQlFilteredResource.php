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

use ApiPlatform\Doctrine\Odm\Filter\ComparisonFilter;
use ApiPlatform\Doctrine\Odm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Odm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Odm\Filter\SortFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * ODM mirror of the QueryParameter-based GraphQL parity fixture.
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
#[ODM\Document]
class GraphQlFilteredResource
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    #[Serializer\Groups(['graphql_filtered'])]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    #[Serializer\Groups(['graphql_filtered'])]
    private string $name = '';

    /**
     * @var Collection<int, GraphQlFilteredResourceColor>
     */
    #[ODM\ReferenceMany(targetDocument: GraphQlFilteredResourceColor::class, mappedBy: 'resource')]
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
