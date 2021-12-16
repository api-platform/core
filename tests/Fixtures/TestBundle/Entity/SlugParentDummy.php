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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Custom Identifier Dummy With Subresource.
 *
 * @ApiResource(attributes={"identifiers"="slug"})
 * @ORM\Entity
 */
class SlugParentDummy
{
    /**
     * @var int|null The database identifier
     *
     * @ApiProperty(identifier=false)
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The slug used a API identifier
     *
     * @ApiProperty(identifier=true)
     *
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $slug;

    /**
     * @var Collection<int, SlugChildDummy>
     * @ORM\OneToMany(targetEntity="ApiPlatform\Tests\Fixtures\TestBundle\Entity\SlugChildDummy", mappedBy="parentDummy")
     *
     * @ApiSubresource
     */
    private $childDummies;

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
     * @return Collection|SlugChildDummy[]
     */
    public function getChildDummies(): Collection
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
