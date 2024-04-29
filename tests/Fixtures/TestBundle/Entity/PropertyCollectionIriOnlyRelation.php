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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;

#[
    Post,
    GetCollection(uriTemplate: '/property-collection-relations'),
    GetCollection(
        uriTemplate: '/parent/{parentId}/another-collection-operations',
        uriVariables: [
            'parentId' => new Link(toProperty: 'propertyCollectionIriOnly', fromClass: PropertyCollectionIriOnly::class),
        ]
    )
]
#[ORM\Entity]
class PropertyCollectionIriOnlyRelation
{
    /**
     * The entity ID.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    #[NotBlank]
    #[Groups('read')]
    public string $name = '';

    #[ORM\ManyToOne(inversedBy: 'propertyCollectionIriOnlyRelation')]
    private ?PropertyCollectionIriOnly $propertyCollectionIriOnly = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: PropertyCollectionIriOnlyRelationSecondLevel::class)]
    #[ApiProperty(uriTemplate: '/property_collection_iri_only_relations/{parentId}/children')]
    #[Groups('read')]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id ?? 9999;
    }

    public function getPropertyCollectionIriOnly(): ?PropertyCollectionIriOnly
    {
        return $this->propertyCollectionIriOnly;
    }

    public function setPropertyCollectionIriOnly(?PropertyCollectionIriOnly $propertyCollectionIriOnly): void
    {
        $this->propertyCollectionIriOnly = $propertyCollectionIriOnly;
    }

    /**
     * @return Collection<int, PropertyCollectionIriOnlyRelationSecondLevel>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(PropertyCollectionIriOnlyRelationSecondLevel $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(PropertyCollectionIriOnlyRelationSecondLevel $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }
}
