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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy used in different kind of relations in the same resource.
 *
 * @author Thomas Helmrich <thomas@gigabit.de>
 */
#[ApiResource(graphQlOperations: [new Query(resolver: 'app.graphql.query_resolver.multi_relations_custom_item', read: false), new QueryCollection(resolver: 'app.graphql.query_resolver.multi_relations_collection', read: false)])]
#[ODM\Document]
class MultiRelationsResolveDummy
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string', nullable: true)]
    public ?string $name;

    #[ODM\ReferenceOne(targetDocument: MultiRelationsDummy::class, inversedBy: 'oneToManyResolveRelations', nullable: true, storeAs: 'id')]
    private ?MultiRelationsDummy $oneToManyResolveRelation;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOneToManyResolveRelation(): ?MultiRelationsDummy
    {
        return $this->oneToManyResolveRelation;
    }

    public function setOneToManyResolveRelation(?MultiRelationsDummy $oneToManyResolveRelation): void
    {
        $this->oneToManyResolveRelation = $oneToManyResolveRelation;
    }
}
