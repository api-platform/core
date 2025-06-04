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

use ApiPlatform\Doctrine\Odm\Filter\IriFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Chicken;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
#[GetCollection(
    normalizationContext: ['hydra_prefix' => false],
    parameters: ['chickens' => new QueryParameter(filter: new IriFilter())]
)]
class ChickenCoop
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\ReferenceMany(targetDocument: Chicken::class, mappedBy: 'chickenCoop')]
    private Collection $chickens;

    public function __construct()
    {
        $this->chickens = new ArrayCollection();
    }

    public function getId(): ?string
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
