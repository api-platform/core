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

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[
    Post,
    GetCollection(uriTemplate: '/property-collection-relation-second-levels'),
    GetCollection(
        uriTemplate: '/property_collection_iri_only_relations/{parentId}/children',
        uriVariables: [
            'parentId' => new Link(toProperty: 'parent', fromClass: PropertyCollectionIriOnlyRelation::class),
        ]
    )
]
#[ODM\Document]
class PropertyCollectionIriOnlyRelationSecondLevel
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\ReferenceOne(targetDocument: PropertyCollectionIriOnlyRelation::class)]
    private ?PropertyCollectionIriOnlyRelation $parent = null;

    public function getId(): ?int
    {
        return $this->id ?? 9999;
    }

    public function getParent(): ?PropertyCollectionIriOnlyRelation
    {
        return $this->parent;
    }

    public function setParent(?PropertyCollectionIriOnlyRelation $parent): void
    {
        $this->parent = $parent;
    }
}
