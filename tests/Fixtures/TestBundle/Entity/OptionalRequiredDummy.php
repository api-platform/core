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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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
#[ORM\Entity]
class OptionalRequiredDummy
{
    #[ApiProperty(writable: false)]
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['chicago', 'friends'])]
    private $id;

    #[ORM\ManyToOne(targetEntity: ThirdLevel::class, cascade: ['persist'], inversedBy: 'relatedDummies')]
    #[Groups(['barcelona', 'chicago', 'friends'])]
    public ?ThirdLevel $thirdLevel = null;

    #[ORM\ManyToOne(targetEntity: ThirdLevel::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(required: true)]
    #[Groups(['barcelona', 'chicago', 'friends'])]
    public ThirdLevel $thirdLevelRequired;

    #[ORM\OneToMany(targetEntity: RelatedToDummyFriend::class, cascade: ['persist'], mappedBy: 'relatedDummy')]
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
