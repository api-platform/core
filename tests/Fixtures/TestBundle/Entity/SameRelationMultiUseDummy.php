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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Same relation Multi Use Dummy.
 *
 * @author Thomas Helmrich <thomas@gigabit.de>
 */
#[ApiResource(graphQlOperations: [new QueryCollection(name: 'collection_query'), new Query(name: 'item_query')])]
#[ORM\Entity]
class SameRelationMultiUseDummy
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string The same relation multi use dummy name
     */
    #[ApiProperty(iris: ['https://schema.org/name'])]
    #[ORM\Column]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\ManyToOne(targetEntity: RelatedMultiUsedDummy::class)]
    public ?RelatedMultiUsedDummy $manyToOneRelation = null;

    #[ORM\ManyToMany(targetEntity: RelatedMultiUsedDummy::class)]
    public Collection|iterable $manyToManyRelations;

    #[ORM\OneToMany(targetEntity: RelatedMultiUsedDummy::class, mappedBy: 'oneToManyRelation')]
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
