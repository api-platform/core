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

use ApiPlatform\Doctrine\Odm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use ApiPlatform\Doctrine\Odm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Odm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/aliased-property-search-documents'),
    ],
)]
#[ODM\Document]
#[ApiFilter(
    SearchFilter::class,
    properties: ['name' => 'exact'],
    arguments: ['propertyAliases' => ['name' => 'aliasedName']]
)]
#[ApiFilter(
    BooleanFilter::class,
    properties: ['isValidated'],
    arguments: ['propertyAliases' => ['isValidated' => 'aliasedIsValidated']]
)]
#[ApiFilter(
    DateFilter::class,
    properties: ['dateOfCreation'],
    arguments: ['propertyAliases' => ['dateOfCreation' => 'aliasedDateOfCreation']]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: ['nullableBoolProperty'],
    arguments: ['propertyAliases' => ['nullableBoolProperty' => 'aliasedNullableBoolProperty']]
)]
#[ApiFilter(
    NumericFilter::class,
    properties: ['timesExecuted'],
    arguments: ['propertyAliases' => ['timesExecuted' => 'aliasedTimesExecuted']]
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['timesExecuted'],
    arguments: ['orderParameterName' => 'customOrder', 'propertyAliases' => ['timesExecuted' => 'aliasedTimesExecuted']]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: ['timesExecuted'],
    arguments: ['propertyAliases' => ['timesExecuted' => 'aliasedTimesExecuted']]
)]
class AliasedPropertySearchItemDocument
{
    #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    #[ODM\Index]
    public string $name = '';

    #[ODM\Field(type: 'bool')]
    public bool $isValidated = false;

    #[ODM\Field(type: 'date')]
    public ?\DateTimeInterface $dateOfCreation = null;

    #[ODM\Field(type: 'bool', nullable: true)]
    public ?bool $nullableBoolProperty = null;

    #[ODM\Field(type: 'int')]
    public ?int $timesExecuted = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isValidated(): bool
    {
        return $this->isValidated;
    }

    public function setIsValidated(bool $isValidated): void
    {
        $this->isValidated = $isValidated;
    }

    public function getTimesExecuted(): ?int
    {
        return $this->timesExecuted;
    }

    public function setTimesExecuted(?int $timesExecuted): void
    {
        $this->timesExecuted = $timesExecuted;
    }

    public function getNullableBoolProperty(): ?bool
    {
        return $this->nullableBoolProperty;
    }

    public function setNullableBoolProperty(?bool $nullableBoolProperty): void
    {
        $this->nullableBoolProperty = $nullableBoolProperty;
    }

    public function getDateOfCreation(): ?\DateTimeInterface
    {
        return $this->dateOfCreation;
    }

    public function setDateOfCreation(?\DateTimeInterface $dateOfCreation): void
    {
        $this->dateOfCreation = $dateOfCreation;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
