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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8113;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [new Get()])]
#[ORM\Entity]
class BarJoinedA extends BarJoined
{
    #[ORM\Column]
    #[Groups(['foo'])]
    private ?string $y = null;

    public function getY(): ?string
    {
        return $this->y;
    }

    public function setY(?string $y): void
    {
        $this->y = $y;
    }
}
