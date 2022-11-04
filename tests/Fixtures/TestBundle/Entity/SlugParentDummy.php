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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Custom Identifier Dummy With Subresource.
 */
#[ApiResource(uriVariables: 'slug')]
#[ApiResource(uriTemplate: '/slug_parent_dummies/{slug}/child_dummies/{childDummies}/parent_dummy{._format}', uriVariables: ['slug' => new Link(fromClass: self::class, identifiers: ['slug'], toProperty: 'parentDummy'), 'childDummies' => new Link(fromClass: SlugChildDummy::class, identifiers: ['slug'], fromProperty: 'parentDummy')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/slug_child_dummies/{slug}/parent_dummy{._format}', uriVariables: ['slug' => new Link(fromClass: SlugChildDummy::class, identifiers: ['slug'], fromProperty: 'parentDummy')], status: 200, operations: [new Get()])]
#[ORM\Entity]
class SlugParentDummy
{
    /**
     * @var int|null The database identifier
     */
    #[ApiProperty(identifier: false)]
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    /**
     * @var string The slug used a API identifier
     */
    #[ApiProperty(identifier: true)]
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    /**
     * @var Collection<int, SlugChildDummy>
     */
    #[ORM\OneToMany(targetEntity: SlugChildDummy::class, mappedBy: 'parentDummy')]
    private Collection|iterable $childDummies;

    public function __construct()
    {
        $this->childDummies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return Collection<SlugChildDummy>
     */
    public function getChildDummies(): Collection|iterable
    {
        return $this->childDummies;
    }

    public function addChildDummy(SlugChildDummy $childDummy): self
    {
        if (!$this->childDummies->contains($childDummy)) {
            $this->childDummies[] = $childDummy;
            $childDummy->setParentDummy($this);
        }

        return $this;
    }

    public function removeChildDummy(SlugChildDummy $childDummy): self
    {
        if ($this->childDummies->contains($childDummy)) {
            $this->childDummies->removeElement($childDummy);
            // set the owning side to null (unless already changed)
            if ($childDummy->getParentDummy() === $this) {
                $childDummy->setParentDummy(null);
            }
        }

        return $this;
    }
}
