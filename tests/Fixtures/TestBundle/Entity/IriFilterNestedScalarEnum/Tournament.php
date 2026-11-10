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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterNestedScalarEnum;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GamePlayMode;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    shortName: 'IriFilterNestedScalarEnumTournament',
    operations: [
        new Get(),
    ]
)]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name;

    #[ORM\Column(type: 'string', enumType: GamePlayMode::class)]
    public GamePlayMode $playMode = GamePlayMode::SINGLE_PLAYER;

    public function getId(): ?int
    {
        return $this->id;
    }
}
