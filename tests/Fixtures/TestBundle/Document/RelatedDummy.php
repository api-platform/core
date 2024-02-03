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

use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use ApiPlatform\Doctrine\Odm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 */
#[ApiResource(graphQlOperations: [new Query(name: 'item_query'), new Mutation(name: 'update', normalizationContext: ['groups' => ['chicago', 'fakemanytomany']], denormalizationContext: ['groups' => ['friends']])], types: ['https://schema.org/Product'], normalizationContext: ['groups' => ['friends']], filters: ['related_dummy.mongodb.friends'])]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies{._format}', uriVariables: ['id' => new Link(fromClass: Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies')], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies/{relatedDummies}{._format}', uriVariables: ['id' => new Link(fromClass: Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/id{._format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies')], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies/{relatedDummies}{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies')], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies/{relatedDummies}{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['id'])]
#[ODM\Document]
class RelatedDummy extends ParentDummy implements \Stringable
{
    #[Groups(['chicago', 'friends'])]
    #[ApiProperty(writable: false)]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    /**
     * @var string A name
     */
    #[Groups(['friends'])]
    #[ODM\Field(type: 'string', nullable: true)]
    public $name;
    #[Groups(['barcelona', 'chicago', 'friends'])]
    #[ODM\Field(type: 'string')]
    #[ApiFilter(filterClass: SearchFilter::class)]
    #[ApiFilter(filterClass: ExistsFilter::class)]
    protected $symfony = 'symfony';
    /**
     * @var \DateTime A dummy date
     */
    #[Assert\DateTime]
    #[Groups(['friends'])]
    #[ODM\Field(type: 'date', nullable: true)]
    #[ApiFilter(filterClass: DateFilter::class)]
    public $dummyDate;
    #[Groups(['barcelona', 'chicago', 'friends'])]
    #[ODM\ReferenceOne(targetDocument: ThirdLevel::class, cascade: ['persist'], nullable: true, storeAs: 'id', inversedBy: 'relatedDummies')]
    public ?ThirdLevel $thirdLevel = null;
    #[Groups(['fakemanytomany', 'friends'])]
    #[ODM\ReferenceMany(targetDocument: RelatedToDummyFriend::class, cascade: ['persist'], mappedBy: 'relatedDummy', storeAs: 'id')]
    public Collection|iterable $relatedToDummyFriend;
    #[Groups(['friends'])]
    #[ODM\Field(type: 'bool')]
    public ?bool $dummyBoolean = null;
    #[Groups(['friends'])]
    #[ODM\EmbedOne(targetDocument: EmbeddableDummy::class)]
    public ?EmbeddableDummy $embeddedDummy = null;

    public function __construct()
    {
        $this->relatedToDummyFriend = new ArrayCollection();
        $this->embeddedDummy = new EmbeddableDummy();
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

    public function getSymfony()
    {
        return $this->symfony;
    }

    public function setSymfony($symfony): void
    {
        $this->symfony = $symfony;
    }

    public function setDummyDate(\DateTime $dummyDate): void
    {
        $this->dummyDate = $dummyDate;
    }

    public function getDummyDate()
    {
        return $this->dummyDate;
    }

    public function isDummyBoolean(): ?bool
    {
        return $this->dummyBoolean;
    }

    /**
     * @param bool $dummyBoolean
     */
    public function setDummyBoolean($dummyBoolean): void
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    public function getThirdLevel(): ?ThirdLevel
    {
        return $this->thirdLevel;
    }

    public function setThirdLevel(?ThirdLevel $thirdLevel = null): void
    {
        $this->thirdLevel = $thirdLevel;
    }

    /**
     * Get relatedToDummyFriend.
     */
    public function getRelatedToDummyFriend(): Collection|iterable
    {
        return $this->relatedToDummyFriend;
    }

    /**
     * Set relatedToDummyFriend.
     *
     * @param RelatedToDummyFriend $relatedToDummyFriend the value to set
     */
    public function addRelatedToDummyFriend(RelatedToDummyFriend $relatedToDummyFriend): void
    {
        $this->relatedToDummyFriend->add($relatedToDummyFriend);
    }

    public function getEmbeddedDummy(): EmbeddableDummy
    {
        return $this->embeddedDummy;
    }

    public function setEmbeddedDummy(EmbeddableDummy $embeddedDummy): void
    {
        $this->embeddedDummy = $embeddedDummy;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
