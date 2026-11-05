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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765;

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765MappedAward;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ORM\Entity]
#[Map(target: Issue8765MappedAward::class)]
class Issue8765Award
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public string $name = '';

    #[ORM\ManyToOne(targetEntity: Issue8765Author::class, inversedBy: 'awards')]
    #[Map(if: false)]
    public ?Issue8765Author $author = null;
}
