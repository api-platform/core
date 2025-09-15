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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6225;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'bar6225')]
#[ApiResource]
class Bar6225
{
    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid', unique: true)]
    #[Groups(['Foo:Read', 'Foo:Write'])]
    private Uuid $id;

    #[ORM\OneToOne(mappedBy: 'bar', cascade: ['persist', 'remove'])]
    private ?Foo6225 $foo;

    #[ORM\Column(length: 255)]
    #[Groups(['Foo:Write', 'Foo:Read'])]
    private string $someProperty;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFoo(): Foo6225
    {
        return $this->foo;
    }

    public function setFoo(Foo6225 $foo): static
    {
        $this->foo = $foo;

        return $this;
    }

    public function getSomeProperty(): string
    {
        return $this->someProperty;
    }

    public function setSomeProperty(string $someProperty): static
    {
        $this->someProperty = $someProperty;

        return $this;
    }
}
