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

namespace ApiPlatform\Core\Tests\Bridge\Eloquent;

use ApiPlatform\Core\Bridge\Eloquent\DataPersister;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group eloquent
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DataPersisterTest extends TestCase
{
    use ProphecyTrait;

    private $databaseManagerProphecy;
    private $dataPersister;

    protected function setUp(): void
    {
        $this->databaseManagerProphecy = $this->prophesize(DatabaseManager::class);
        $this->dataPersister = new DataPersister($this->databaseManagerProphecy->reveal());
    }

    /**
     * @dataProvider provideSupportsCases
     */
    public function testSupports($data, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->dataPersister->supports($data));
    }

    public function provideSupportsCases(): \Generator
    {
        yield 'not supported' => [new \stdClass(), false];
        yield 'supported' => [new Dummy(), true];
    }

    public function testPersist(): void
    {
        $relatedModelProphecy = $this->prophesize(Model::class);
        $relatedModelProphecy->save()->shouldBeCalled();
        $relatedModelProphecy->getRelations()->willReturn([]);
        $relatedModelProphecy->push()->shouldBeCalled();
        $relatedModel = $relatedModelProphecy->reveal();

        $relatedModelCollection = new Collection([$relatedModel]);

        $belongsToProphecy = $this->prophesize(BelongsTo::class);
        $belongsToProphecy->associate($relatedModel)->shouldBeCalled();

        $hasManyProphecy = $this->prophesize(HasMany::class);
        $hasManyProphecy->saveMany($relatedModelCollection)->shouldBeCalled();

        $modelProphecy = $this->prophesize(Dummy::class);
        $modelProphecy->save()->shouldBeCalledOnce();
        $modelProphecy->relatedDummy()->willReturn($belongsToProphecy->reveal());
        $modelProphecy->relatedDummies()->willReturn($hasManyProphecy->reveal());
        $modelProphecy->getRelations()->willReturn([
            'relatedDummy' => $relatedModel,
            'relatedDummies' => $relatedModelCollection,
        ]);
        $modelProphecy->push()->shouldBeCalledOnce();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->transaction(Argument::that(static function ($callback) {
            $callback();

            return true;
        }))->shouldBeCalled();
        $this->databaseManagerProphecy->connection()->willReturn($connectionProphecy->reveal());

        $modelProphecy->refresh()->shouldBeCalled();

        $model = $modelProphecy->reveal();

        self::assertSame($model, $this->dataPersister->persist($model));
    }

    public function testRemove(): void
    {
        $modelProphecy = $this->prophesize(Model::class);

        $modelProphecy->delete()->shouldBeCalled();

        $this->dataPersister->remove($modelProphecy->reveal());
    }
}
