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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 */
#[ApiResource(extraProperties: ['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]]], filters: ['my_dummy.mongodb.boolean', 'my_dummy.mongodb.date', 'my_dummy.mongodb.exists', 'my_dummy.mongodb.numeric', 'my_dummy.mongodb.order', 'my_dummy.mongodb.range', 'my_dummy.mongodb.search', 'my_dummy.property'])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy')], status: 200, filters: ['my_dummy.mongodb.boolean', 'my_dummy.mongodb.date', 'my_dummy.mongodb.exists', 'my_dummy.mongodb.numeric', 'my_dummy.mongodb.order', 'my_dummy.mongodb.range', 'my_dummy.mongodb.search', 'my_dummy.property'], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy')], status: 200, filters: ['my_dummy.mongodb.boolean', 'my_dummy.mongodb.date', 'my_dummy.mongodb.exists', 'my_dummy.mongodb.numeric', 'my_dummy.mongodb.order', 'my_dummy.mongodb.range', 'my_dummy.mongodb.search', 'my_dummy.property'], operations: [new Get()])]
#[ODM\Document]
class Dummy
{
    /**
     * @var int|null The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int', nullable: true)]
    private $id;
    /**
     * @var string|null The dummy name
     */
    #[ApiProperty(types: ['http://schema.org/name'])]
    #[Assert\NotBlank]
    #[ODM\Field(type: 'string')]
    private $name;
    /**
     * @var string|null The dummy name alias
     */
    #[ApiProperty(types: ['http://schema.org/alternateName'])]
    #[ODM\Field(nullable: true)]
    private $alias;
    /**
     * @var array|null foo
     */
    private ?array $foo = null;
    /**
     * @var string|null A short description of the item
     */
    #[ApiProperty(types: ['http://schema.org/description'])]
    #[ODM\Field(type: 'string', nullable: true)]
    public $description;
    /**
     * @var string|null A dummy
     */
    #[ODM\Field(nullable: true)]
    public $dummy;
    /**
     * @var bool|null A dummy boolean
     */
    #[ODM\Field(type: 'bool', nullable: true)]
    public $dummyBoolean;
    /**
     * @var \DateTime|null A dummy date
     */
    #[ApiProperty(types: ['http://schema.org/DateTime'])]
    #[ODM\Field(type: 'date', nullable: true)]
    public $dummyDate;
    /**
     * @var float|null A dummy float
     */
    #[ODM\Field(type: 'float', nullable: true)]
    public $dummyFloat;
    /**
     * @var float|null A dummy price
     */
    #[ODM\Field(type: 'float', nullable: true)]
    public $dummyPrice;
    /**
     * @var RelatedDummy|null A related dummy
     */
    #[ODM\ReferenceOne(targetDocument: RelatedDummy::class, storeAs: 'id', nullable: true)]
    public $relatedDummy;
    /**
     * @var Collection Several dummies
     */
    #[ODM\ReferenceMany(targetDocument: RelatedDummy::class, storeAs: 'id', nullable: true)]
    public $relatedDummies;
    /**
     * @var array serialize data
     */
    #[ODM\Field(type: 'hash', nullable: true)]
    public $jsonData;
    /**
     * @var array
     */
    #[ODM\Field(type: 'collection', nullable: true)]
    public $arrayData;
    /**
     * @var string|null
     */
    #[ODM\Field(nullable: true)]
    public $nameConverted;
    /**
     * @var RelatedOwnedDummy|null
     */
    #[ODM\ReferenceOne(targetDocument: RelatedOwnedDummy::class, cascade: ['persist'], mappedBy: 'owningDummy', nullable: true)]
    public $relatedOwnedDummy;
    /**
     * @var RelatedOwningDummy|null
     */
    #[ODM\ReferenceOne(targetDocument: RelatedOwningDummy::class, cascade: ['persist'], inversedBy: 'ownedDummy', nullable: true, storeAs: 'id')]
    public $relatedOwningDummy;

    public static function staticMethod(): void
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

    public function isDummyBoolean(): ?bool
    {
        return $this->dummyBoolean;
    }

    public function setDummyBoolean(?bool $dummyBoolean)
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
