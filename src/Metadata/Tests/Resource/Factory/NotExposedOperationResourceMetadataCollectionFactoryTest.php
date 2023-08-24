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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Resource\Factory\LinkFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\NotExposedOperationResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeResource;
use ApiPlatform\Metadata\Tests\Fixtures\Metadata\Get as CustomGet;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class NotExposedOperationResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testItIgnoresClassesWithoutResources(): void
    {
        $linkFactoryProphecy = $this->prophesize(LinkFactoryInterface::class);
        $linkFactoryProphecy->createLinksFromIdentifiers(Argument::type(HttpOperation::class))->shouldNotBeCalled();

        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(AttributeResource::class)->willReturn(
            new ResourceMetadataCollection(AttributeResource::class, []),
        );

        $factory = new NotExposedOperationResourceMetadataCollectionFactory($linkFactoryProphecy->reveal(), $resourceCollectionMetadataFactoryProphecy->reveal());
        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, []),
            $factory->create(AttributeResource::class)
        );
    }

    public function testItIgnoresResourcesWithAnItemOperation(): void
    {
        $linkFactoryProphecy = $this->prophesize(LinkFactoryInterface::class);
        $linkFactoryProphecy->createLinksFromIdentifiers(Argument::type(HttpOperation::class))->shouldNotBeCalled();

        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(AttributeResource::class)->willReturn(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [],
                    class: AttributeResource::class
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [
                        '_api_AttributeResource_get_collection' => new GetCollection(controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                        '_api_AttributeResource_get' => new Get(uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])], controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                    ],
                    uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])],
                    class: AttributeResource::class
                ),
            ]),
        );

        $factory = new NotExposedOperationResourceMetadataCollectionFactory($linkFactoryProphecy->reveal(), $resourceCollectionMetadataFactoryProphecy->reveal());
        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [],
                    class: AttributeResource::class
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [
                        '_api_AttributeResource_get_collection' => new GetCollection(controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                        '_api_AttributeResource_get' => new Get(uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])], controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                    ],
                    uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])],
                    class: AttributeResource::class
                ),
            ]),
            $factory->create(AttributeResource::class)
        );
    }

    public function testItIgnoresResourcesWithAnItemOperationUsingCustomClass(): void
    {
        $linkFactoryProphecy = $this->prophesize(LinkFactoryInterface::class);
        $linkFactoryProphecy->createLinksFromIdentifiers(Argument::type(HttpOperation::class))->shouldNotBeCalled();

        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(AttributeResource::class)->willReturn(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [],
                    class: AttributeResource::class
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [
                        '_api_AttributeResource_get_collection' => new GetCollection(controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                        '_api_AttributeResource_get' => new CustomGet(uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])], controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                    ],
                    uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])],
                    class: AttributeResource::class
                ),
            ]),
        );

        $factory = new NotExposedOperationResourceMetadataCollectionFactory($linkFactoryProphecy->reveal(), $resourceCollectionMetadataFactoryProphecy->reveal());
        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [],
                    class: AttributeResource::class
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [
                        '_api_AttributeResource_get_collection' => new GetCollection(controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                        '_api_AttributeResource_get' => new CustomGet(uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])], controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                    ],
                    uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])],
                    class: AttributeResource::class
                ),
            ]),
            $factory->create(AttributeResource::class)
        );
    }

    public function testItAddsANotExposedOperationWithoutRouteNameOnTheLastResource(): void
    {
        $linkFactoryProphecy = $this->prophesize(LinkFactoryInterface::class);
        $linkFactoryProphecy->createLinksFromIdentifiers(Argument::type(HttpOperation::class))->willReturn([new Link()])->shouldBeCalled();

        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(AttributeResource::class)->willReturn(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [],
                    class: AttributeResource::class
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [
                        '_api_AttributeResource_get_collection' => new GetCollection(controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                    ],
                    class: AttributeResource::class
                ),
            ]),
        );

        $factory = new NotExposedOperationResourceMetadataCollectionFactory($linkFactoryProphecy->reveal(), $resourceCollectionMetadataFactoryProphecy->reveal());
        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [],
                    class: AttributeResource::class
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [
                        '_api_AttributeResource_get_collection' => new GetCollection(controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                        '_api_AttributeResource_get' => new NotExposed(controller: 'api_platform.action.not_exposed', shortName: 'AttributeResource', class: AttributeResource::class, output: false, read: false, extraProperties: ['generated_operation' => true]),
                    ],
                    class: AttributeResource::class
                ),
            ]),
            $factory->create(AttributeResource::class)
        );
    }

    public function testItAddsANotExposedOperationWithRouteNameOnTheLastResource(): void
    {
        $linkFactoryProphecy = $this->prophesize(LinkFactoryInterface::class);
        $linkFactoryProphecy->createLinksFromIdentifiers(Argument::type(HttpOperation::class))->willReturn([])->shouldBeCalled();

        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(AttributeResource::class)->willReturn(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [],
                    class: AttributeResource::class
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    types: ['https://schema.org/Book'],
                    uriTemplate: '/custom_api_resources', // uriTemplate should not be inherited on NotExposed operation
                    uriVariables: ['slug'], // same as it is used to generate the uriTemplate of our NotExposed operation
                    operations: [
                        '_api_AttributeResource_get_collection' => new GetCollection(controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                    ],
                    class: AttributeResource::class
                ),
            ]),
        );

        $factory = new NotExposedOperationResourceMetadataCollectionFactory($linkFactoryProphecy->reveal(), $resourceCollectionMetadataFactoryProphecy->reveal());
        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    operations: [],
                    class: AttributeResource::class
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    uriTemplate: '/custom_api_resources',
                    uriVariables: ['slug'],
                    types: ['https://schema.org/Book'],
                    operations: [
                        '_api_AttributeResource_get_collection' => new GetCollection(controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                        '_api_AttributeResource_get' => new NotExposed(uriTemplate: '/.well-known/genid/{id}', controller: 'api_platform.action.not_exposed', shortName: 'AttributeResource', class: AttributeResource::class, output: false, read: false, extraProperties: ['generated_operation' => true], types: ['https://schema.org/Book']),
                    ],
                    class: AttributeResource::class
                ),
            ]),
            $factory->create(AttributeResource::class)
        );
    }
}
