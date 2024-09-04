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

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/aliased-property-search-items'),
    ],
)]
#[ORM\Entity]
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
class AliasedPropertySearchItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false, options: ['default' => ''])]
    public string $name = '';

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    public bool $isValidated = false;

    #[ORM\Column(type: 'datetime', nullable: false, options: ['default' => 'now()'])]
    public ?\DateTimeInterface $dateOfCreation = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => null])]
    public ?bool $nullableBoolProperty = null;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
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
