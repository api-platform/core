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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(attributes={"filters"={"my_dummy.search", "my_dummy.order", "my_dummy.date", "my_dummy.range", "my_dummy.boolean", "my_dummy.numeric"}})
 * @ORM\Entity
 */
class Dummy
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ORM\Column
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var string The dummy name alias
     *
     * @ORM\Column(nullable=true)
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
     * @ORM\Column(nullable=true)
     * @ApiProperty(iri="https://schema.org/description")
     */
    public $description;

    /**
     * @var string A dummy
     *
     * @ORM\Column(nullable=true)
     */
    public $dummy;

    /**
     * @var bool A dummy boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $dummyBoolean;

    /**
     * @var \DateTime A dummy date
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime
     */
    public $dummyDate;

    /**
     * @var string A dummy float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    public $dummyFloat;

    /**
     * @var string A dummy price
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    public $dummyPrice;

    /**
     * @var RelatedDummy A related dummy
     *
     * @ORM\ManyToOne(targetEntity="RelatedDummy")
     */
    public $relatedDummy;

    /**
     * @var ArrayCollection Several dummies
     *
     * @ORM\ManyToMany(targetEntity="RelatedDummy")
     */
    public $relatedDummies;

    /**
     * @var array serialize data
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    public $jsonData;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    public $nameConverted;

    public static function staticMethod()
    {
    }

    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
        $this->jsonData = [];
    }

    public function getId()
    {
        return $this->id;
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

    public function setFoo(array $foo = null)
    {
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
