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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Custom identifier dummy.
 */
#[ApiResource]
#[ORM\Entity]
class GuidIdentifierDummy
{
    #[ORM\Column(type: 'guid')]
    #[ORM\Id]
    private ?string $guid = null;
    #[ORM\Column(length: 30)]
    private ?string $name = null;

    public function getGuid(): ?string
    {
        return $this->guid;
    }

    public function setGuid(string $guid): void
    {
        $this->guid = $guid;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
