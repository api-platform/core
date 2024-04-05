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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy using different kind of relations to the same resource.
 *
 * @author Thomas Helmrich <thomas@gigabit.de>
 */
#[ApiResource(graphQlOperations: [new QueryCollection(), new Query()])]
#[ODM\Document]
class MultiRelationsDummy
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    public string $name;

    #[ODM\ReferenceOne(targetDocument: MultiRelationsRelatedDummy::class, storeAs: 'id', nullable: true)]
    public ?MultiRelationsRelatedDummy $manyToOneRelation = null;

    #[ODM\ReferenceOne(targetDocument: MultiRelationsResolveDummy::class, storeAs: 'id', nullable: true)]
    public ?MultiRelationsResolveDummy $manyToOneResolveRelation = null;

    /** @var Collection<int, MultiRelationsRelatedDummy> */
    #[ODM\ReferenceMany(targetDocument: MultiRelationsRelatedDummy::class, storeAs: 'id', nullable: true)]
    public Collection $manyToManyRelations;

    /** @var Collection<int, MultiRelationsRelatedDummy> */
    #[ODM\ReferenceMany(targetDocument: MultiRelationsRelatedDummy::class, mappedBy: 'oneToManyRelation', storeAs: 'id')]
    public Collection $oneToManyRelations;

    /** @var Collection<MultiRelationsNested> */
    #[ODM\EmbedMany]
    private Collection $nestedCollection;

    /** @var Collection<MultiRelationsNestedPaginated> */
    #[ODM\EmbedMany]
    private Collection $nestedPaginatedCollection;

    public function __construct()
    {
        $this->manyToManyRelations = new ArrayCollection();
        $this->oneToManyRelations = new ArrayCollection();
        $this->nestedCollection = new ArrayCollection();
        $this->nestedPaginatedCollection = new ArrayCollection();
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

    public function getManyToOneResolveRelation(): ?MultiRelationsResolveDummy
    {
        return $this->manyToOneResolveRelation;
    }

    public function setManyToOneResolveRelation(?MultiRelationsResolveDummy $manyToOneResolveRelation): self
    {
        $this->manyToOneResolveRelation = $manyToOneResolveRelation;

        return $this;
    }

    public function addManyToManyRelation(MultiRelationsRelatedDummy $relatedMultiUsedDummy): void
    {
        $this->manyToManyRelations->add($relatedMultiUsedDummy);
    }

    public function addOneToManyRelation(MultiRelationsRelatedDummy $relatedMultiUsedDummy): void
    {
        $this->oneToManyRelations->add($relatedMultiUsedDummy);
    }

    public function getNestedCollection(): Collection
    {
        return $this->nestedCollection->map(fn ($entry) => ['name' => $entry->name]);
    }

    public function setNestedCollection(Collection $nestedCollection): self
    {
        $this->nestedCollection = $nestedCollection;

        return $this;
    }

    public function getNestedPaginatedCollection(): Collection
    {
        return $this->nestedPaginatedCollection->map(fn ($entry) => ['name' => $entry->name]);
    }

    public function setNestedPaginatedCollection(Collection $nestedPaginatedCollection): self
    {
        $this->nestedPaginatedCollection = $nestedPaginatedCollection;

        return $this;
    }
}
