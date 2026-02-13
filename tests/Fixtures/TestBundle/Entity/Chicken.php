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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['hydra_prefix' => false],
            parameters: [
                'chickenCoop' => new QueryParameter(filter: new IriFilter()),
                'chickenCoopNoProperty' => new QueryParameter(filter: new IriFilter()),
                'chickenCoopId' => new QueryParameter(filter: new ExactFilter(), property: 'chickenCoop'),
                'name' => new QueryParameter(filter: new ExactFilter()),
                'nameExactNoProperty' => new QueryParameter(filter: new ExactFilter()),
                'namePartial' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    property: 'name',
                ),
                'namePartialNoProperty' => new QueryParameter(filter: new PartialSearchFilter()),
                'namePartialSensitive' => new QueryParameter(
                    filter: new PartialSearchFilter(true),
                    property: 'name',
                ),
                'autocomplete' => new QueryParameter(filter: new FreeTextQueryFilter(new OrFilter(new ExactFilter())), properties: ['name', 'ean']),
                'q' => new QueryParameter(filter: new FreeTextQueryFilter(new PartialSearchFilter()), properties: ['name', 'ean']),
                'ownerNamePartial' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    property: 'owner.name',
                ),
                'searchOwnerNamePartial[:property]' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    properties: ['owner.name'],
                ),
                'ownerNameExact' => new QueryParameter(
                    filter: new ExactFilter(),
                    property: 'owner.name',
                ),
                'searchOwnerNameExact[:property]' => new QueryParameter(
                    filter: new ExactFilter(),
                    properties: ['owner.name'],
                ),
            ],
        ),
        new Get(),
    ]
)]
class Chicken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ean;

    #[ORM\ManyToOne(targetEntity: ChickenCoop::class, inversedBy: 'chickens')]
    #[ORM\JoinColumn(nullable: false)]
    private ChickenCoop $chickenCoop;

    #[ORM\ManyToOne(targetEntity: Owner::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Owner $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(string $ean): self
    {
        $this->ean = $ean;

        return $this;
    }

    public function getChickenCoop(): ?ChickenCoop
    {
        return $this->chickenCoop;
    }

    public function setChickenCoop(?ChickenCoop $chickenCoop): self
    {
        $this->chickenCoop = $chickenCoop;

        return $this;
    }

    public function getOwner(): ?Owner
    {
        return $this->owner;
    }

    public function setOwner(?Owner $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
