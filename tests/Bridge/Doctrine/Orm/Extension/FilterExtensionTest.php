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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterInterface as ApiFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class FilterExtensionTest extends TestCase
{
    public function testApplyToCollectionWithValidFilters()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET', 'filters' => ['dummyFilter', 'dummyBadFilter']], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $queryBuilder = $queryBuilderProphecy->reveal();

        $ormFilterProphecy = $this->prophesize(FilterInterface::class);
        $ormFilterProphecy->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get', ['filters' => []])->shouldBeCalled();

        $ordinaryFilterProphecy = $this->prophesize(ApiFilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummyFilter')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->has('dummyBadFilter')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummyFilter')->willReturn($ormFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->get('dummyBadFilter')->willReturn($ordinaryFilterProphecy->reveal())->shouldBeCalled();

        $orderExtensionTest = new FilterExtension($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get');
    }

    /**
     * @group legacy
     * @expectedDeprecation The ApiPlatform\Core\Api\FilterCollection class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of Psr\Container\ContainerInterface instead.
     */
    public function testApplyToCollectionWithValidFiltersAndDeprecatedFilterCollection()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET', 'filters' => ['dummyFilter']], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $queryBuilder = $queryBuilderProphecy->reveal();

        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get', ['filters' => []])->shouldBeCalled();

        $orderExtensionTest = new FilterExtension($resourceMetadataFactoryProphecy->reveal(), new FilterCollection(['dummyFilter' => $filterProphecy->reveal()]));
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get');
    }

    /**
     * @group legacy
     */
    public function testConstructWithInvalidFilterLocator()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "$filterLocator" argument is expected to be an implementation of the "Psr\\Container\\ContainerInterface" interface.');

        new FilterExtension($this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(), new \ArrayObject());
    }

    public function testApplyToCollectionWithoutFilters()
    {
        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $orderExtensionTest = new FilterExtension($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(ContainerInterface::class)->reveal());
        $orderExtensionTest->applyToCollection($this->prophesize(QueryBuilder::class)->reveal(), new QueryNameGenerator(), Dummy::class, 'get');
    }
}
