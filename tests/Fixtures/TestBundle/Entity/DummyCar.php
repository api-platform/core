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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * @ApiFilter (DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter (BooleanFilter::class)
 * @ApiFilter (PropertyFilter::class, arguments={"parameterName"="foobar"})
 * @ApiFilter (GroupFilter::class, arguments={"parameterName"="foobargroups"})
 * @ApiFilter (GroupFilter::class, arguments={"parameterName"="foobargroups_override"}, id="override")
 */
#[ApiResource(operations: [new Get(openapiContext: ['tags' => []]), new Put(), new Delete(), new Post(), new GetCollection()], sunset: '2050-01-01', normalizationContext: ['groups' => ['colors']])]
#[ORM\Entity]
class DummyCar
{
    /**
     * @var DummyCarIdentifier The entity Id
     */
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: 'DummyCarIdentifier', cascade: ['persist'])]
    private readonly \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCarIdentifier $id;
    /**
     * @var mixed Something else
     *
     * @ApiFilter(SearchFilter::class, properties={"colors.prop"="ipartial", "colors"="exact"})
     */
    #[ORM\OneToMany(targetEntity: 'DummyCarColor', mappedBy: 'car')]
    #[Serializer\Groups(['colors'])]
    private $colors;
    /**
     * @var mixed Something else
     *
     * @ApiFilter(SearchFilter::class, strategy="exact")
     */
    #[ORM\OneToMany(targetEntity: 'DummyCarColor', mappedBy: 'car')]
    #[Serializer\Groups(['colors'])]
    private ?mixed $secondColors = null;
    /**
     * @var mixed Something else
     *
     * @ApiFilter(SearchFilter::class, strategy="exact")
     */
    #[ORM\OneToMany(targetEntity: 'DummyCarColor', mappedBy: 'car')]
    #[Serializer\Groups(['colors'])]
    private ?mixed $thirdColors = null;
    /**
     * @var mixed Something else
     *
     * @ApiFilter(SearchFilter::class, strategy="exact")
     */
    #[ORM\ManyToMany(targetEntity: 'UuidIdentifierDummy', indexBy: 'uuid')]
    #[Serializer\Groups(['colors'])]
    private ?mixed $uuid = null;
    /**
     * @ApiFilter(SearchFilter::class, strategy="partial")
     */
    #[ORM\Column(type: 'string')]
    private ?string $name = null;
    #[ORM\Column(type: 'boolean')]
    private ?bool $canSell = null;
    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $availableAt = null;
    #[Serializer\Groups(['colors'])]
    #[Serializer\SerializedName('carBrand')]
    #[ORM\Column]
    private string $brand = 'DummyBrand';
    #[ORM\Embedded(class: 'DummyCarInfo')]
    private \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCarInfo $info;

    public function __construct()
    {
        $this->id = new DummyCarIdentifier();
        $this->colors = new ArrayCollection();
        $this->info = new DummyCarInfo();
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

    /**
     * Get name.
     */
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

    public function getInfo(): DummyCarInfo
    {
        return $this->info;
    }

    public function setInfo(DummyCarInfo $info): void
    {
        $this->info = $info;
    }
}
