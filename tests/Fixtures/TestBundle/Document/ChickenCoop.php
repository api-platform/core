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

use ApiPlatform\Doctrine\Odm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Odm\Filter\FreeTextQueryFilter;
use ApiPlatform\Doctrine\Odm\Filter\IriFilter;
use ApiPlatform\Doctrine\Odm\Filter\OrFilter;
use ApiPlatform\Doctrine\Odm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
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
                    property: 'chickenReferences',
                ),
                'searchChickenIri[:property]' => new QueryParameter(
                    filter: new IriFilter(),
                    properties: ['chickenReferences'],
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
    #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
    private ?int $id = null;

    #[ODM\EmbedMany(targetDocument: Chicken::class)]
    private Collection $chickens;

    #[ODM\ReferenceMany(targetDocument: Chicken::class, cascade: 'all')]
    private Collection $chickenReferences;

    public function __construct()
    {
        $this->chickens = new ArrayCollection();
        $this->chickenReferences = new ArrayCollection();
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

    /**
     * @return Collection<int, Chicken>
     */
    public function getChickenReferences(): Collection
    {
        return $this->chickenReferences;
    }

    public function addChickenReference(Chicken $chicken): self
    {
        if (!$this->chickenReferences->contains($chicken)) {
            $this->chickenReferences[] = $chicken;
        }

        return $this;
    }

    public function removeChickenReference(Chicken $chicken): self
    {
        $this->chickenReferences->removeElement($chicken);

        return $this;
    }
}
