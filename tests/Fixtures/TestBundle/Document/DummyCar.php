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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * @ApiResource(
 *     itemOperations={"get"={"swagger_context"={"tags"={}}, "openapi_context"={"tags"={}}}, "put", "delete"},
 *     attributes={
 *         "sunset"="2050-01-01",
 *         "normalization_context"={"groups"={"colors"}}
 *     }
 * )
 * @ODM\Document
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(PropertyFilter::class, arguments={"parameterName"="foobar"})
 * @ApiFilter(GroupFilter::class, arguments={"parameterName"="foobargroups"})
 * @ApiFilter(GroupFilter::class, arguments={"parameterName"="foobargroups_override"}, id="override")
 */
class DummyCar
{
    /**
     * @var int The entity Id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var mixed Something else
     *
     * @ODM\ReferenceMany(targetDocument=DummyCarColor::class, mappedBy="car")
     *
     * @Serializer\Groups({"colors"})
     * @ApiFilter(SearchFilter::class, properties={"colors.prop"="ipartial", "colors"="exact"})
     */
    private $colors;

    /**
     * @var mixed Something else
     *
     * @ODM\ReferenceMany(targetDocument=DummyCarColor::class, mappedBy="car")
     *
     * @Serializer\Groups({"colors"})
     * @ApiFilter(SearchFilter::class, strategy="exact")
     */
    private $secondColors;

    /**
     * @var mixed Something else
     *
     * @ODM\ReferenceMany(targetDocument=DummyCarColor::class, mappedBy="car")
     *
     * @Serializer\Groups({"colors"})
     * @ApiFilter(SearchFilter::class, strategy="exact")
     */
    private $thirdColors;

    /**
     * @var mixed Something else
     *
     * @ODM\ReferenceMany(targetDocument=UuidIdentifierDummy::class)
     *
     * @Serializer\Groups({"colors"})
     * @ApiFilter(SearchFilter::class, strategy="exact")
     */
    private $uuid;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     * @ApiFilter(SearchFilter::class, strategy="partial")
     */
    private $name;

    /**
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    private $canSell;

    /**
     * @var \DateTime
     *
     * @ODM\Field(type="date")
     */
    private $availableAt;

    /**
     * @var string
     *
     * @Serializer\Groups({"colors"})
     * @Serializer\SerializedName("carBrand")
     *
     * @ODM\Field
     */
    private $brand = 'DummyBrand';

    public function __construct()
    {
        $this->colors = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getColors()
    {
        return $this->colors;
    }

    public function setColors($colors): self
    {
        $this->colors = $colors;

        return $this;
    }

    public function getSecondColors()
    {
        return $this->secondColors;
    }

    public function setSecondColors($secondColors)
    {
        $this->secondColors = $secondColors;
    }

    public function getThirdColors()
    {
        return $this->thirdColors;
    }

    public function setThirdColors($thirdColors)
    {
        $this->thirdColors = $thirdColors;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getCanSell(): bool
    {
        return $this->canSell;
    }

    public function setCanSell(bool $canSell)
    {
        $this->canSell = $canSell;
    }

    public function getAvailableAt(): \DateTime
    {
        return $this->availableAt;
    }

    public function setAvailableAt(\DateTime $availableAt)
    {
        $this->availableAt = $availableAt;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }
}
