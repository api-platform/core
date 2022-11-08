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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Same relation Multi Use Dummy.
 *
 * @author Thomas Helmrich <thomas@gigabit.de>
 */
#[ApiResource(graphQlOperations: [new QueryCollection(name: 'collection_query'), new Query(name: 'item_query')])]
#[ODM\Document]
class SameRelationMultiUseDummy
{
    /**
     * @var int|null The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;

    /**
     * @var string The same relation multi use dummy name
     */
    #[ApiProperty(iris: ['https://schema.org/name'])]
    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $name;

    #[ODM\ReferenceOne(targetDocument: RelatedMultiUsedDummy::class, storeAs: 'id', nullable: true)]
    public ?RelatedMultiUsedDummy $manyToOneRelation = null;

    #[ODM\ReferenceMany(targetDocument: RelatedMultiUsedDummy::class, storeAs: 'id', nullable: true)]
    public Collection|iterable $manyToManyRelations;

    #[ODM\ReferenceMany(targetDocument: RelatedMultiUsedDummy::class, mappedBy: 'oneToManyRelation', storeAs: 'id')]
    public Collection|iterable $oneToManyRelations;

    public function __construct()
    {
        $this->manyToManyRelations = new ArrayCollection();
        $this->oneToManyRelations = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getManyToOneRelation(): ?RelatedMultiUsedDummy
    {
        return $this->manyToOneRelation;
    }

    public function setManyToOneRelation(RelatedMultiUsedDummy $relatedMultiUsedDummy): void
    {
        $this->manyToOneRelation = $relatedMultiUsedDummy;
    }

    public function addManyToManyRelation(RelatedMultiUsedDummy $relatedMultiUsedDummy): void
    {
        $this->manyToManyRelations->add($relatedMultiUsedDummy);
    }

    public function addOneToManyRelation(RelatedMultiUsedDummy $relatedMultiUsedDummy): void
    {
        $this->oneToManyRelations->add($relatedMultiUsedDummy);
    }
}
