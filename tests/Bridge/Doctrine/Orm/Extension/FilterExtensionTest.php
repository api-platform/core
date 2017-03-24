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

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class FilterExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testApplyToCollectionWithValidFilters()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET', 'filters' => ['dummyFilter']], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $queryBuilder = $queryBuilderProphecy->reveal();

        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get')->shouldBeCalled();

        $orderExtensionTest = new FilterExtension($resourceMetadataFactoryProphecy->reveal(), new FilterCollection(['dummyFilter' => $filterProphecy->reveal()]));
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get');
    }

    public function testApplyToCollectionWithoutFilters()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $queryBuilder = $queryBuilderProphecy->reveal();

        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get')->shouldNotBeCalled();

        $orderExtensionTest = new FilterExtension($resourceMetadataFactoryProphecy->reveal(), new FilterCollection(['dummyFilter' => $filterProphecy->reveal()]));
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get');
    }
}
