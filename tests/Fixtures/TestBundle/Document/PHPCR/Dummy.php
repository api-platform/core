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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\PHPCR;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Dummy.
 *
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @ApiResource(attributes={
 *     "filters"={
 *         "my_dummy.phpcr.boolean"
 *     }
 * })
 * @PHPCRODM\Document(referenceable=true)
 */
class Dummy
{
    /**
     * @var int The id
     *
     * @PHPCRODM\Id
     */
    private $id;

    /**
     * @PHPCRODM\Node
     */
    public $node;

    /**
     * @PHPCRODM\ParentDocument()
     */
    public $parentDocument;

    /**
     * @var string The dummy name
     *
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     * @PHPCRODM\Field()
     */
    private $name;

    /**
     * @var string The dummy name alias
     *
     * @ApiProperty(iri="https://schema.org/alternateName")
     * @PHPCRODM\Field()
     */
    private $alias;

    /**
     * @var array foo
     */
    private $foo;

    /**
     * @var string A short description of the item
     *
     * @ApiProperty(iri="https://schema.org/description")
     * @PHPCRODM\Field()
     */
    public $description;

    /**
     * @var string A dummy
     *
     * @PHPCRODM\Field()
     */
    public $dummy;

    /**
     * @var bool A dummy boolean
     *
     * @PHPCRODM\Field(type="boolean")
     */
    public $dummyBoolean;

    /**
     * @var \DateTime A dummy date
     *
     * @Assert\DateTime
     * @PHPCRODM\Field(type="date")
     */
    public $dummyDate;

    /**
     * @var string A dummy float
     *
     * @PHPCRODM\Field(type="float")
     */
    public $dummyFloat;

    /**
     * @var string A dummy price
     *
     * @PHPCRODM\Field(type="int")
     */
    public $dummyPrice;

    /**
     * @var RelatedDummy A related dummy
     *
     * @PHPCRODM\ReferenceOne(targetDocument="RelatedDummy")
     */
    public $relatedDummy;

    /**
     * @PHPCRODM\ReferenceMany(targetDocument="RelatedDummy")
     * @ApiSubresource
     */
    public $relatedDummies;

    /**
     * @var array serialize data
     *
     * @PHPCRODM\Field(type="text")
     */
    public $jsonData;

    /**
     * @var array
     *
     * @PHPCRODM\Field(type="raw")
     */
    public $arrayData;

    /**
     * @var string
     *
     * @PHPCRODM\Field(type="string")
     */
    public $nameConverted;

    /**
     * @var RelatedOwnedDummy
     *
     * @PHPCRODM\ReferenceOne(targetDocument="RelatedOwnedDummy", cascade={"persist"})
     */
    public $relatedOwnedDummy;

    /**
     * @var RelatedOwningDummy
     *
     * @PHPCRODM\ReferenceOne(targetDocument="RelatedOwningDummy", cascade={"persist"})
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
