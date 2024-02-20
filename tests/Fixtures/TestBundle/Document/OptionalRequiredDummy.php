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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * OptionalRequiredDummy. Used to test GraphQL Schema generation for nullable embedded relations.
 */
#[ApiResource(
    graphQlOperations: [
        new Query(name: 'item_query'),
        new Mutation(name: 'update', normalizationContext: ['groups' => ['chicago', 'fakemanytomany']], denormalizationContext: ['groups' => ['friends']]),
    ],
)]
#[ODM\Document]
class OptionalRequiredDummy
{
    #[ApiProperty(writable: false)]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    #[Groups(['chicago', 'friends'])]
    private $id;

    #[ODM\ReferenceOne(targetDocument: ThirdLevel::class, inversedBy: 'thirdLevel', nullable: true, storeAs: 'id')]
    #[Groups(['barcelona', 'chicago', 'friends'])]
    public ?ThirdLevel $thirdLevel = null;

    #[ODM\ReferenceOne(targetDocument: ThirdLevel::class, inversedBy: 'thirdLevelRequired', nullable: true, storeAs: 'id')]
    #[ApiProperty(required: true)]
    #[Groups(['barcelona', 'chicago', 'friends'])]
    public ThirdLevel $thirdLevelRequired;

    #[ODM\ReferenceMany(targetDocument: RelatedToDummyFriend::class, mappedBy: 'relatedDummy')]
    #[Groups(['fakemanytomany', 'friends'])]
    public Collection|iterable $relatedToDummyFriend;

    public function __construct()
    {
        $this->relatedToDummyFriend = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getThirdLevel(): ?ThirdLevel
    {
        return $this->thirdLevel;
    }

    public function setThirdLevel(?ThirdLevel $thirdLevel = null): void
    {
        $this->thirdLevel = $thirdLevel;
    }

    public function getThirdLevelRequired(): ThirdLevel
    {
        return $this->thirdLevelRequired;
    }

    public function setThirdLevelRequired(ThirdLevel $thirdLevelRequired): void
    {
        $this->thirdLevelRequired = $thirdLevelRequired;
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

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
