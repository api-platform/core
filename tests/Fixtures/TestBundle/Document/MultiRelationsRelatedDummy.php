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
#[ApiResource(graphQlOperations: [new QueryCollection(), new Query()])]
#[ODM\Document]
class MultiRelationsRelatedDummy
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string', nullable: true)]
    public ?string $name;

    #[ODM\ReferenceOne(targetDocument: MultiRelationsDummy::class, inversedBy: 'oneToManyRelations', nullable: true, storeAs: 'id')]
    private ?MultiRelationsDummy $oneToManyRelation;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOneToManyRelation(): ?MultiRelationsDummy
    {
        return $this->oneToManyRelation;
    }

    public function setOneToManyRelation(?MultiRelationsDummy $oneToManyRelation): void
    {
        $this->oneToManyRelation = $oneToManyRelation;
    }
}
