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

namespace ApiPlatform\Tests\Metadata\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Resource\Factory\LinkFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\NotExposedOperationResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class NotExposedOperationResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testItIgnoresClassesWithoutResources()
    {
        $linkFactoryProphecy = $this->prophesize(LinkFactoryInterface::class);
        $linkFactoryProphecy->createLinksFromIdentifiers(Argument::type(ApiResource::class))->shouldNotBeCalled();

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

    public function testItIgnoresResourcesWithAnItemOperation()
    {
        $linkFactoryProphecy = $this->prophesize(LinkFactoryInterface::class);
        $linkFactoryProphecy->createLinksFromIdentifiers(Argument::type(ApiResource::class))->shouldNotBeCalled();

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
                        '_api_AttributeResource_get' => new Get(uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])], controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                        '_api_AttributeResource_get_collection' => new GetCollection(controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
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
                        '_api_AttributeResource_get' => new Get(uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])], controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                        '_api_AttributeResource_get_collection' => new GetCollection(controller: 'api_platform.action.placeholder', shortName: 'AttributeResource', class: AttributeResource::class),
                    ],
                    uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])],
                    class: AttributeResource::class
                ),
            ]),
            $factory->create(AttributeResource::class)
        );
    }

    public function testItAddsANotExposedOperationWithoutRouteNameOnTheLastResource()
    {
        $linkFactoryProphecy = $this->prophesize(LinkFactoryInterface::class);
        $linkFactoryProphecy->createLinksFromIdentifiers(Argument::type(ApiResource::class))->willReturn([new Link()])->shouldBeCalled();

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
                        '_api_AttributeResource_get' => new NotExposed(routeName: null, controller: 'api_platform.action.not_exposed', shortName: 'AttributeResource', class: AttributeResource::class, output: false, read: false),
                    ],
                    class: AttributeResource::class
                ),
            ]),
            $factory->create(AttributeResource::class)
        );
    }

    public function testItAddsANotExposedOperationWithRouteNameOnTheLastResource()
    {
        $linkFactoryProphecy = $this->prophesize(LinkFactoryInterface::class);
        $linkFactoryProphecy->createLinksFromIdentifiers(Argument::type(ApiResource::class))->willReturn([])->shouldBeCalled();

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
                        '_api_AttributeResource_get' => new NotExposed(routeName: 'api_genid', controller: 'api_platform.action.not_exposed', shortName: 'AttributeResource', class: AttributeResource::class, output: false, read: false),
                    ],
                    class: AttributeResource::class
                ),
            ]),
            $factory->create(AttributeResource::class)
        );
    }
}
