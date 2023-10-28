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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource]
class TestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read', 'write'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'write'])]
    private ?string $nullableString = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read', 'write'])]
    private ?int $nullableInt = null;

    #[ORM\ManyToOne(inversedBy: 'tests')]
    private ?BagOfTests $bagOfTests = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNullableString(): ?string
    {
        return $this->nullableString;
    }

    public function setNullableString(?string $nullableString): static
    {
        $this->nullableString = $nullableString;

        return $this;
    }

    public function getNullableInt(): ?int
    {
        return $this->nullableInt;
    }

    public function setNullableInt(?int $nullableInt): static
    {
        $this->nullableInt = $nullableInt;

        return $this;
    }

    public function getBagOfTests(): ?BagOfTests
    {
        return $this->bagOfTests;
    }

    public function setBagOfTests(?BagOfTests $bagOfTests): static
    {
        $this->bagOfTests = $bagOfTests;

        return $this;
    }
}
