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

use ApiPlatform\Core\Bridge\Eloquent\BuilderFactoryInterface;
use ApiPlatform\Core\Bridge\Eloquent\ItemDataProvider;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

/**
 * @group eloquent
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ItemDataProviderTest extends TestCase
{
    use ProphecyTrait;

    private $builderFactoryProphecy;
    private $dataProvider;

    protected function setUp(): void
    {
        $this->builderFactoryProphecy = $this->prophesize(BuilderFactoryInterface::class);
        $this->dataProvider = new ItemDataProvider($this->builderFactoryProphecy->reveal());
    }

    public function testGetItemSingleIdentifier(): void
    {
        $result = new \stdClass();

        $queryBuilder = $this->prophesize(Builder::class);
        $queryBuilder->where('id', 1)->shouldBeCalled();
        $queryBuilder->first()->willReturn($result);

        $this->builderFactoryProphecy->getQueryBuilder(Dummy::class)->willReturn($queryBuilder->reveal());

        self::assertSame($result, $this->dataProvider->getItem(Dummy::class, ['id' => 1], 'foo', []));
    }

    public function testGetItemDoubleIdentifier(): void
    {
        $result = new \stdClass();

        $queryBuilder = $this->prophesize(Builder::class);
        $queryBuilder->where('ida', 1)->shouldBeCalled();
        $queryBuilder->where('idb', 2)->shouldBeCalled();
        $queryBuilder->first()->willReturn($result);

        $this->builderFactoryProphecy->getQueryBuilder(Dummy::class)->willReturn($queryBuilder->reveal());

        self::assertSame($result, $this->dataProvider->getItem(Dummy::class, ['ida' => 1, 'idb' => 2], 'foo', []));
    }
}
