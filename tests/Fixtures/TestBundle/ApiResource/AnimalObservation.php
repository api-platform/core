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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource]
class AnimalObservation
{
    private ?int $id = null;

    #[ApiProperty(required: true)]
    private Collection $individuals;

    public function __construct()
    {
        $this->individuals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return Collection<int, Animal> */
    public function getIndividuals(): Collection
    {
        return $this->individuals;
    }

    public function addIndividual(Animal $individual): static
    {
        if (!$this->individuals->contains($individual)) {
            $this->individuals->add($individual);
        }

        return $this;
    }

    public function removeIndividual(Animal $individual): static
    {
        $this->individuals->removeElement($individual);

        return $this;
    }
}
