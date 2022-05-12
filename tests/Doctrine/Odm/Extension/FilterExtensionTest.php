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

namespace ApiPlatform\Tests\Doctrine\Odm\Extension;

use ApiPlatform\Api\FilterInterface as ApiFilterInterface;
use ApiPlatform\Doctrine\Odm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @group mongodb
 */
class FilterExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testApplyToCollectionWithValidFilters()
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $dummyMetadata = new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations(['get' => (new GetCollection())->withFilters(['dummyFilter', 'dummyBadFilter'])]))]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
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
        $dummyMetadata = new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations(['get' => (new GetCollection())->withFilters(['dummyFilter', 'dummyBadFilter'])]))]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $orderExtensionTest = new FilterExtension($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(ContainerInterface::class)->reveal());
        $orderExtensionTest->applyToCollection($this->prophesize(Builder::class)->reveal(), Dummy::class, 'get');
    }
}
