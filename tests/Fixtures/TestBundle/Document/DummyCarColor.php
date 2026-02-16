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

use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ODM\Document]
class DummyCarColor
{
    /**
     * @var int The entity Id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[Assert\NotBlank]
    #[ODM\ReferenceOne(targetDocument: DummyCar::class, inversedBy: 'colors', storeAs: 'id')]
    private ?DummyCar $car = null;
    #[ApiFilter(SearchFilter::class)]
    #[Assert\NotBlank]
    #[Serializer\Groups(['colors'])]
    #[ODM\Field(nullable: false)]
    private string $prop = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCar(): ?DummyCar
    {
        return $this->car;
    }

    public function setCar(DummyCar $car): static
    {
        $this->car = $car;

        return $this;
    }

    public function getProp(): string
    {
        return $this->prop;
    }

    public function setProp(string $prop): static
    {
        $this->prop = $prop;

        return $this;
    }
}
