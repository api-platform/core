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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
class SubresourceOrganization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'subresourceOrganization', targetEntity: SubresourceEmployee::class, orphanRemoval: true)]
    private Collection $employees;

    #[ORM\OneToMany(mappedBy: 'subresourceOrganization', targetEntity: SubresourceFactory::class, orphanRemoval: true)]
    private Collection $factories;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
        $this->factories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, SubresourceEmployee>
     */
    public function getSubresourceEmployees(): Collection
    {
        return $this->employees;
    }

    public function addSubresourceEmployee(SubresourceEmployee $employee): self
    {
        if (!$this->employees->contains($employee)) {
            $this->employees->add($employee);
            $employee->setSubresourceOrganization($this);
        }

        return $this;
    }

    public function removeSubresourceEmployee(SubresourceEmployee $employee): self
    {
        // set the owning side to null (unless already changed)
        if ($this->employees->removeElement($employee) && $employee->getSubresourceOrganization() === $this) {
            $employee->setSubresourceOrganization(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, SubresourceFactory>
     */
    public function getSubresourceFactories(): Collection
    {
        return $this->factories;
    }

    public function addSubresourceFactory(SubresourceFactory $factory): self
    {
        if (!$this->factories->contains($factory)) {
            $this->factories->add($factory);
            $factory->setSubresourceOrganization($this);
        }

        return $this;
    }

    public function removeSubresourceFactory(SubresourceFactory $factory): self
    {
        // set the owning side to null (unless already changed)
        if ($this->factories->removeElement($factory) && $factory->getSubresourceOrganization() === $this) {
            $factory->setSubresourceOrganization(null);
        }

        return $this;
    }
}
