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

namespace ApiPlatform\GraphQl\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiProperty;

/**
 * Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Dummy
{
    /**
     * @var int|null The id
     */
    private $id;

    /**
     * @var string The dummy name
     */
    #[ApiProperty(iris: ['https://schema.org/name'])]
    private string $name;

    /**
     * @var string|null The dummy name alias
     */
    #[ApiProperty(iris: ['https://schema.org/alternateName'])]
    private $alias;

    /**
     * @var array foo
     */
    private ?array $foo = null;

    /**
     * @var string|null A short description of the item
     */
    #[ApiProperty(iris: ['https://schema.org/description'])]
    public $description;

    /**
     * @var string|null A dummy
     */
    public $dummy;

    /**
     * @var bool|null A dummy boolean
     */
    public ?bool $dummyBoolean = null;
    /**
     * @var \DateTime|null A dummy date
     */
    #[ApiProperty(iris: ['https://schema.org/DateTime'])]
    public $dummyDate;

    /**
     * @var float|null A dummy float
     */
    public $dummyFloat;

    /**
     * @var string|null A dummy price
     */
    public $dummyPrice;

    /**
     * @var array|null serialize data
     */
    public $jsonData = [];

    /**
     * @var array|null
     */
    public $arrayData = [];

    /**
     * @var string|null
     */
    public $nameConverted;

    public static function staticMethod(): void
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAlias($alias): void
    {
        $this->alias = $alias;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setDescription($description): void
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function fooBar($baz): void
    {
    }

    public function getFoo(): ?array
    {
        return $this->foo;
    }

    public function setFoo(?array $foo = null): void
    {
        $this->foo = $foo;
    }

    public function setDummyDate(?\DateTime $dummyDate = null): void
    {
        $this->dummyDate = $dummyDate;
    }

    public function getDummyDate()
    {
        return $this->dummyDate;
    }

    public function setDummyPrice($dummyPrice)
    {
        $this->dummyPrice = $dummyPrice;

        return $this;
    }

    public function getDummyPrice()
    {
        return $this->dummyPrice;
    }

    public function setJsonData($jsonData): void
    {
        $this->jsonData = $jsonData;
    }

    public function getJsonData()
    {
        return $this->jsonData;
    }

    public function setArrayData($arrayData): void
    {
        $this->arrayData = $arrayData;
    }

    public function getArrayData()
    {
        return $this->arrayData;
    }

    public function isDummyBoolean(): ?bool
    {
        return $this->dummyBoolean;
    }

    /**
     * @param bool $dummyBoolean
     */
    public function setDummyBoolean($dummyBoolean): void
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    public function setDummy($dummy = null): void
    {
        $this->dummy = $dummy;
    }

    public function getDummy()
    {
        return $this->dummy;
    }
}
