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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Routing;

use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolverInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class IriConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage No route matches "/users/3".
     */
    public function testGetItemFromIriNoRouteException()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willThrow(new RouteNotFoundException())->shouldBeCalledTimes(1);

        $converter = new IriConverter(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal()
        );
        $converter->getItemFromIri('/users/3');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage No resource associated to "/users/3".
     */
    public function testGetItemFromIriNoResourceException()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([])->shouldBeCalledTimes(1);

        $converter = new IriConverter(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal()
        );
        $converter->getItemFromIri('/users/3');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Item not found for "/users/3".
     */
    public function testGetItemFromIriItemNotFoundException()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem('AppBundle\Entity\User', 3, null, [])->shouldBeCalledTimes(1);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => 'AppBundle\Entity\User',
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $converter = new IriConverter(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal()
        );
        $converter->getItemFromIri('/users/3');
    }

    public function testGetItemFromIri()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem('AppBundle\Entity\User', 3, null, ['fetch_data' => true])
            ->willReturn('foo')
            ->shouldBeCalledTimes(1);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => 'AppBundle\Entity\User',
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $converter = new IriConverter(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal()
        );
        $converter->getItemFromIri('/users/3', ['fetch_data' => true]);
    }
}
