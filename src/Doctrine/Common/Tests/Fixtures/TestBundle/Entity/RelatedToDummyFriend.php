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

namespace ApiPlatform\Doctrine\Common\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related To Dummy Friend represent an association table for a manytomany relation.
 */
#[ApiResource(normalizationContext: ['groups' => ['fakemanytomany']], filters: ['related_to_dummy_friend.name'])]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies/{relatedDummies}/related_to_dummy_friends{._format}', uriVariables: ['id' => new Link(fromClass: Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/id/related_to_dummy_friends{._format}', uriVariables: ['id' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/related_to_dummy_friends{._format}', uriVariables: ['id' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies/{relatedDummies}/related_to_dummy_friends{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies/{relatedDummies}/related_to_dummy_friends{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], toProperty: 'relatedDummy')], status: 200, filters: ['related_to_dummy_friend.name'], normalizationContext: ['groups' => ['fakemanytomany']], operations: [new GetCollection()])]
#[ORM\Entity]
class RelatedToDummyFriend
{
    /**
     * @var string The dummy name
     */
    #[ApiProperty(types: ['https://schema.org/name'])]
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['fakemanytomany', 'friends'])]
    private $name;
    /**
     * @var string|null The dummy description
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['fakemanytomany', 'friends'])]
    private ?string $description = null;
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: DummyFriend::class)]
    #[ORM\JoinColumn(name: 'dummyfriend_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['fakemanytomany', 'friends'])]
    #[Assert\NotNull]
    private DummyFriend $dummyFriend;
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: RelatedDummy::class, inversedBy: 'relatedToDummyFriend')]
    #[ORM\JoinColumn(name: 'relateddummy_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private RelatedDummy $relatedDummy;

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
