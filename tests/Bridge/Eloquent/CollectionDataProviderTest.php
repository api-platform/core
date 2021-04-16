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
use ApiPlatform\Core\Bridge\Eloquent\CollectionDataProvider;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

/**
 * @group eloquent
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class CollectionDataProviderTest extends TestCase
{
    use ProphecyTrait;

    private $builderFactoryProphecy;
    private $dataProvider;

    protected function setUp(): void
    {
        $this->builderFactoryProphecy = $this->prophesize(BuilderFactoryInterface::class);
        $this->dataProvider = new CollectionDataProvider($this->builderFactoryProphecy->reveal());
    }

    /**
     * @dataProvider provideSupportsCases
     */
    public function testSupports($data, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->dataProvider->supports($data));
    }

    public function provideSupportsCases(): \Generator
    {
        yield 'not supported' => ['sdtClass', false];
        yield 'supported' => [Dummy::class, true];
    }

    public function testGetCollection(): void
    {
        $result = [new \stdClass()];

        $queryBuilder = $this->prophesize(Builder::class);
        $queryBuilder->get()->willReturn($result);

        $this->builderFactoryProphecy->getQueryBuilder(Dummy::class)->willReturn($queryBuilder->reveal());

        self::assertSame($result, $this->dataProvider->getCollection(Dummy::class, 'foo', []));
    }
}
