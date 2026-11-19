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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * ODM mirror of the QueryParameter-based "color" collection resource.
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
#[ODM\Document]
class GraphQlFilteredResourceColor
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    #[Serializer\Groups(['graphql_filtered'])]
    private ?int $id = null;

    #[ODM\ReferenceOne(targetDocument: GraphQlFilteredResource::class, inversedBy: 'colors', storeAs: 'id')]
    private ?GraphQlFilteredResource $resource = null;

    #[ODM\Field(type: 'string')]
    #[Serializer\Groups(['graphql_filtered'])]
    private string $prop = '';

    #[ODM\Field(type: 'int')]
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
