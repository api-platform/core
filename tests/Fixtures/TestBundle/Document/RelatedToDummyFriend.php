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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related To Dummy Friend represent an association table for a manytomany relation.
 *
 * @ODM\Document
 */
#[ApiResource(normalizationContext: ['groups' => ['fakemanytomany']], filters: ['related_to_dummy_friend.mongodb.name'])]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies/{relatedDummies}/related_to_dummy_friends.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.mongodb.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/id/related_to_dummy_friends.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.mongodb.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/related_to_dummy_friends.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.mongodb.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies/{relatedDummies}/related_to_dummy_friends.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.mongodb.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies/{relatedDummies}/related_to_dummy_friends.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.mongodb.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
class RelatedToDummyFriend
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;
    /**
     * @var string The dummy name
     *
     * @ODM\Field(type="string")
     */
    #[Assert\NotBlank]
    #[ApiProperty(types: ['http://schema.org/name'])]
    #[Groups(['fakemanytomany', 'friends'])]
    private $name;
    /**
     * @var string|null The dummy description
     *
     * @ODM\Field(type="string")
     */
    #[Groups(['fakemanytomany', 'friends'])]
    private ?string $description = null;
    /**
     * @ODM\ReferenceOne(targetDocument=DummyFriend::class, storeAs="id")
     */
    #[Groups(['fakemanytomany', 'friends'])]
    #[Assert\NotNull]
    private $dummyFriend;
    /**
     * @ODM\ReferenceOne(targetDocument=RelatedDummy::class, inversedBy="relatedToDummyFriend", storeAs="id")
     */
    #[Assert\NotNull]
    private $relatedDummy;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Gets dummyFriend.
     */
    public function getDummyFriend(): DummyFriend
    {
        return $this->dummyFriend;
    }

    /**
     * Sets dummyFriend.
     *
     * @param DummyFriend $dummyFriend the value to set
     */
    public function setDummyFriend(DummyFriend $dummyFriend)
    {
        $this->dummyFriend = $dummyFriend;
    }

    /**
     * Gets relatedDummy.
     */
    public function getRelatedDummy(): RelatedDummy
    {
        return $this->relatedDummy;
    }

    /**
     * Sets relatedDummy.
     *
     * @param RelatedDummy $relatedDummy the value to set
     */
    public function setRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummy = $relatedDummy;
    }
}
