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

use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\SortFilterExtension;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\ConstantScoreFilterInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\SortFilterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SortFilterExtensionTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            RequestBodySearchCollectionExtensionInterface::class,
            new SortFilterExtension(
                $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(ContainerInterface::class)->reveal()
            )
        );
    }

    public function testApplyToCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata(null, null, null, null, ['get' => ['filters' => ['filter.order']]]))->shouldBeCalled();

        $sortFilterProphecy = $this->prophesize(SortFilterInterface::class);
        $sortFilterProphecy->apply([], Foo::class, 'get', ['filters' => ['order' => ['id' => 'desc']]])->willReturn([['id' => ['order' => 'desc']]])->shouldBeCalled();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('filter.order')->willReturn(true)->shouldBeCalled();
        $containerProphecy->get('filter.order')->willReturn($sortFilterProphecy)->shouldBeCalled();

        $sortFilterExtension = new SortFilterExtension($resourceMetadataFactoryProphecy->reveal(), $containerProphecy->reveal());

        self::assertSame(['sort' => [['id' => ['order' => 'desc']]]], $sortFilterExtension->applyToCollection([], Foo::class, 'get', ['filters' => ['order' => ['id' => 'desc']]]));
    }

    public function testApplyToCollectionWithNoFilters()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata());

        $sortFilterExtension = new SortFilterExtension($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(ContainerInterface::class)->reveal());

        self::assertEmpty($sortFilterExtension->applyToCollection([], Foo::class));
    }

    public function testApplyToCollectionWithNoSortFilters()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata(null, null, null, null, ['get' => ['filters' => ['filter.term']]]))->shouldBeCalled();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('filter.term')->willReturn(true)->shouldBeCalled();
        $containerProphecy->get('filter.term')->willReturn($this->prophesize(ConstantScoreFilterInterface::class))->shouldBeCalled();

        $sortFilterExtension = new SortFilterExtension($resourceMetadataFactoryProphecy->reveal(), $containerProphecy->reveal());

        self::assertEmpty($sortFilterExtension->applyToCollection([], Foo::class, 'get', ['filters' => ['order' => ['id' => 'desc']]]));
    }
}
