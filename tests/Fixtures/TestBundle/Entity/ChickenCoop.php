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

use ApiPlatform\Doctrine\Orm\Filter\IriFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[GetCollection(
    normalizationContext: ['hydra_prefix' => false],
    parameters: ['chickens' => new QueryParameter(filter: new IriFilter())]
)]
class ChickenCoop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'chickenCoop', targetEntity: Chicken::class, cascade: ['persist'])]
    private Collection $chickens;

    public function __construct()
    {
        $this->chickens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Chicken>
     */
    public function getChickens(): Collection
    {
        return $this->chickens;
    }

    public function addChicken(Chicken $chicken): self
    {
        if (!$this->chickens->contains($chicken)) {
            $this->chickens[] = $chicken;
            $chicken->setChickenCoop($this);
        }

        return $this;
    }

    public function removeChicken(Chicken $chicken): self
    {
        if ($this->chickens->removeElement($chicken)) {
            if ($chicken->getChickenCoop() === $this) {
                $chicken->setChickenCoop(null);
            }
        }

        return $this;
    }
}
