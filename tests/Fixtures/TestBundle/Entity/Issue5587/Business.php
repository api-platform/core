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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5587;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'issue5584_business',
    operations: [
        new Get(uriTemplate: 'issue5584_businesses/{id}'),
        new Post(uriTemplate: 'issue5584_businesses'),
        new Put(uriTemplate: 'issue5584_businesses/{id}'),
    ],
    normalizationContext: [
        'groups' => ['r'],
    ],
    denormalizationContext: [
        'groups' => ['w'],
    ],
)]
#[ORM\Table(name: 'issue5584_business')]
#[ORM\Entity()]
class Business
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups(['w', 'r'])]
    private $id;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['w', 'r'])]
    private $name;
    /** @var Collection<int, Employee> */
    #[ORM\JoinTable(name: 'issue5584_business_users')]
    #[ORM\ManyToMany(targetEntity: Employee::class, inversedBy: 'businesses')]
    #[Groups(['w', 'r'])]
    private $businessEmployees;

    public function __construct()
    {
        $this->businessEmployees = new ArrayCollection();
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

    public function getBusinessEmployees(): Collection
    {
        return $this->businessEmployees;
    }

    public function addBusinessEmployee(Employee $businessEmployee): self
    {
        if (!$this->businessEmployees->contains($businessEmployee)) {
            $this->businessEmployees[] = $businessEmployee;
        }

        return $this;
    }

    public function removeBusinessEmployee(Employee $businessEmployee): self
    {
        if ($this->businessEmployees->contains($businessEmployee)) {
            $this->businessEmployees->removeElement($businessEmployee);
        }

        return $this;
    }
}
