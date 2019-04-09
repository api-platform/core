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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\Extension;

use ApiPlatform\Core\Api\FilterInterface as ApiFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\FilterExtension;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\FilterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class FilterExtensionTest extends TestCase
{
    public function testApplyToCollectionWithValidFilters()
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET', 'filters' => ['dummyFilter', 'dummyBadFilter']], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $mongoDbOdmFilterProphecy = $this->prophesize(FilterInterface::class);
        $mongoDbOdmFilterProphecy->apply($aggregationBuilder, Dummy::class, 'get', ['filters' => []])->shouldBeCalled();

        $ordinaryFilterProphecy = $this->prophesize(ApiFilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummyFilter')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->has('dummyBadFilter')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummyFilter')->willReturn($mongoDbOdmFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->get('dummyBadFilter')->willReturn($ordinaryFilterProphecy->reveal())->shouldBeCalled();

        $orderExtensionTest = new FilterExtension($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class, 'get');
    }

    public function testApplyToCollectionWithoutFilters()
    {
        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $orderExtensionTest = new FilterExtension($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(ContainerInterface::class)->reveal());
        $orderExtensionTest->applyToCollection($this->prophesize(Builder::class)->reveal(), Dummy::class, 'get');
    }
}
