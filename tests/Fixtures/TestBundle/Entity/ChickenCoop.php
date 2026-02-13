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

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Orm\Filter\FreeTextQueryFilter;
use ApiPlatform\Doctrine\Orm\Filter\IriFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrFilter;
use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['hydra_prefix' => false],
            parameters: [
                'chickenNamePartial' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    property: 'chickens.name',
                ),
                'searchChickenNamePartial[:property]' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    properties: ['chickens.name'],
                ),
                'chickenNameExact' => new QueryParameter(
                    filter: new ExactFilter(),
                    property: 'chickens.name',
                ),
                'searchChickenNameExact[:property]' => new QueryParameter(
                    filter: new ExactFilter(),
                    properties: ['chickens.name'],
                ),
                'chickenIri' => new QueryParameter(
                    filter: new IriFilter(),
                    property: 'chickens',
                ),
                'searchChickenIri[:property]' => new QueryParameter(
                    filter: new IriFilter(),
                    properties: ['chickens'],
                ),
                'q' => new QueryParameter(
                    filter: new FreeTextQueryFilter(new PartialSearchFilter()),
                    properties: ['chickens.name'],
                ),
                'chickenNameOrEan' => new QueryParameter(
                    filter: new FreeTextQueryFilter(new OrFilter(new PartialSearchFilter())),
                    properties: ['chickens.name', 'chickens.ean'],
                ),
                'qOwner' => new QueryParameter(
                    filter: new FreeTextQueryFilter(new PartialSearchFilter()),
                    properties: ['chickens.owner.name'],
                ),
                'searchQOwner[:property]' => new QueryParameter(
                    filter: new FreeTextQueryFilter(new PartialSearchFilter()),
                    properties: ['chickens.owner.name'],
                ),
                'chickenOwnerNamePartial' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    property: 'chickens.owner.name',
                ),
                'searchChickenOwnerNamePartial[:property]' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    properties: ['chickens.owner.name'],
                ),
                'chickenOwnerNameExact' => new QueryParameter(
                    filter: new ExactFilter(),
                    property: 'chickens.owner.name',
                ),
                'searchChickenOwnerNameExact[:property]' => new QueryParameter(
                    filter: new ExactFilter(),
                    properties: ['chickens.owner.name'],
                ),
            ],
        ),
    ]
)]
class ChickenCoop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: Chicken::class, mappedBy: 'chickenCoop', cascade: ['persist'])]
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
