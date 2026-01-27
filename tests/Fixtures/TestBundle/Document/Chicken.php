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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
#[GetCollection(
    normalizationContext: ['hydra_prefix' => false],
    parameters: [
        'chickenCoop' => new QueryParameter(filter: new IriFilter()),
        'chickenCoopId' => new QueryParameter(filter: new ExactFilter(), property: 'chickenCoop'),
        'name' => new QueryParameter(filter: new ExactFilter()),
        'namePartial' => new QueryParameter(
            filter: new PartialSearchFilter(),
            property: 'name',
        ),
        'nameConverted' => new QueryParameter(filter: new ExactFilter()),
        'nameConvertedAlias' => new QueryParameter(filter: new ExactFilter(), property: 'nameConverted'),
        'nameNotConverted' => new QueryParameter(filter: new ExactFilter()),
        'nameNotConvertedAlias' => new QueryParameter(filter: new ExactFilter(), property: 'nameNotConverted'),
        'autocomplete' => new QueryParameter(filter: new FreeTextQueryFilter(new OrFilter(new ExactFilter())), properties: ['name', 'ean']),
        'q' => new QueryParameter(filter: new FreeTextQueryFilter(new PartialSearchFilter()), properties: ['name', 'ean']),
    ],
)]
class Chicken
{
    #[ODM\Id(type: 'string', strategy: 'INCREMENT')]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $name;

    #[ODM\Field(type: 'string')]
    private string $nameConverted;

    #[ODM\Field(type: 'string')]
    private string $nameNotConverted;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $ean;

    #[ODM\ReferenceOne(targetDocument: ChickenCoop::class, inversedBy: 'chickens')]
    private ?ChickenCoop $chickenCoop = null;

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
        $this->nameConverted = $name;
        $this->nameNotConverted = $name;

        return $this;
    }

    public function getNameConverted(): ?string
    {
        return $this->nameConverted;
    }

    public function getNameNotConverted(): ?string
    {
        return $this->nameNotConverted;
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
}
