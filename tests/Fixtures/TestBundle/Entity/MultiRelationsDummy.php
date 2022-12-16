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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy using different kind of relations to the same resource.
 *
 * @author Thomas Helmrich <thomas@gigabit.de>
 */
#[ApiResource(graphQlOperations: [new QueryCollection(), new Query()])]
#[ORM\Entity]
class MultiRelationsDummy
{
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column]
    public string $name;

    #[ORM\ManyToOne(targetEntity: MultiRelationsRelatedDummy::class)]
    public ?MultiRelationsRelatedDummy $manyToOneRelation = null;

    /** @var Collection<int, MultiRelationsRelatedDummy> */
    #[ORM\ManyToMany(targetEntity: MultiRelationsRelatedDummy::class)]
    public Collection $manyToManyRelations;

    /** @var Collection<int, MultiRelationsRelatedDummy> */
    #[ORM\OneToMany(targetEntity: MultiRelationsRelatedDummy::class, mappedBy: 'oneToManyRelation')]
    public Collection $oneToManyRelations;

    public function __construct()
    {
        $this->manyToManyRelations = new ArrayCollection();
        $this->oneToManyRelations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getManyToOneRelation(): ?MultiRelationsRelatedDummy
    {
        return $this->manyToOneRelation;
    }

    public function setManyToOneRelation(?MultiRelationsRelatedDummy $relatedMultiUsedDummy): void
    {
        $this->manyToOneRelation = $relatedMultiUsedDummy;
    }

    public function addManyToManyRelation(MultiRelationsRelatedDummy $relatedMultiUsedDummy): void
    {
        $this->manyToManyRelations->add($relatedMultiUsedDummy);
    }

    public function addOneToManyRelation(MultiRelationsRelatedDummy $relatedMultiUsedDummy): void
    {
        $this->oneToManyRelations->add($relatedMultiUsedDummy);
    }
}
