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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @ApiResource(attributes={
 *     "filters"={
 *         "my_dummy.mongodb.boolean",
 *         "my_dummy.mongodb.date",
 *         "my_dummy.mongodb.exists",
 *         "my_dummy.mongodb.numeric",
 *         "my_dummy.mongodb.order",
 *         "my_dummy.mongodb.range",
 *         "my_dummy.mongodb.search",
 *         "my_dummy.property"
 *     }
 * })
 * @ODM\Document
 */
class Dummy
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ODM\Field(type="string")
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var string The dummy name alias
     *
     * @ODM\Field(nullable=true)
     * @ApiProperty(iri="https://schema.org/alternateName")
     */
    private $alias;

    /**
     * @var array foo
     */
    private $foo;

    /**
     * @var string A short description of the item
     *
     * @ODM\Field(type="string", nullable=true)
     * @ApiProperty(iri="https://schema.org/description")
     */
    public $description;

    /**
     * @var string A dummy
     *
     * @ODM\Field(nullable=true)
     */
    public $dummy;

    /**
     * @var bool A dummy boolean
     *
     * @ODM\Field(type="boolean", nullable=true)
     */
    public $dummyBoolean;

    /**
     * @var \DateTime A dummy date
     *
     * @ODM\Field(type="date", nullable=true)
     * @Assert\DateTime
     */
    public $dummyDate;

    /**
     * @var string A dummy float
     *
     * @ODM\Field(type="float", nullable=true)
     */
    public $dummyFloat;

    /**
     * @var string A dummy price
     *
     * @ODM\Field(type="float", nullable=true)
     */
    public $dummyPrice;

    /**
     * @var RelatedDummy A related dummy
     *
     * @ODM\ReferenceOne(targetDocument=RelatedDummy::class, storeAs="id", nullable=true)
     */
    public $relatedDummy;

    /**
     * @var ArrayCollection Several dummies
     *
     * @ODM\ReferenceMany(targetDocument=RelatedDummy::class, storeAs="id", nullable=true)
     * @ApiSubresource
     */
    public $relatedDummies;

    /**
     * @var array serialize data
     *
     * @ODM\Field(type="hash", nullable=true)
     */
    public $jsonData;

    /**
     * @var array
     *
     * @ODM\Field(type="collection", nullable=true)
     */
    public $arrayData;

    /**
     * @var string
     *
     * @ODM\Field(nullable=true)
     */
    public $nameConverted;

    /**
     * @var RelatedOwnedDummy
     *
     * @ODM\ReferenceOne(targetDocument=RelatedOwnedDummy::class, cascade={"persist"}, mappedBy="owningDummy", nullable=true)
     */
    public $relatedOwnedDummy;

    /**
     * @var RelatedOwningDummy
     *
     * @ODM\ReferenceOne(targetDocument=RelatedOwningDummy::class, cascade={"persist"}, inversedBy="ownedDummy", nullable=true, storeAs="id")
     */
    public $relatedOwningDummy;

    public static function staticMethod()
    {
    }

    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
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

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
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

    public function hasRole($role)
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

    public function setRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummy = $relatedDummy;
    }

    public function addRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummies->add($relatedDummy);
    }

    public function getRelatedOwnedDummy()
    {
        return $this->relatedOwnedDummy;
    }

    public function setRelatedOwnedDummy(RelatedOwnedDummy $relatedOwnedDummy)
    {
        $this->relatedOwnedDummy = $relatedOwnedDummy;

        if ($this !== $this->relatedOwnedDummy->getOwningDummy()) {
            $this->relatedOwnedDummy->setOwningDummy($this);
        }
    }

    public function getRelatedOwningDummy()
    {
        return $this->relatedOwningDummy;
    }

    public function setRelatedOwningDummy(RelatedOwningDummy $relatedOwningDummy)
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
}
