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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Related Multi Used Dummy.
 *
 * @author Thomas Helmrich <thomas@gigabit.de>
 */
#[ApiResource(graphQlOperations: [new QueryCollection(name: 'collection_query'), new Query(name: 'item_query')])]
#[ORM\Entity]
class RelatedMultiUsedDummy
{
    #[ApiProperty(writable: false)]
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string|null A name
     */
    #[ORM\Column(nullable: true)]
    public $name;

    #[ORM\ManyToOne(targetEntity: SameRelationMultiUseDummy::class, inversedBy: 'oneToManyRelations')]
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
