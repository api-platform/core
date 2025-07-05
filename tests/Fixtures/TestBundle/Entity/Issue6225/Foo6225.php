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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
#[ORM\Table(name: 'foo6225')]
#[ApiResource(
    operations: [
        new Post(),
        new Patch(),
    ],
    normalizationContext: [
        'groups' => ['Foo:Read'],
    ],
    denormalizationContext: [
        'allow_extra_attributes' => false,
        'groups' => ['Foo:Write'],
    ],
)]
class Foo6225
{
    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid', unique: true)]
    #[Groups(['Foo:Read'])]
    private Uuid $id;

    #[ORM\OneToOne(inversedBy: 'foo', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['Foo:Write', 'Foo:Read'])]
    private Bar6225 $bar;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getBar(): Bar6225
    {
        return $this->bar;
    }

    public function setBar(Bar6225 $bar): static
    {
        $this->bar = $bar;

        return $this;
    }
}
