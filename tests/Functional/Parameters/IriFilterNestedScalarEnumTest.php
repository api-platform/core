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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\IriFilterNestedScalarEnum\Tournament as DocumentTournament;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\IriFilterNestedScalarEnum\TournamentPlayer as DocumentTournamentPlayer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterNestedScalarEnum\Tournament;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterNestedScalarEnum\TournamentPlayer;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GamePlayMode;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;

final class IriFilterNestedScalarEnumTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Tournament::class, TournamentPlayer::class, GamePlayMode::class];
    }

    public function testFilterNestedScalarEnumByIri(): void
    {
        $res = $this->createClient()->request('GET', '/iri_filter_nested_scalar_enum_tournament_players?tournamentPlayMode=/game_play_modes/SINGLE_PLAYER')->toArray();

        $names = array_map(static fn (array $player): string => $player['name'], $res['member']);
        sort($names);
        $this->assertSame(['Alice', 'Bob'], $names);
    }

    public function testFilterNestedScalarEnumByIriMultiple(): void
    {
        $res = $this->createClient()->request('GET', '/iri_filter_nested_scalar_enum_tournament_players?tournamentPlayMode[]=/game_play_modes/SINGLE_PLAYER&tournamentPlayMode[]=/game_play_modes/CO_OP')->toArray();

        $this->assertCount(3, $res['member']);
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->recreateSchema($this->isMongoDB() ? [DocumentTournament::class, DocumentTournamentPlayer::class] : [Tournament::class, TournamentPlayer::class]);
        $this->loadFixtures();
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(): void
    {
        $manager = $this->getManager();
        $tournamentClass = $this->isMongoDB() ? DocumentTournament::class : Tournament::class;
        $playerClass = $this->isMongoDB() ? DocumentTournamentPlayer::class : TournamentPlayer::class;

        $solo = new $tournamentClass();
        $solo->name = 'Solo Cup';
        $solo->playMode = GamePlayMode::SINGLE_PLAYER;
        $manager->persist($solo);

        $coop = new $tournamentClass();
        $coop->name = 'Team Cup';
        $coop->playMode = GamePlayMode::CO_OP;
        $manager->persist($coop);

        foreach ([['Alice', $solo], ['Bob', $solo], ['Carol', $coop]] as [$name, $tournament]) {
            $player = new $playerClass();
            $player->name = $name;
            $player->tournament = $tournament;
            $manager->persist($player);
        }

        $manager->flush();
    }
}
