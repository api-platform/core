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

namespace ApiPlatform\Elasticsearch\Tests\Extension;

use ApiPlatform\Elasticsearch\Extension\ConstantScoreFilterExtension;
use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Filter\ConstantScoreFilterInterface;
use ApiPlatform\Elasticsearch\Filter\SortFilterInterface;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\Get;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class ConstantScoreFilterExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            RequestBodySearchCollectionExtensionInterface::class,
            new ConstantScoreFilterExtension(
                $this->prophesize(ContainerInterface::class)->reveal()
            )
        );
    }

    public function testApplyToCollection(): void
    {
        $operation = new Get(filters: ['filter.term']);

        $constantScoreFilterProphecy = $this->prophesize(ConstantScoreFilterInterface::class);
        $constantScoreFilterProphecy->apply([], Foo::class, $operation, ['filters' => ['name' => ['Kilian', 'Xavier', 'François']]])->willReturn(['bool' => ['must' => [['terms' => ['name' => ['Kilian', 'Xavier', 'François']]]]]])->shouldBeCalled();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('filter.term')->willReturn(true)->shouldBeCalled();
        $containerProphecy->get('filter.term')->willReturn($constantScoreFilterProphecy)->shouldBeCalled();

        $constantScoreFilterExtension = new ConstantScoreFilterExtension($containerProphecy->reveal());

        self::assertEquals(['query' => ['constant_score' => ['filter' => ['bool' => ['must' => [['terms' => ['name' => ['Kilian', 'Xavier', 'François']]]]]]]]], $constantScoreFilterExtension->applyToCollection([], Foo::class, $operation, ['filters' => ['name' => ['Kilian', 'Xavier', 'François']]]));
    }

    public function testApplyToCollectionWithNoFilters(): void
    {
        $constantScoreFilterExtension = new ConstantScoreFilterExtension($this->prophesize(ContainerInterface::class)->reveal());

        self::assertEmpty($constantScoreFilterExtension->applyToCollection([], Foo::class));
    }

    public function testApplyToCollectionWithNoConstantScoreFilters(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('filter.order')->willReturn(true)->shouldBeCalled();
        $containerProphecy->get('filter.order')->willReturn($this->prophesize(SortFilterInterface::class))->shouldBeCalled();

        $constantScoreFilterExtension = new ConstantScoreFilterExtension($containerProphecy->reveal());

        self::assertEmpty($constantScoreFilterExtension->applyToCollection([], Foo::class, new Get(filters: ['filter.order']), ['filters' => ['name' => ['Kilian', 'Xavier', 'François']]]));
    }
}
