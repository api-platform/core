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

namespace ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiFilter(DateFilter::class, strategy: DateFilter::EXCLUDE_NULL)]
#[ApiFilter(BooleanFilter::class)]
#[ApiResource(operations: [new Get(), new Put(), new Delete(), new Post(), new GetCollection()], sunset: '2050-01-01', normalizationContext: ['groups' => ['colors']])]
#[ORM\Entity]
class DummyCar
{
    /**
     * @var DummyCarIdentifier The entity Id
     */
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: DummyCarIdentifier::class, cascade: ['persist'])]
    private DummyCarIdentifier $id;
    #[ApiFilter(SearchFilter::class, properties: ['colors.prop' => 'ipartial', 'colors' => 'exact'])]
    #[ORM\OneToMany(targetEntity: DummyCarColor::class, mappedBy: 'car')]
    private Collection|iterable $colors;
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[ORM\OneToMany(targetEntity: DummyCarColor::class, mappedBy: 'car')]
    private Collection|iterable|null $secondColors = null;
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[ORM\OneToMany(targetEntity: DummyCarColor::class, mappedBy: 'car')]
    private Collection|iterable|null $thirdColors = null;
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[ORM\ManyToMany(targetEntity: UuidIdentifierDummy::class, indexBy: 'uuid')]
    #[ORM\JoinColumn(name: 'car_id', referencedColumnName: 'id_id')]
    #[ORM\InverseJoinColumn(name: 'uuid_uuid', referencedColumnName: 'uuid')]
    #[ORM\JoinTable(name: 'uuid_cars')]
    private Collection|iterable|null $uuid = null;

    #[ApiFilter(SearchFilter::class, strategy: 'partial')]
    #[ORM\Column(type: 'string')]
    private string $name;
    #[ORM\Column(type: 'boolean')]
    private bool $canSell;
    #[ORM\Column(type: 'datetime')]
    private \DateTime $availableAt;
    #[ApiFilter(SearchFilter::class, strategy: SearchFilter::STRATEGY_IEXACT)]
    #[ORM\Column]
    private string $brand = 'DummyBrand';
    #[ORM\Embedded(class: 'DummyCarInfo')]
    private DummyCarInfo $info;

    public function __construct()
    {
        $this->id = new DummyCarIdentifier();
        $this->colors = new ArrayCollection();
        $this->info = new DummyCarInfo();
    }

    public function getId(): DummyCarIdentifier
    {
        return $this->id;
    }

    public function getColors(): Collection|iterable
    {
        return $this->colors;
    }

    public function setColors(Collection|iterable $colors): self
    {
        $this->colors = $colors;

        return $this;
    }

    public function getSecondColors(): ?iterable
    {
        return $this->secondColors;
    }

    public function setSecondColors($secondColors): void
    {
        $this->secondColors = $secondColors;
    }

    public function getThirdColors(): ?iterable
    {
        return $this->thirdColors;
    }

    public function setThirdColors($thirdColors): void
    {
        $this->thirdColors = $thirdColors;
    }

    public function getUuid(): ?iterable
    {
        return $this->uuid;
    }

    public function setUuid($uuid): void
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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCanSell(): bool
    {
        return $this->canSell;
    }

    public function setCanSell(bool $canSell): void
    {
        $this->canSell = $canSell;
    }

    public function getAvailableAt(): \DateTime
    {
        return $this->availableAt;
    }

    public function setAvailableAt(\DateTime $availableAt): void
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
