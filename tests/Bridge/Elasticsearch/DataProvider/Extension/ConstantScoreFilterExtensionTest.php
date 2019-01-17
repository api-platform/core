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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\DataProvider\Extension;

use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\ConstantScoreFilterExtension;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\ConstantScoreFilterInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\SortFilterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ConstantScoreFilterExtensionTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            RequestBodySearchCollectionExtensionInterface::class,
            new ConstantScoreFilterExtension(
                $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(ContainerInterface::class)->reveal()
            )
        );
    }

    public function testApplyToCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata(null, null, null, null, ['get' => ['filters' => ['filter.term']]]))->shouldBeCalled();

        $constantScoreFilterProphecy = $this->prophesize(ConstantScoreFilterInterface::class);
        $constantScoreFilterProphecy->apply([], Foo::class, 'get', ['filters' => ['name' => ['Kilian', 'Xavier', 'François']]])->willReturn(['bool' => ['must' => [['terms' => ['name' => ['Kilian', 'Xavier', 'François']]]]]])->shouldBeCalled();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('filter.term')->willReturn(true)->shouldBeCalled();
        $containerProphecy->get('filter.term')->willReturn($constantScoreFilterProphecy)->shouldBeCalled();

        $constantScoreFilterExtension = new ConstantScoreFilterExtension($resourceMetadataFactoryProphecy->reveal(), $containerProphecy->reveal());

        self::assertSame(['query' => ['constant_score' => ['filter' => ['bool' => ['must' => [['terms' => ['name' => ['Kilian', 'Xavier', 'François']]]]]]]]], $constantScoreFilterExtension->applyToCollection([], Foo::class, 'get', ['filters' => ['name' => ['Kilian', 'Xavier', 'François']]]));
    }

    public function testApplyToCollectionWithNoFilters()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata());

        $constantScoreFilterExtension = new ConstantScoreFilterExtension($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(ContainerInterface::class)->reveal());

        self::assertEmpty($constantScoreFilterExtension->applyToCollection([], Foo::class));
    }

    public function testApplyToCollectionWithNoConstantScoreFilters()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata(null, null, null, null, ['get' => ['filters' => ['filter.order']]]))->shouldBeCalled();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('filter.order')->willReturn(true)->shouldBeCalled();
        $containerProphecy->get('filter.order')->willReturn($this->prophesize(SortFilterInterface::class))->shouldBeCalled();

        $constantScoreFilterExtension = new ConstantScoreFilterExtension($resourceMetadataFactoryProphecy->reveal(), $containerProphecy->reveal());

        self::assertEmpty($constantScoreFilterExtension->applyToCollection([], Foo::class, 'get', ['filters' => ['name' => ['Kilian', 'Xavier', 'François']]]));
    }
}
