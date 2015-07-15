<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Api;

use Dunglas\ApiBundle\Api\IriConverter;
use Symfony\Component\Routing\RouterInterface;

class IriConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testGetItemFromIri()
    {
        $item = new \stdClass();
        $resource = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface')->reveal();
        $classMetadataFactory = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface')->reveal();
        $propertyAccessor = $this->prophesize('Symfony\Component\PropertyAccess\PropertyAccessorInterface')->reveal();

        $resourceCollectionProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface');
        $resourceCollectionProphecy->getResourceForShortName('Foo')->willReturn($resource)->shouldBeCalled();
        $resourceCollection = $resourceCollectionProphecy->reveal();

        $dataProviderProphecy = $this->prophesize('Dunglas\ApiBundle\Model\DataProviderInterface');
        $dataProviderProphecy->getItem($resource, 69, true)->willReturn($item)->shouldBeCalled();
        $dataProvider = $dataProviderProphecy->reveal();

        $routerProphecy = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $routerProphecy->match('/foo/69')->willReturn(['_resource' => 'Foo', 'id' => 69])->shouldBeCalled();
        $router = $routerProphecy->reveal();

        $iriConverter = new IriConverter($resourceCollection, $dataProvider, $classMetadataFactory, $router, $propertyAccessor);

        $this->assertInstanceOf('Dunglas\ApiBundle\Api\IriConverter', $iriConverter);
        $this->assertEquals($item, $iriConverter->getItemFromIri('/foo/69', true));
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage No route matches "/bar/69".
     */
    public function testGetItemFromIriRouteNotFound()
    {
        $classMetadataFactory = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface')->reveal();
        $propertyAccessor = $this->prophesize('Symfony\Component\PropertyAccess\PropertyAccessorInterface')->reveal();
        $resourceCollection = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface')->reveal();
        $dataProvider = $this->prophesize('Dunglas\ApiBundle\Model\DataProviderInterface')->reveal();

        $routerProphecy = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $routerProphecy->match('/bar/69')->willThrow('Symfony\Component\Routing\Exception\ResourceNotFoundException')->shouldBeCalled();
        $router = $routerProphecy->reveal();

        $iriConverter = new IriConverter($resourceCollection, $dataProvider, $classMetadataFactory, $router, $propertyAccessor);
        $iriConverter->getItemFromIri('/bar/69');
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage No resource associated to "/baz/69".
     */
    public function testGetItemFromIriResourceNotInRouterParameters()
    {
        $classMetadataFactory = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface')->reveal();
        $propertyAccessor = $this->prophesize('Symfony\Component\PropertyAccess\PropertyAccessorInterface')->reveal();
        $resourceCollection = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface')->reveal();
        $dataProvider = $this->prophesize('Dunglas\ApiBundle\Model\DataProviderInterface')->reveal();

        $routerProphecy = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $routerProphecy->match('/baz/69')->willReturn(['id' => 36])->shouldBeCalled();
        $router = $routerProphecy->reveal();

        $iriConverter = new IriConverter($resourceCollection, $dataProvider, $classMetadataFactory, $router, $propertyAccessor);
        $iriConverter->getItemFromIri('/baz/69');
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage No resource associated to "/bac/69".
     */
    public function testGetItemFromIriIdNotInRouterParameters()
    {
        $classMetadataFactory = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface')->reveal();
        $propertyAccessor = $this->prophesize('Symfony\Component\PropertyAccess\PropertyAccessorInterface')->reveal();
        $resourceCollection = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface')->reveal();
        $dataProvider = $this->prophesize('Dunglas\ApiBundle\Model\DataProviderInterface')->reveal();

        $routerProphecy = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $routerProphecy->match('/bac/69')->willReturn(['_resource' => 'Foo'])->shouldBeCalled();
        $router = $routerProphecy->reveal();

        $iriConverter = new IriConverter($resourceCollection, $dataProvider, $classMetadataFactory, $router, $propertyAccessor);
        $iriConverter->getItemFromIri('/bac/69');
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage No resource associated to "/bal/69".
     */
    public function testGetItemFromIriResourceIsNull()
    {
        $classMetadataFactory = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface')->reveal();
        $propertyAccessor = $this->prophesize('Symfony\Component\PropertyAccess\PropertyAccessorInterface')->reveal();
        $dataProvider = $this->prophesize('Dunglas\ApiBundle\Model\DataProviderInterface')->reveal();

        $resourceCollectionProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface');
        $resourceCollectionProphecy->getResourceForShortName('Bal')->willReturn(null)->shouldBeCalled();
        $resourceCollection = $resourceCollectionProphecy->reveal();

        $routerProphecy = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $routerProphecy->match('/bal/69')->willReturn(['_resource' => 'Bal', 'id' => 'milleneufcent'])->shouldBeCalled();
        $router = $routerProphecy->reveal();

        $iriConverter = new IriConverter($resourceCollection, $dataProvider, $classMetadataFactory, $router, $propertyAccessor);
        $iriConverter->getItemFromIri('/bal/69');
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Item not found for "/bal/milleneufcent".
     */
    public function testGetItemFromIriDataProviderReturnsNull()
    {
        $resource = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface')->reveal();
        $classMetadataFactory = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface')->reveal();
        $propertyAccessor = $this->prophesize('Symfony\Component\PropertyAccess\PropertyAccessorInterface')->reveal();

        $resourceCollectionProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface');
        $resourceCollectionProphecy->getResourceForShortName('Bal')->willReturn($resource)->shouldBeCalled();
        $resourceCollection = $resourceCollectionProphecy->reveal();

        $dataProviderProphecy = $this->prophesize('Dunglas\ApiBundle\Model\DataProviderInterface');
        $dataProviderProphecy->getItem($resource, 'milleneufcent', false)->willReturn(null)->shouldBeCalled();
        $dataProvider = $dataProviderProphecy->reveal();

        $routerProphecy = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $routerProphecy->match('/bal/milleneufcent')->willReturn(['_resource' => 'Bal', 'id' => 'milleneufcent'])->shouldBeCalled();
        $router = $routerProphecy->reveal();

        $iriConverter = new IriConverter($resourceCollection, $dataProvider, $classMetadataFactory, $router, $propertyAccessor);
        $iriConverter->getItemFromIri('/bal/milleneufcent');
    }

    public function testGetIriFromItem()
    {
        $item = new \stdClass();
        $item->myId = 69;

        $dataProvider = $this->prophesize('Dunglas\ApiBundle\Model\DataProviderInterface')->reveal();

        $propertyAccessorProphecy = $this->prophesize('Symfony\Component\PropertyAccess\PropertyAccessorInterface');
        $propertyAccessorProphecy->getValue($item, 'myId')->willReturn(69)->shouldBeCalled();
        $propertyAccessor = $propertyAccessorProphecy->reveal();

        $attributeMetadataProphecy = $this->prophesize('Dunglas\ApiBundle\Mapping\AttributeMetadataInterface');
        $attributeMetadataProphecy->getName()->willReturn('myId')->shouldBeCalled();
        $attributeMetadata = $attributeMetadataProphecy->reveal();

        $classMetadataProphecy = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataInterface');
        $classMetadataProphecy->getIdentifier()->willReturn($attributeMetadata)->shouldBeCalled();
        $classMetadata = $classMetadataProphecy->reveal();

        $classMetadataFactoryProphecy = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface');
        $classMetadataFactoryProphecy->getMetadataFor('MyClass', null, null, null)->willReturn($classMetadata)->shouldBeCalled();
        $classMetadataFactory = $classMetadataFactoryProphecy->reveal();

        $itemRouteProphecy = $this->prophesize('Symfony\Component\Routing\Route');
        $itemRouteProphecy->getMethods()->willReturn([])->shouldBeCalled();
        $itemRoute = $itemRouteProphecy->reveal();

        $itemOperationProphecy = $this->prophesize('Dunglas\ApiBundle\Api\Operation\OperationInterface');
        $itemOperationProphecy->getRoute()->willReturn($itemRoute)->shouldBeCalled();
        $itemOperationProphecy->getRouteName()->willReturn('item_route');
        $itemOperation = $itemOperationProphecy->reveal();

        $resourceProphecy = $this->createResourceProphecy();
        $resourceProphecy->getItemOperations()->willReturn([$itemOperation])->shouldBeCalled();
        $resource = $resourceProphecy->reveal();

        $resourceCollectionProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface');
        $resourceCollectionProphecy->getResourceForEntity($item)->willReturn($resource)->shouldBeCalled();
        $resourceCollection = $resourceCollectionProphecy->reveal();

        $routerProphecy = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $routerProphecy->generate('item_route', ['id' => 69], RouterInterface::ABSOLUTE_PATH)->willReturn('/foo/69')->shouldBeCalled();
        $router = $routerProphecy->reveal();

        $iriConverter = new IriConverter($resourceCollection, $dataProvider, $classMetadataFactory, $router, $propertyAccessor);

        $this->assertInstanceOf('Dunglas\ApiBundle\Api\IriConverter', $iriConverter);
        $this->assertEquals('/foo/69', $iriConverter->getIriFromItem($item));
        // Second call to test fetching from the local cache
        $this->assertEquals('/foo/69', $iriConverter->getIriFromItem($item));
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage No resource associated with the type "stdClass".
     */
    public function testGetIriFromItemNoResourceAssociated()
    {
        $item = new \stdClass();
        $classMetadataFactory = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface')->reveal();
        $propertyAccessor = $this->prophesize('Symfony\Component\PropertyAccess\PropertyAccessorInterface')->reveal();
        $dataProvider = $this->prophesize('Dunglas\ApiBundle\Model\DataProviderInterface')->reveal();
        $router = $this->prophesize('Symfony\Component\Routing\RouterInterface')->reveal();

        $resourceCollectionProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface');
        $resourceCollectionProphecy->getResourceForEntity($item)->willReturn(null)->shouldBeCalled();
        $resourceCollection = $resourceCollectionProphecy->reveal();

        $iriConverter = new IriConverter($resourceCollection, $dataProvider, $classMetadataFactory, $router, $propertyAccessor);

        $iriConverter->getIriFromItem($item);
    }

    public function testGetIriFromResource()
    {
        $resourceCollection = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface')->reveal();
        $dataProvider = $this->prophesize('Dunglas\ApiBundle\Model\DataProviderInterface')->reveal();
        $classMetadataFactory = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface')->reveal();
        $propertyAccessor = $this->prophesize('Symfony\Component\PropertyAccess\PropertyAccessorInterface')->reveal();

        $collectionRouteProphecy = $this->prophesize('Symfony\Component\Routing\Route');
        $collectionRouteProphecy->getMethods()->willReturn(['GET', 'HEAD'])->shouldBeCalled();
        $collectionRoute = $collectionRouteProphecy->reveal();

        $collectionOperationProphecy = $this->prophesize('Dunglas\ApiBundle\Api\Operation\OperationInterface');
        $collectionOperationProphecy->getRoute()->willReturn($collectionRoute)->shouldBeCalled();
        $collectionOperationProphecy->getRouteName()->willReturn('collection_route')->shouldBeCalled();
        $collectionOperation = $collectionOperationProphecy->reveal();

        $resourceProphecy = $this->createResourceProphecy();
        $resourceProphecy->getCollectionOperations()->willReturn([$collectionOperation])->shouldBeCalled();
        $resource = $resourceProphecy->reveal();

        $routerProphecy = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $routerProphecy->generate('collection_route', [], RouterInterface::ABSOLUTE_PATH)->willReturn('/foo')->shouldBeCalled();
        $router = $routerProphecy->reveal();

        $iriConverter = new IriConverter($resourceCollection, $dataProvider, $classMetadataFactory, $router, $propertyAccessor);

        $this->assertInstanceOf('Dunglas\ApiBundle\Api\IriConverter', $iriConverter);
        $this->assertEquals('/foo', $iriConverter->getIriFromResource($resource));
        // Second call to test fetching from the local cache
        $this->assertEquals('/foo', $iriConverter->getIriFromResource($resource));
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unable to generate an IRI for "Foo".
     */
    public function testGetIriFromResourceRouteNotFound()
    {
        $resourceCollection = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface')->reveal();
        $classMetadataFactory = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface')->reveal();
        $propertyAccessor = $this->prophesize('Symfony\Component\PropertyAccess\PropertyAccessorInterface')->reveal();
        $dataProvider = $this->prophesize('Dunglas\ApiBundle\Model\DataProviderInterface')->reveal();

        $routerProphecy = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $routerProphecy->generate(null, [], RouterInterface::ABSOLUTE_PATH)->willThrow('Symfony\Component\Routing\Exception\RouteNotFoundException')->shouldBeCalled();
        $router = $routerProphecy->reveal();

        $iriConverter = new IriConverter($resourceCollection, $dataProvider, $classMetadataFactory, $router, $propertyAccessor);

        $resourceProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $resourceProphecy->getShortName()->willReturn('Foo')->shouldBeCalled();
        $resourceProphecy->getCollectionOperations()->willReturn([])->shouldBeCalled();
        $resource = $resourceProphecy->reveal();

        $iriConverter->getIriFromResource($resource);
    }

    private function createResourceProphecy()
    {
        $resourceProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $resourceProphecy->getEntityClass()->willReturn('MyClass');
        $resourceProphecy->getNormalizationGroups()->willReturn(null);
        $resourceProphecy->getDenormalizationGroups()->willReturn(null);
        $resourceProphecy->getValidationGroups()->willReturn(null);

        return $resourceProphecy;
    }
}
