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
 */
#[ApiResource(normalizationContext: ['groups' => ['fakemanytomany']], filters: ['related_to_dummy_friend.mongodb.name'])]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies/{relatedDummies}/related_to_dummy_friends{._format}', uriVariables: ['id' => new Link(fromClass: Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.mongodb.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/id/related_to_dummy_friends{._format}', uriVariables: ['id' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.mongodb.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/related_to_dummy_friends{._format}', uriVariables: ['id' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.mongodb.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies/{relatedDummies}/related_to_dummy_friends{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.mongodb.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies/{relatedDummies}/related_to_dummy_friends{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.mongodb.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ODM\Document]
class RelatedToDummyFriend
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    /**
     * @var string The dummy name
     */
    #[Assert\NotBlank]
    #[ApiProperty(types: ['https://schema.org/name'])]
    #[Groups(['fakemanytomany', 'friends'])]
    #[ODM\Field(type: 'string')]
    private $name;
    /**
     * @var string|null The dummy description
     */
    #[Groups(['fakemanytomany', 'friends'])]
    #[ODM\Field(type: 'string')]
    private ?string $description = null;
    #[Groups(['fakemanytomany', 'friends'])]
    #[Assert\NotNull]
    #[ODM\ReferenceOne(targetDocument: DummyFriend::class, storeAs: 'id')]
    private DummyFriend $dummyFriend;
    #[Assert\NotNull]
    #[ODM\ReferenceOne(targetDocument: RelatedDummy::class, inversedBy: 'relatedToDummyFriend', storeAs: 'id')]
    private RelatedDummy $relatedDummy;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description): void
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
    public function setDummyFriend(DummyFriend $dummyFriend): void
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
    public function setRelatedDummy(RelatedDummy $relatedDummy): void
    {
        $this->relatedDummy = $relatedDummy;
    }
}
