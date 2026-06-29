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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\IriFilterScalarEnum\Game as DocumentGame;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterScalarEnum\Game;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GamePlayMode;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;

final class IriFilterScalarEnumTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Game::class, GamePlayMode::class];
    }

    public function testFilterScalarEnumColumnByIri(): void
    {
        $client = $this->createClient();
        $res = $client->request('GET', '/iri_filter_scalar_enum_games?playMode=/game_play_modes/SINGLE_PLAYER')->toArray();

        $this->assertCount(2, $res['member']);
        foreach ($res['member'] as $game) {
            $this->assertSame('/game_play_modes/SINGLE_PLAYER', $game['playMode']);
        }
    }

    public function testFilterScalarEnumColumnByIriMultiple(): void
    {
        $client = $this->createClient();
        $res = $client->request('GET', '/iri_filter_scalar_enum_games?playMode[]=/game_play_modes/SINGLE_PLAYER&playMode[]=/game_play_modes/CO_OP')->toArray();

        $this->assertCount(3, $res['member']);
    }

    public function testFilterScalarEnumColumnByUnknownIriYieldsNoResult(): void
    {
        $client = $this->createClient();
        $res = $client->request('GET', '/iri_filter_scalar_enum_games?playMode=/game_play_modes/MULTI_PLAYER')->toArray();

        $this->assertCount(0, $res['member']);
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DocumentGame::class : Game::class]);
        $this->loadFixtures();
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DocumentGame::class : Game::class;

        foreach ([
            ['Solo Quest', GamePlayMode::SINGLE_PLAYER],
            ['Lone Wolf', GamePlayMode::SINGLE_PLAYER],
            ['Team Up', GamePlayMode::CO_OP],
        ] as [$name, $playMode]) {
            $game = new $class();
            $game->name = $name;
            $game->playMode = $playMode;
            $manager->persist($game);
        }

        $manager->flush();
    }
}
