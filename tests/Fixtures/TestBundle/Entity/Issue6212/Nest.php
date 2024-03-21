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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6212;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6212\Bird;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6212\Robin;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6212\Wren;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
class Nest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'json')]
    private ?Bird $owner;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?Bird
    {
        return $this->owner;
    }

    public function setOwner(Wren|Robin|null $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
