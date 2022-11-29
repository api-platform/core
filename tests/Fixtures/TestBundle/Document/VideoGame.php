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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GamePlayMode;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource]
#[ODM\Document]
class VideoGame
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    public string $name;

    #[ODM\Field(type: 'string', enumType: GamePlayMode::class)]
    public GamePlayMode $playMode = GamePlayMode::SINGLE_PLAYER;

    public function getId(): ?int
    {
        return $this->id;
    }
}
