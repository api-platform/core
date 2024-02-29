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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Annotation as Serializer;

#[ApiResource(operations: [new Get(openapi: new OpenApiOperation(tags: [])), new Put(), new Delete(), new Post(), new GetCollection()], sunset: '2050-01-01', normalizationContext: ['groups' => ['colors']])]
class DummyCar
{
    /**
     * @var DummyCarIdentifier The entity Id
     */
    private DummyCarIdentifier $id;
    #[Serializer\Groups(['colors'])]
    private iterable $colors;
    #[Serializer\Groups(['colors'])]
    private ?iterable $secondColors = null;
    #[Serializer\Groups(['colors'])]
    private ?iterable $thirdColors = null;
    #[Serializer\Groups(['colors'])]
    private ?iterable $uuid = null;

    private string $name;
    private bool $canSell;
    private \DateTime $availableAt;
    #[Serializer\Groups(['colors'])]
    #[Serializer\SerializedName('carBrand')]
    private string $brand = 'DummyBrand';
    private DummyCarInfo $info;

    public function __construct()
    {
        $this->id = new DummyCarIdentifier();
        $this->colors = [];
        $this->info = new DummyCarInfo();
    }

    public function getId(): DummyCarIdentifier
    {
        return $this->id;
    }

    public function getColors(): iterable
    {
        return $this->colors;
    }

    public function setColors(iterable $colors): self
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
