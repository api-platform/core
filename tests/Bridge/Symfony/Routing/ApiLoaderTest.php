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

use ApiPlatform\Core\Bridge\Symfony\Routing\ApiLoader;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\PathResolver\CustomOperationPathResolver;
use ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver;
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
            $this->getRoute('/dummies/{id}.{_format}', 'api_platform.action.get_item', DummyEntity::class, 'get', ['GET']),
            $routeCollection->get('api_dummies_get_item')
        );

        $this->assertEquals(
            $this->getRoute('/dummies/{id}.{_format}', 'api_platform.action.delete_item', DummyEntity::class, 'delete', ['DELETE']),
            $routeCollection->get('api_dummies_delete_item')
        );

        $this->assertEquals(
            $this->getRoute('/dummies/{id}.{_format}', 'api_platform.action.put_item', DummyEntity::class, 'put', ['PUT']),
            $routeCollection->get('api_dummies_put_item')
        );

        $this->assertEquals(
            $this->getRoute('/dummies.{_format}', 'some.service.name', DummyEntity::class, 'my_op', ['GET'], true),
            $routeCollection->get('api_dummies_my_op_collection')
        );

        $this->assertEquals(
            $this->getRoute('/dummies.{_format}', 'api_platform.action.post_collection', DummyEntity::class, 'my_second_op', ['POST'], true),
            $routeCollection->get('api_dummies_my_second_op_collection')
        );

        $this->assertEquals(
            $this->getRoute('/some/custom/path', 'api_platform.action.get_collection', DummyEntity::class, 'my_path_op', ['GET'], true),
            $routeCollection->get('api_dummies_my_path_op_collection')
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

        $this->getApiLoaderWithResourceMetadata($resourceMetadata)->load(null);
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

        $this->getApiLoaderWithResourceMetadata($resourceMetadata)->load(null);
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
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        foreach ($possibleArguments as $possibleArgument) {
            $containerProphecy->has($possibleArgument)->willReturn(true);
        }
        $containerProphecy->getParameter('api_platform.enable_swagger')->willReturn(true);

        $containerProphecy->has(Argument::type('string'))->willReturn(false);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->willReturn($resourceMetadata);

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyEntity::class]));

        $operationPathResolver = new CustomOperationPathResolver(new UnderscoreOperationPathResolver());

        $apiLoader = new ApiLoader($kernelProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $operationPathResolver, $containerProphecy->reveal(), ['jsonld' => ['application/ld+json']]);

        return $apiLoader;
    }

    private function getRoute(string $path, string $controller, string $resourceClass, string $operationName, array $methods, bool $collection = false): Route
    {
        return new Route(
            $path,
            [
                '_controller' => $controller,
                '_format' => null,
                '_api_resource_class' => $resourceClass,
                sprintf('_api_%s_operation_name', $collection ? 'collection' : 'item') => $operationName,
            ],
            [],
            [],
            '',
            [],
            $methods
        );
    }
}
