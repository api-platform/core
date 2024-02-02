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
#[ApiResource(extraProperties: ['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]], 'standard_put' => false, 'rfc_7807_compliant_errors' => false], filters: ['my_dummy.mongodb.boolean', 'my_dummy.mongodb.date', 'my_dummy.mongodb.exists', 'my_dummy.mongodb.numeric', 'my_dummy.mongodb.order', 'my_dummy.mongodb.range', 'my_dummy.mongodb.search', 'my_dummy.property'])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy')], status: 200, filters: ['my_dummy.mongodb.boolean', 'my_dummy.mongodb.date', 'my_dummy.mongodb.exists', 'my_dummy.mongodb.numeric', 'my_dummy.mongodb.order', 'my_dummy.mongodb.range', 'my_dummy.mongodb.search', 'my_dummy.property'], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy')], status: 200, filters: ['my_dummy.mongodb.boolean', 'my_dummy.mongodb.date', 'my_dummy.mongodb.exists', 'my_dummy.mongodb.numeric', 'my_dummy.mongodb.order', 'my_dummy.mongodb.range', 'my_dummy.mongodb.search', 'my_dummy.property'], operations: [new Get()])]
#[ODM\Document]
class Dummy
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int', nullable: true)]
    private ?int $id = null;
    /**
     * @var string|null The dummy name
     */
    #[ApiProperty(iris: ['https://schema.org/name'])]
    #[Assert\NotBlank]
    #[ODM\Field(type: 'string')]
    private $name;
    /**
     * @var string|null The dummy name alias
     */
    #[ApiProperty(iris: ['https://schema.org/alternateName'])]
    #[ODM\Field(nullable: true)]
    private $alias;
    /**
     * @var array|null foo
     */
    private ?array $foo = null;
    /**
     * @var string|null A short description of the item
     */
    #[ApiProperty(iris: ['https://schema.org/description'])]
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
    public ?bool $dummyBoolean = null;
    /**
     * @var \DateTime|null A dummy date
     */
    #[ApiProperty(iris: ['https://schema.org/DateTime'])]
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
    #[ODM\ReferenceOne(targetDocument: RelatedDummy::class, storeAs: 'id', nullable: true)]
    public ?RelatedDummy $relatedDummy = null;
    #[ODM\ReferenceMany(targetDocument: RelatedDummy::class, storeAs: 'id', nullable: true)]
    public Collection|iterable $relatedDummies;
    #[ODM\Field(type: 'hash', nullable: true)]
    public array $jsonData = [];
    #[ODM\Field(type: 'collection', nullable: true)]
    public array $arrayData = [];
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
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
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

    public function setJsonData(array $jsonData): void
    {
        $this->jsonData = $jsonData;
    }

    public function getJsonData(): array
    {
        return $this->jsonData;
    }

    public function setArrayData(array $arrayData): void
    {
        $this->arrayData = $arrayData;
    }

    public function getArrayData(): array
    {
        return $this->arrayData;
    }

    public function getRelatedDummy(): ?RelatedDummy
    {
        return $this->relatedDummy;
    }

    public function setRelatedDummy(RelatedDummy $relatedDummy): void
    {
        $this->relatedDummy = $relatedDummy;
    }

    public function addRelatedDummy(RelatedDummy $relatedDummy): void
    {
        $this->relatedDummies->add($relatedDummy);
    }

    public function getRelatedOwnedDummy()
    {
        return $this->relatedOwnedDummy;
    }

    public function setRelatedOwnedDummy(RelatedOwnedDummy $relatedOwnedDummy): void
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

    public function setRelatedOwningDummy(RelatedOwningDummy $relatedOwningDummy): void
    {
        $this->relatedOwningDummy = $relatedOwningDummy;
    }

    public function isDummyBoolean(): ?bool
    {
        return $this->dummyBoolean;
    }

    public function setDummyBoolean(?bool $dummyBoolean): void
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
