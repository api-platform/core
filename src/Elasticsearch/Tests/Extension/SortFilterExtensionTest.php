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

use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Extension\SortFilterExtension;
use ApiPlatform\Elasticsearch\Filter\ConstantScoreFilterInterface;
use ApiPlatform\Elasticsearch\Filter\SortFilterInterface;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\GetCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class SortFilterExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            RequestBodySearchCollectionExtensionInterface::class,
            new SortFilterExtension(
                $this->prophesize(ContainerInterface::class)->reveal()
            )
        );
    }

    public function testApplyToCollection(): void
    {
        $operation = new GetCollection(filters: ['filter.order']);

        $sortFilterProphecy = $this->prophesize(SortFilterInterface::class);
        $sortFilterProphecy->apply([], Foo::class, $operation, ['filters' => ['order' => ['id' => 'desc']]])->willReturn([['id' => ['order' => 'desc']]])->shouldBeCalled();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('filter.order')->willReturn(true)->shouldBeCalled();
        $containerProphecy->get('filter.order')->willReturn($sortFilterProphecy)->shouldBeCalled();

        $sortFilterExtension = new SortFilterExtension($containerProphecy->reveal());

        self::assertEquals(['sort' => [['id' => ['order' => 'desc']]]], $sortFilterExtension->applyToCollection([], Foo::class, $operation, ['filters' => ['order' => ['id' => 'desc']]]));
    }

    public function testApplyToCollectionWithNoFilters(): void
    {
        $sortFilterExtension = new SortFilterExtension($this->prophesize(ContainerInterface::class)->reveal());

        self::assertEmpty($sortFilterExtension->applyToCollection([], Foo::class));
    }

    public function testApplyToCollectionWithNoSortFilters(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('filter.term')->willReturn(true)->shouldBeCalled();
        $containerProphecy->get('filter.term')->willReturn($this->prophesize(ConstantScoreFilterInterface::class))->shouldBeCalled();

        $sortFilterExtension = new SortFilterExtension($containerProphecy->reveal());

        self::assertEmpty($sortFilterExtension->applyToCollection([], Foo::class, new GetCollection(filters: ['filter.term']), ['filters' => ['order' => ['id' => 'desc']]]));
    }
}
