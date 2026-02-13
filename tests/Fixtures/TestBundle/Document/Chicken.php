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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
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
                    filter: new PartialSearchFilter(false),
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
    #[ODM\Id(type: 'string', strategy: 'INCREMENT')]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $name;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $ean;

    #[ODM\ReferenceOne(targetDocument: ChickenCoop::class, inversedBy: 'chickens')]
    private ?ChickenCoop $chickenCoop = null;

    #[ODM\EmbedOne(targetDocument: Owner::class)]
    private ?Owner $owner = null;

    public function getId(): ?string
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
