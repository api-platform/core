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

namespace ApiPlatform\Serializer\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;

#[
    Post,
    GetCollection(uriTemplate: '/property-collection-relations'),
    GetCollection(
        uriTemplate: '/parent/{parentId}/another-collection-operations',
        uriVariables: [
            'parentId' => new Link(fromProperty: 'propertyCollectionIriOnly', fromClass: PropertyCollectionIriOnly::class),
        ]
    ),
    Get(
        uriTemplate: '/parent/{parentId}/another-collection-operations/{id}',
        uriVariables: [
            'parentId' => new Link(fromProperty: 'propertyCollectionIriOnly', fromClass: PropertyCollectionIriOnly::class),
            'id' => new Link(fromProperty: 'id', toClass: PropertyCollectionIriOnlyRelation::class),
        ]
    )
]
class PropertyCollectionIriOnlyRelation
{
    /**
     * The entity ID.
     */
    private ?int $id = null;

    #[Groups('read')]
    public string $name = '';

    private ?PropertyCollectionIriOnly $propertyCollectionIriOnly = null;

    public function getId(): ?int
    {
        return $this->id ?? 9999;
    }

    public function getPropertyCollectionIriOnly(): ?PropertyCollectionIriOnly
    {
        return $this->propertyCollectionIriOnly;
    }

    public function setPropertyCollectionIriOnly(?PropertyCollectionIriOnly $propertyCollectionIriOnly): void
    {
        $this->propertyCollectionIriOnly = $propertyCollectionIriOnly;
    }
}
