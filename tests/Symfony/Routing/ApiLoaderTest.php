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

namespace ApiPlatform\Tests\Symfony\Routing;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\PathResolver\CustomOperationPathResolver;
use ApiPlatform\PathResolver\OperationPathResolver;
use ApiPlatform\Symfony\Routing\ApiLoader;
use ApiPlatform\Tests\Fixtures\DummyEntity;
use ApiPlatform\Tests\Fixtures\RelatedDummyEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 *
 * TODO: in 3.0 just remove the IdentifiersExtractor
 *
 * @group legacy
 */
class ApiLoaderTest extends TestCase
{
    use ProphecyTrait;

    public function testApiLoader()
    {
        $path = '/dummies/{id}.{_format}';

        $resourceCollection = new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withShortName('dummy')->withOperations(new Operations([
                // Default operations based on OperationResourceMetadataFactory
                'api_dummies_get_item' => (new Get())->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])])->withUriTemplate($path)->withDefaults(['my_default' => 'default_value', '_controller' => 'should_not_be_overriden'])->withRequirements(['id' => '\d+'])->withController('api_platform.action.get_item'),
                'api_dummies_put_item' => (new Put())->withUriTemplate($path),
                'api_dummies_delete_item' => (new Delete())->withUriTemplate($path),
                // Custom operations
                'api_dummies_my_op_collection' => (new GetCollection())->withUriTemplate('/dummies.{_format}')->withDefaults(['my_default' => 'default_value', '_format' => 'a valid format'])->withRequirements(['_format' => 'a valid format'])->withCondition("request.headers.get('User-Agent') matches '/firefox/i'")->withController('some.service.name'),
                'api_dummies_my_second_op_collection' => (new GetCollection())->withMethod('POST')->withUriTemplate('/dummies.{_format}')->withOptions(['option' => 'option_value'])->withHost('{subdomain}.api-platform.com')->withSchemes(['https']),
                // without controller, takes the default one
                'api_dummies_my_path_op_collection' => (new GetCollection())->withUriTemplate('some/custom/path'),
                // Custom path
                'api_dummies_my_stateless_op_collection' => (new GetCollection())->withUriTemplate('/dummies.{_format}')->withStateless(true),
            ])),
        ]);

        $routeCollection = $this->getApiLoaderWithResourceMetadataCollection($resourceCollection)->load(null);

        $this->assertEquals(
            $this->getRoute(
                $path,
                'api_platform.action.get_item',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_get_item',
                ['my_default' => 'default_value', '_controller' => 'should_not_be_overriden'],
                ['GET'],
                ['id' => '\d+']
            ),
            $routeCollection->get('api_dummies_get_item')
        );

        $this->assertEquals(
            $this->getRoute(
                $path,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_delete_item',
                [],
                ['DELETE'],
                []
            ),
            $routeCollection->get('api_dummies_delete_item')
        );

        $this->assertEquals(
            $this->getRoute(
                $path,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_put_item',
                [],
                ['PUT'],
                []
            ),
            $routeCollection->get('api_dummies_put_item')
        );

        $this->assertEquals(
            $this->getRoute(
                '/dummies.{_format}',
                'some.service.name',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_my_op_collection',
                ['my_default' => 'default_value', '_format' => 'a valid format'],
                ['GET'],
                ['_format' => 'a valid format'],
                [],
                '',
                [],
                "request.headers.get('User-Agent') matches '/firefox/i'"
            ),
            $routeCollection->get('api_dummies_my_op_collection')
        );

        $this->assertEquals(
            $this->getRoute(
                '/dummies.{_format}',
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_my_second_op_collection',
                [],
                ['POST'],
                [],
                ['option' => 'option_value'],
                '{subdomain}.api-platform.com',
                ['https']
            ),
            $routeCollection->get('api_dummies_my_second_op_collection')
        );

        $this->assertEquals(
            $this->getRoute(
                'some/custom/path',
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_my_path_op_collection',
                [],
                ['GET'],
                []
            ),
            $routeCollection->get('api_dummies_my_path_op_collection')
        );

        $this->assertEquals(
            $this->getRoute(
                '/dummies.{_format}',
                'api_platform.action.placeholder',
                true,
                RelatedDummyEntity::class,
                [],
                'api_dummies_my_stateless_op_collection',
                [],
                ['GET'],
                []
            ),
            $routeCollection->get('api_dummies_my_stateless_op_collection')
        );
    }

    public function testApiLoaderWithPrefix()
    {
        $prefix = '/foobar-prefix';
        $path = '/dummies/{id}.{_format}';

        $resourceCollection = new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withShortName('dummy')->withOperations(new Operations([
            'api_dummies_get_item' => (new Get())->withUriTemplate($path)->withRoutePrefix($prefix)->withDefaults(['my_default' => 'default_value', '_controller' => 'should_not_be_overriden'])->withRequirements(['id' => '\d+']),
            'api_dummies_put_item' => (new Put())->withUriTemplate($path)->withRoutePrefix($prefix),
            'api_dummies_delete_item' => (new Delete())->withUriTemplate($path)->withRoutePrefix($prefix),
        ]))]);

        $routeCollection = $this->getApiLoaderWithResourceMetadataCollection($resourceCollection)->load(null);

        $prefixedPath = $prefix.$path;

        $this->assertEquals(
            $this->getRoute(
                $prefixedPath,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_get_item',
                ['my_default' => 'default_value', '_controller' => 'should_not_be_overriden'],
                ['GET'],
                ['id' => '\d+']
            ),
            $routeCollection->get('api_dummies_get_item')
        );

        $this->assertEquals(
            $this->getRoute(
                $prefixedPath,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_delete_item',
                [],
                ['DELETE']
            ),
            $routeCollection->get('api_dummies_delete_item')
        );

        $this->assertEquals(
            $this->getRoute(
                $prefixedPath,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_put_item',
                [],
                ['PUT']
            ),
            $routeCollection->get('api_dummies_put_item')
        );
    }

    private function getApiLoaderWithResourceMetadataCollection(ResourceMetadataCollection $resourceCollection): ApiLoader
    {
        $routingConfig = __DIR__.'/../../../src/Symfony/Bundle/Resources/config/routing';

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

        $containerProphecy->has(Argument::type('string'))->willReturn(false);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->willReturn($resourceCollection);
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->willReturn($resourceCollection);

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyEntity::class, RelatedDummyEntity::class]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->willReturn(new PropertyNameCollection(['id']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->willReturn(new PropertyNameCollection(['id']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'id')->willReturn(new ApiProperty());
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'id')->willReturn(new ApiProperty());

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);
        $identifiersExtractor = $identifiersExtractorProphecy->reveal();

        return new ApiLoader($kernelProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactory, $operationPathResolver, $containerProphecy->reveal(), ['jsonld' => ['application/ld+json']], [], null, false, true, true, false, false, $identifiersExtractor);
    }

    private function getRoute(string $path, string $controller, ?bool $stateless, string $resourceClass, array $identifiers, string $operationName, array $extraDefaults = [], array $methods = [], array $requirements = [], array $options = [], string $host = '', array $schemes = [], string $condition = ''): Route
    {
        $isCollection = str_contains($operationName, 'collection');

        return new Route(
            $path,
            [
                '_controller' => $controller,
                '_format' => null,
                '_stateless' => $stateless,
                '_api_resource_class' => $resourceClass,
                '_api_operation_name' => $operationName,
            ] + $extraDefaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition
        );
    }
}
