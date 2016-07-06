<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Symfony\Bridge\Bundle\DependencyInjection;

use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
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
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No route matches "/users/3".
     */
    public function testGetItemFromIriNoRouteException()
    {
        $propertyNameCollectionFactoryMock = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $itemDataProviderMock = $this->prophesize(ItemDataProviderInterface::class);
        $routerMock = $this->prophesize(RouterInterface::class);

        $routerMock->match('/users/3')->willThrow(new RouteNotFoundException())->shouldBeCalledTimes(1);

        $converter = new IriConverter(
            $propertyNameCollectionFactoryMock->reveal(),
            $propertyMetadataFactoryMock->reveal(),
            $itemDataProviderMock->reveal(),
            $routerMock->reveal()
        );
        $converter->getItemFromIri('/users/3');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No resource associated to "/users/3".
     */
    public function testGetItemFromIriNoResourceException()
    {
        $propertyNameCollectionFactoryMock = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $itemDataProviderMock = $this->prophesize(ItemDataProviderInterface::class);
        $routerMock = $this->prophesize(RouterInterface::class);

        $routerMock->match('/users/3')->willReturn([])->shouldBeCalledTimes(1);

        $converter = new IriConverter(
            $propertyNameCollectionFactoryMock->reveal(),
            $propertyMetadataFactoryMock->reveal(),
            $itemDataProviderMock->reveal(),
            $routerMock->reveal()
        );
        $converter->getItemFromIri('/users/3');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Item not found for "/users/3".
     */
    public function testGetItemFromIriItemNotFoundException()
    {
        $propertyNameCollectionFactoryMock = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $itemDataProviderMock = $this->prophesize(ItemDataProviderInterface::class);
        $routerMock = $this->prophesize(RouterInterface::class);

        $routerMock->match('/users/3')->willReturn([
            '_api_resource_class' => 'AppBundle\Entity\User',
            'id' => 3,
        ])->shouldBeCalledTimes(1);
        $itemDataProviderMock->getItem('AppBundle\Entity\User', 3, null, false)->shouldBeCalledTimes(1);

        $converter = new IriConverter(
            $propertyNameCollectionFactoryMock->reveal(),
            $propertyMetadataFactoryMock->reveal(),
            $itemDataProviderMock->reveal(),
            $routerMock->reveal()
        );
        $converter->getItemFromIri('/users/3');
    }

    public function testGetItemFromIri()
    {
        $propertyNameCollectionFactoryMock = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $itemDataProviderMock = $this->prophesize(ItemDataProviderInterface::class);
        $routerMock = $this->prophesize(RouterInterface::class);

        $routerMock->match('/users/3')->willReturn([
            '_api_resource_class' => 'AppBundle\Entity\User',
            'id' => 3,
        ])->shouldBeCalledTimes(1);
        $itemDataProviderMock->getItem('AppBundle\Entity\User', 3, null, true)
            ->willReturn('foo')
            ->shouldBeCalledTimes(1);

        $converter = new IriConverter(
            $propertyNameCollectionFactoryMock->reveal(),
            $propertyMetadataFactoryMock->reveal(),
            $itemDataProviderMock->reveal(),
            $routerMock->reveal()
        );
        $converter->getItemFromIri('/users/3', true);
    }
}
