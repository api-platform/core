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

use ApiPlatform\Core\Bridge\Symfony\Routing\ApiLoader;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Routing\ResourcePathGeneratorInterface;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ApiLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testApiLoader()
    {
        $resourceMetadata = new ResourceMetadata();

        $resourceMetadata = $resourceMetadata->withShortName('dummy');

        //default operation based on OperationResourceMetadataFactory
        $resourceMetadata = $resourceMetadata->withItemOperations([
            'get' => ['method' => 'GET'],
            'put' => ['method' => 'PUT'],
            'delete' => ['method' => 'DELETE'],
        ]);

        //custom operations
        $resourceMetadata = $resourceMetadata->withCollectionOperations([
            'my_op' => ['method' => 'GET', 'controller' => 'some.service.name'], //with controller
            'my_second_op' => ['method' => 'POST'], //without controller, takes the default one
            'my_path_op' => ['method' => 'GET', 'path' => 'some/custom/path'], //custom path
        ]);

        $routeCollection = $this->getApiLoaderWithResourceMetadata($resourceMetadata)->load(null);

        $this->assertEquals(
            $routeCollection->get('api_dummies_get_item'),
            $this->getRoute('/dummies/{id}', 'api_platform.action.get_item', DummyEntity::class, 'get', ['GET'])
        );

        $this->assertEquals(
            $routeCollection->get('api_dummies_delete_item'),
            $this->getRoute('/dummies/{id}', 'api_platform.action.delete_item', DummyEntity::class, 'delete', ['DELETE'])
        );

        $this->assertEquals(
            $routeCollection->get('api_dummies_put_item'),
            $this->getRoute('/dummies/{id}', 'api_platform.action.put_item', DummyEntity::class, 'put', ['PUT'])
        );

        $this->assertEquals(
            $routeCollection->get('api_dummies_my_op_collection'),
            $this->getRoute('/dummies', 'some.service.name', DummyEntity::class, 'my_op', ['GET'], true)
        );

        $this->assertEquals(
            $routeCollection->get('api_dummies_my_second_op_collection'),
            $this->getRoute('/dummies', 'api_platform.action.post_collection', DummyEntity::class, 'my_second_op', ['POST'], true)
        );

        $this->assertEquals(
            $routeCollection->get('api_dummies_my_path_op_collection'),
            $this->getRoute('/some/custom/path', 'api_platform.action.get_collection', DummyEntity::class, 'my_path_op', ['GET'], true)
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNoMethodApiLoader()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withShortName('dummy');

        $resourceMetadata = $resourceMetadata->withItemOperations([
            'get' => [],
        ]);

        $resourceMetadata = $resourceMetadata->withCollectionOperations([
            'get' => ['method' => 'GET'],
        ]);

        $routeCollection = $this->getApiLoaderWithResourceMetadata($resourceMetadata)->load(null);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWrongMethodApiLoader()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withShortName('dummy');

        $resourceMetadata = $resourceMetadata->withItemOperations([
            'post' => ['method' => 'POST'],
        ]);

        $resourceMetadata = $resourceMetadata->withCollectionOperations([
            'get' => ['method' => 'GET'],
        ]);

        $routeCollection = $this->getApiLoaderWithResourceMetadata($resourceMetadata)->load(null);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidResourceException
     */
    public function testNoShortNameApiLoader()
    {
        $this->getApiLoaderWithResourceMetadata(new ResourceMetadata())->load(null);
    }

    private function getApiLoaderWithResourceMetadata(ResourceMetadata $resourceMetadata): ApiLoader
    {
        $routingConfig = __DIR__.'/../../../../src/Bridge/Symfony/Bundle/Resources/config/routing';

        $kernelProphecy = $this->prophesize(KernelInterface::class);
        $kernelProphecy->locateResource(Argument::any())->willReturn($routingConfig);
        $possibleArguments = [
            'api_platform.action.get_collection',
            'api_platform.action.post_collection',
            'api_platform.action.get_item',
            'api_platform.action.put_item',
            'api_platform.action.delete_item',
        ];

        $containerInterfaceProphecy = $this->prophesize(ContainerInterface::class);
        $containerInterfaceProphecy->reveal();

        $kernelProphecy->getContainer()->willReturn($containerInterfaceProphecy);

        foreach ($possibleArguments as $possibleArgument) {
            $containerInterfaceProphecy->has($possibleArgument)->willReturn(true);
        }

        $containerInterfaceProphecy->has(Argument::type('string'))->willReturn(false);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->willReturn($resourceMetadata);

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyEntity::class]));

        $resourcePathGeneratorProphecy = $this->prophesize(ResourcePathGeneratorInterface::class);
        $resourcePathGeneratorProphecy->generateResourceBasePath('dummy')->willReturn('dummies');

        $apiLoader = new ApiLoader($kernelProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $resourcePathGeneratorProphecy->reveal());

        return $apiLoader;
    }

    /**
     * get a Route instance with params.
     *
     * @param string path
     * @param string controller
     * @param string ressourceClass
     * @param string operationName
     * @param array methods
     * @param bool collection - whether it's a collection or not
     */
    private function getRoute($path, $controller, $resourceClass, $operationName, array $methods, $collection = false): Route
    {
        return new Route(
            $path,
            [
                '_controller' => $controller,
                '_resource_class' => $resourceClass,
                sprintf('_%s_operation_name', $collection ? 'collection' : 'item') => $operationName,
            ],
            [],
            [],
            '',
            [],
            $methods
        );
    }
}
