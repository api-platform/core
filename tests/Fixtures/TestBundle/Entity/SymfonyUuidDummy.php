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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource]
#[ORM\Entity]
class SymfonyUuidDummy
{
    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private Uuid|UuidV4 $id;
    #[ORM\Column(nullable: true)]
    private ?string $number = null;

    public function __construct(?Uuid $id = null)
    {
        $this->id = $id ?? Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

        return $this;
    }
}
