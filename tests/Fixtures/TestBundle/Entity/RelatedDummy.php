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

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(
    graphQlOperations: [
        new Query(name: 'item_query'),
        new Mutation(name: 'update', normalizationContext: ['groups' => ['chicago', 'fakemanytomany']], denormalizationContext: ['groups' => ['friends']]),
    ],
    types: ['https://schema.org/Product'],
    normalizationContext: ['groups' => ['friends']],
    filters: ['related_dummy.friends', 'related_dummy.complex_sub_query']
)]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies{._format}', uriVariables: ['id' => new Link(fromClass: Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies')], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies/{relatedDummies}{._format}', uriVariables: ['id' => new Link(fromClass: Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/id{._format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies')], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies/{relatedDummies}{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies')], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies/{relatedDummies}{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['id'])]
#[ORM\Entity]
class RelatedDummy extends ParentDummy implements \Stringable
{
    #[ApiProperty(writable: false)]
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['chicago', 'friends'])]
    private $id;

    /**
     * @var string|null A name
     */
    #[ApiProperty(iris: ['RelatedDummy.name'])]
    #[ORM\Column(nullable: true)]
    #[Groups(['friends'])]
    public $name;

    #[ApiProperty(deprecationReason: 'This property is deprecated for upgrade test')]
    #[ORM\Column]
    #[Groups(['barcelona', 'chicago', 'friends'])]
    #[ApiFilter(filterClass: SearchFilter::class)]
    #[ApiFilter(filterClass: ExistsFilter::class)]
    protected $symfony = 'symfony';

    /**
     * @var \DateTime|null A dummy date
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\DateTime]
    #[Groups(['friends'])]
    #[ApiFilter(filterClass: DateFilter::class)]
    public $dummyDate;

    #[ORM\ManyToOne(targetEntity: ThirdLevel::class, cascade: ['persist'], inversedBy: 'relatedDummies')]
    #[Groups(['barcelona', 'chicago', 'friends'])]
    public ?ThirdLevel $thirdLevel = null;

    #[ORM\OneToMany(targetEntity: RelatedToDummyFriend::class, cascade: ['persist'], mappedBy: 'relatedDummy')]
    #[Groups(['fakemanytomany', 'friends'])]
    public Collection|iterable $relatedToDummyFriend;

    /**
     * @var bool|null A dummy bool
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(['friends'])]
    public ?bool $dummyBoolean = null;

    #[ORM\Embedded(class: 'EmbeddableDummy')]
    #[Groups(['friends'])]
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
