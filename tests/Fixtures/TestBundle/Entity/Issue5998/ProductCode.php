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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
#[Get]
#[ORM\Entity]
class ProductCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['product:write'])]
    private ?string $type = null;

    #[ORM\Column(length: 180)]
    #[Groups(['product:write'])]
    private ?string $value = null;

    #[ORM\ManyToOne(inversedBy: 'codes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Issue5998Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    public function getProduct(): ?Issue5998Product
    {
        return $this->product;
    }

    public function setProduct(?Issue5998Product $product): void
    {
        $this->product = $product;
    }
}
