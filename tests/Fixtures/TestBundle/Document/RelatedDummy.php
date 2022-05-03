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
 * @ODM\Document
 */
#[ApiResource(graphQlOperations: [new Query(name: 'item_query'), new Mutation(name: 'update', normalizationContext: ['groups' => ['chicago', 'fakemanytomany']], denormalizationContext: ['groups' => ['friends']])], types: ['https://schema.org/Product'], normalizationContext: ['groups' => ['friends']], filters: ['related_dummy.mongodb.friends'])]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies')], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies/{relatedDummies}.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/id.{_format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies')], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies/{relatedDummies}.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies')], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies/{relatedDummies}.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.mongodb.friends'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
class RelatedDummy extends ParentDummy
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    #[Groups(['chicago', 'friends'])]
    #[ApiProperty(writable: false)]
    private $id;
    /**
     * @var string A name
     *
     * @ODM\Field(type="string", nullable=true)
     */
    #[Groups(['friends'])]
    public $name;
    /**
     * @ODM\Field(type="string")
     */
    #[Groups(['barcelona', 'chicago', 'friends'])]
    protected $symfony = 'symfony';
    /**
     * @var \DateTime A dummy date
     *
     * @ODM\Field(type="date", nullable=true)
     */
    #[Assert\DateTime]
    #[Groups(['friends'])]
    public $dummyDate;
    /**
     * @ODM\ReferenceOne(targetDocument=ThirdLevel::class, cascade={"persist"}, nullable=true, storeAs="id")
     */
    #[Groups(['barcelona', 'chicago', 'friends'])]
    public $thirdLevel;
    /**
     * @ODM\ReferenceMany(targetDocument=RelatedToDummyFriend::class, cascade={"persist"}, mappedBy="relatedDummy", storeAs="id")
     */
    #[Groups(['fakemanytomany', 'friends'])]
    public $relatedToDummyFriend;
    /**
     * @var bool A dummy bool
     *
     * @ODM\Field(type="bool")
     */
    #[Groups(['friends'])]
    public $dummyBoolean;
    /**
     * @var EmbeddableDummy
     *
     * @ODM\EmbedOne(targetDocument=EmbeddableDummy::class)
     */
    #[Groups(['friends'])]
    public $embeddedDummy;

    public function __construct()
    {
        $this->relatedToDummyFriend = new ArrayCollection();
        $this->embeddedDummy = new EmbeddableDummy();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setName($name)
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

    public function setSymfony($symfony)
    {
        $this->symfony = $symfony;
    }

    public function setDummyDate(\DateTime $dummyDate)
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
    public function setDummyBoolean($dummyBoolean)
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    public function getThirdLevel(): ?ThirdLevel
    {
        return $this->thirdLevel;
    }

    public function setThirdLevel(ThirdLevel $thirdLevel = null)
    {
        $this->thirdLevel = $thirdLevel;
    }

    /**
     * Get relatedToDummyFriend.
     */
    public function getRelatedToDummyFriend(): Collection
    {
        return $this->relatedToDummyFriend;
    }

    /**
     * Set relatedToDummyFriend.
     *
     * @param RelatedToDummyFriend $relatedToDummyFriend the value to set
     */
    public function addRelatedToDummyFriend(RelatedToDummyFriend $relatedToDummyFriend)
    {
        $this->relatedToDummyFriend->add($relatedToDummyFriend);
    }

    public function getEmbeddedDummy(): EmbeddableDummy
    {
        return $this->embeddedDummy;
    }

    public function setEmbeddedDummy(EmbeddableDummy $embeddedDummy)
    {
        $this->embeddedDummy = $embeddedDummy;
    }
}
