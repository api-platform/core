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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Assert that a property being a collection set with ApiProperty::UriTemplate to true returns only the IRI of the collection.
 */
#[
    Post,
    Get(normalizationContext: ['groups' => ['read']]),
    GetCollection(normalizationContext: ['groups' => ['read']]),
]
#[ORM\Entity]
class PropertyCollectionIriOnly
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'propertyCollectionIriOnly', targetEntity: PropertyCollectionIriOnlyRelation::class)]
    #[ApiProperty(uriTemplate: '/property-collection-relations')]
    #[Groups('read')]
    private Collection $propertyCollectionIriOnlyRelation;

    /**
     * @var array<int, PropertyCollectionIriOnlyRelation> $iterableIri
     */
    #[ApiProperty(uriTemplate: '/parent/{parentId}/another-collection-operations')]
    #[Groups('read')]
    private array $iterableIri = [];

    #[ApiProperty(uriTemplate: '/parent/{parentId}/property-uri-template/one-to-ones/{id}')]
    #[ORM\OneToOne(mappedBy: 'propertyToOneIriOnly')]
    #[Groups('read')]
    private ?PropertyUriTemplateOneToOneRelation $toOneRelation = null;

    public function __construct()
    {
        $this->propertyCollectionIriOnlyRelation = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, PropertyCollectionIriOnlyRelation>
     */
    public function getPropertyCollectionIriOnlyRelation(): Collection
    {
        return $this->propertyCollectionIriOnlyRelation;
    }

    public function addPropertyCollectionIriOnlyRelation(PropertyCollectionIriOnlyRelation $propertyCollectionIriOnlyRelation): self
    {
        if (!$this->propertyCollectionIriOnlyRelation->contains($propertyCollectionIriOnlyRelation)) {
            $this->propertyCollectionIriOnlyRelation->add($propertyCollectionIriOnlyRelation);
            $propertyCollectionIriOnlyRelation->setPropertyCollectionIriOnly($this);
        }

        return $this;
    }

    public function removePropertyCollectionIriOnlyRelation(PropertyCollectionIriOnlyRelation $propertyCollectionIriOnlyRelation): self
    {
        if ($this->propertyCollectionIriOnlyRelation->removeElement($propertyCollectionIriOnlyRelation)) {
            // set the owning side to null (unless already changed)
            if ($propertyCollectionIriOnlyRelation->getPropertyCollectionIriOnly() === $this) {
                $propertyCollectionIriOnlyRelation->setPropertyCollectionIriOnly(null);
            }
        }

        return $this;
    }

    /**
     * @return array<int, PropertyCollectionIriOnlyRelation>
     */
    public function getIterableIri(): array
    {
        $propertyCollectionIriOnlyRelation = new PropertyCollectionIriOnlyRelation();
        $propertyCollectionIriOnlyRelation->name = 'Michel';

        $this->iterableIri = [$propertyCollectionIriOnlyRelation];

        return $this->iterableIri;
    }

    public function setToOneRelation(PropertyUriTemplateOneToOneRelation $toOneRelation): void
    {
        $toOneRelation->setPropertyToOneIriOnly($this);
        $this->toOneRelation = $toOneRelation;
    }

    public function getToOneRelation(): ?PropertyUriTemplateOneToOneRelation
    {
        return $this->toOneRelation;
    }
}
