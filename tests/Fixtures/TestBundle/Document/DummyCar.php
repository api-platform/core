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

use ApiPlatform\Doctrine\Odm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation as Serializer;

#[ApiFilter(DateFilter::class, strategy: DateFilter::EXCLUDE_NULL)]
#[ApiFilter(BooleanFilter::class)]
#[ApiFilter(PropertyFilter::class, arguments: ['parameterName' => 'foobar'])]
#[ApiFilter(GroupFilter::class, arguments: ['parameterName' => 'foobargroups'])]
#[ApiFilter(GroupFilter::class, arguments: ['parameterName' => 'foobargroups_override'], id: 'override')]
#[ApiResource(operations: [new Get(openapiContext: ['tags' => []]), new Put(), new Delete(), new Post(), new GetCollection()], sunset: '2050-01-01', normalizationContext: ['groups' => ['colors']])]
#[ODM\Document]
class DummyCar
{
    /**
     * @var int The entity Id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var mixed Something else
     */
    #[ApiFilter(SearchFilter::class, properties: ['colors.prop' => 'ipartial', 'colors' => 'exact'])]
    #[Serializer\Groups(['colors'])]
    #[ODM\ReferenceMany(targetDocument: DummyCarColor::class, mappedBy: 'car')]
    private $colors;
    /**
     * @var mixed Something else
     */
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[Serializer\Groups(['colors'])]
    #[ODM\ReferenceMany(targetDocument: DummyCarColor::class, mappedBy: 'car')]
    private mixed $secondColors = null;
    /**
     * @var mixed Something else
     */
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[Serializer\Groups(['colors'])]
    #[ODM\ReferenceMany(targetDocument: DummyCarColor::class, mappedBy: 'car')]
    private mixed $thirdColors = null;
    /**
     * @var mixed Something else
     */
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[Serializer\Groups(['colors'])]
    #[ODM\ReferenceMany(targetDocument: UuidIdentifierDummy::class)]
    private mixed $uuid = null;
    #[ApiFilter(SearchFilter::class, strategy: 'partial')]
    #[ODM\Field(type: 'string')]
    private ?string $name = null;
    #[ODM\Field(type: 'bool')]
    private ?bool $canSell = null;
    #[ODM\Field(type: 'date')]
    private ?\DateTime $availableAt = null;
    #[Serializer\Groups(['colors'])]
    #[Serializer\SerializedName('carBrand')]
    #[ODM\Field]
    private string $brand = 'DummyBrand';

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
