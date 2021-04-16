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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy as DummyModel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     dataModel=DummyModel::class,
 *     attributes={
 *         "filters"={
 *             "my_dummy.boolean",
 *             "my_dummy.date",
 *             "my_dummy.exists",
 *             "my_dummy.numeric",
 *             "my_dummy.order",
 *             "my_dummy.range",
 *             "my_dummy.search",
 *             "my_dummy.property"
 *         }
 *     }
 * )
 */
class Dummy
{
    /**
     * @var int The id
     *
     * @ApiProperty(identifier=true)
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var ?string The dummy name alias
     *
     * @ApiProperty(iri="https://schema.org/alternateName")
     */
    private $alias;

    /**
     * @var array foo
     *
     * @ApiProperty(virtual=true)
     */
    private $foo;

    /**
     * @var ?string A short description of the item
     *
     * @ApiProperty(iri="https://schema.org/description")
     */
    public $description;

    /**
     * @var ?string A dummy
     */
    public $dummy;

    /**
     * @var ?bool A dummy boolean
     */
    public $dummyBoolean;

    /**
     * @var ?\DateTime A dummy date
     *
     * @ApiProperty(iri="http://schema.org/DateTime")
     */
    public $dummyDate;

    /**
     * @var ?string A dummy float
     */
    public $dummyFloat;

    /**
     * @var ?string A dummy price
     */
    public $dummyPrice;

    /**
     * @var ?RelatedDummy A related dummy
     *
     * @ApiProperty(push=true)
     */
    public $relatedDummy;

    /**
     * @var RelatedDummy[] Several dummies
     *
     * @ApiSubresource
     */
    public $relatedDummies;

    /**
     * @var array serialize data
     */
    public $jsonData;

    /**
     * @var array
     */
    public $arrayData;

    /**
     * @var ?string
     */
    public $nameConverted;

    /**
     * @var ?RelatedOwnedDummy
     */
    public $relatedOwnedDummy;

    /**
     * @var ?RelatedOwningDummy
     */
    public $relatedOwningDummy;

    public static function staticMethod()
    {
    }

    public function __construct()
    {
        $this->relatedDummies = [];
        $this->jsonData = [];
        $this->arrayData = [];
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function fooBar($baz)
    {
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function setFoo(array $foo = null)
    {
        $this->foo = $foo;
    }

    public function setDummyDate(\DateTime $dummyDate = null)
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

    public function setJsonData($jsonData)
    {
        $this->jsonData = $jsonData;
    }

    public function getJsonData()
    {
        return $this->jsonData;
    }

    public function setArrayData($arrayData)
    {
        $this->arrayData = $arrayData;
    }

    public function getArrayData()
    {
        return $this->arrayData;
    }

    public function getRelatedDummy()
    {
        return $this->relatedDummy;
    }

    public function setRelatedDummy(?RelatedDummy $relatedDummy)
    {
        $this->relatedDummy = $relatedDummy;
    }

    public function addRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummies[] = $relatedDummy;
    }

    public function getRelatedOwnedDummy()
    {
        return $this->relatedOwnedDummy;
    }

    public function setRelatedOwnedDummy(?RelatedOwnedDummy $relatedOwnedDummy)
    {
        $this->relatedOwnedDummy = $relatedOwnedDummy;

        if (null === $relatedOwnedDummy) {
            return;
        }

        if ($this !== $this->relatedOwnedDummy->getOwningDummy()) {
            $this->relatedOwnedDummy->setOwningDummy($this);
        }
    }

    public function getRelatedOwningDummy()
    {
        return $this->relatedOwningDummy;
    }

    public function setRelatedOwningDummy(?RelatedOwningDummy $relatedOwningDummy)
    {
        $this->relatedOwningDummy = $relatedOwningDummy;
    }

    /**
     * @return bool
     */
    public function isDummyBoolean()
    {
        return $this->dummyBoolean;
    }

    /**
     * @param bool $dummyBoolean
     */
    public function setDummyBoolean($dummyBoolean)
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    public function setDummy($dummy = null)
    {
        $this->dummy = $dummy;
    }

    public function getDummy()
    {
        return $this->dummy;
    }

    public function getRelatedDummies()
    {
        return $this->relatedDummies;
    }
}
