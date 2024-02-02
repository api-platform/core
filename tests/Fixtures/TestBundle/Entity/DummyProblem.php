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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DummyProblem.
 * Tests features/hal/problem.feature.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource()]
#[ORM\Entity]
class DummyProblem
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string The dummy name
     */
    #[ApiProperty(iris: ['https://schema.org/name'])]
    #[ORM\Column]
    #[Assert\NotBlank]
    private string $name;

    /**
     * @var string|null The dummy name alias
     */
    #[ApiProperty(iris: ['https://schema.org/alternateName'])]
    #[ORM\Column(nullable: true)]
    private $alias;

    /**
     * @var array foo
     */
    private ?array $foo = null;

    /**
     * @var string|null A short description of the item
     */
    #[ApiProperty(iris: ['https://schema.org/description'])]
    #[ORM\Column(nullable: true)]
    public $description;

    /**
     * @var string|null A dummy
     */
    #[ORM\Column(nullable: true)]
    public $dummy;

    /**
     * @var bool|null A dummy boolean
     */
    #[ORM\Column(type: 'boolean', nullable: true)]

    public ?bool $dummyBoolean = null;
    /**
     * @var \DateTime|null A dummy date
     */
    #[ApiProperty(iris: ['https://schema.org/DateTime'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    public $dummyDate;

    /**
     * @var float|null A dummy float
     */
    #[ORM\Column(type: 'float', nullable: true)]
    public $dummyFloat;

    /**
     * @var string|null A dummy price
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public $dummyPrice;

    #[ApiProperty(push: true)]
    #[ORM\ManyToOne(targetEntity: RelatedDummy::class)]
    public ?RelatedDummy $relatedDummy = null;

    #[ORM\ManyToMany(targetEntity: RelatedDummy::class)]
    public Collection|iterable $relatedDummies;

    /**
     * @var array|null serialize data
     */
    #[ORM\Column(type: 'json', nullable: true)]
    public $jsonData = [];

    /**
     * @var array|null
     */
    #[ORM\Column(type: 'simple_array', nullable: true)]
    public $arrayData = [];

    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    public $nameConverted;

    /**
     * @var RelatedOwnedDummy|null
     */
    #[ORM\OneToOne(targetEntity: RelatedOwnedDummy::class, cascade: ['persist'], mappedBy: 'owningDummy')]
    public $relatedOwnedDummy;

    /**
     * @var RelatedOwningDummy|null
     */
    #[ORM\OneToOne(targetEntity: RelatedOwningDummy::class, cascade: ['persist'], inversedBy: 'ownedDummy')]
    public $relatedOwningDummy;

    public static function staticMethod(): void
    {
    }

    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
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

    public function getRelatedDummies(): Collection|iterable
    {
        return $this->relatedDummies;
    }
}
