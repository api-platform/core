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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Related Multi Used Dummy.
 *
 * @author Thomas Helmrich <thomas@gigabit.de>
 */
#[ApiResource(graphQlOperations: [new QueryCollection(name: 'collection_query'), new Query(name: 'item_query')])]
#[ODM\Document]
class RelatedMultiUsedDummy
{
    #[ApiProperty(writable: false)]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;

    /**
     * @var string|null A name
     */
    #[ODM\Field(type: 'string', nullable: true)]
    public $name;

    #[ODM\ReferenceOne(targetDocument: SameRelationMultiUseDummy::class, inversedBy: 'oneToManyRelations', nullable: true, storeAs: 'id')]
    protected ?SameRelationMultiUseDummy $oneToManyRelation = null;

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getOneToManyRelation(): ?SameRelationMultiUseDummy
    {
        return $this->oneToManyRelation;
    }

    public function setOneToManyRelation(SameRelationMultiUseDummy $oneToManyRelation): void
    {
        $this->oneToManyRelation = $oneToManyRelation;
    }
}
