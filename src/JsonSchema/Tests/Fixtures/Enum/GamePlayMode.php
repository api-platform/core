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

namespace ApiPlatform\JsonSchema\Tests\Fixtures\Enum;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\Metadata\Get;

#[Get(description: 'Indicates whether this game is multi-player, co-op or single-player.', provider: self::class.'::getCase')]
#[GetCollection(provider: self::class.'::getCases')]
#[Query(provider: self::class.'::getCase')]
#[QueryCollection(provider: self::class.'::getCases', paginationEnabled: false)]
enum GamePlayMode: string
{
    /* Co-operative games, where you play on the same team with friends. */
    case CO_OP = 'CoOp';

    /* Requiring or allowing multiple human players to play simultaneously. */
    case MULTI_PLAYER = 'MultiPlayer';

    /* Which is played by a lone player. */
    case SINGLE_PLAYER = 'SinglePlayer';

    public function getId(): string
    {
        return $this->name;
    }

    public static function getCase(Operation $operation, array $uriVariables): GamePlayMode
    {
        $name = $uriVariables['id'] ?? null;

        return \constant(self::class."::$name");
    }

    public static function getCases(): array
    {
        return self::cases();
    }
}
