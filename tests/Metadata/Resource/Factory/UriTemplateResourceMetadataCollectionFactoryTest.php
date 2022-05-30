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

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\LinkFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\UriTemplateResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class UriTemplateResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Argument::cetera())->willReturn(new PropertyNameCollection());
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $linkFactory = new LinkFactory($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal());
        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('AttributeResource')->willReturn('attribute_resources');
        $pathSegmentNameGeneratorProphecy->getSegmentName('AttributeDefaultOperations')->willReturn('attribute_default_operations');
        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(AttributeResource::class)->willReturn(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])],
                    operations: [
                        '_api_AttributeResource_get' => new Get(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])]),
                        '_api_AttributeResource_put' => new Put(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])]),
                        '_api_AttributeResource_delete' => new Delete(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])]),
                        '_api_AttributeResource_get_collection' => new GetCollection(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder'),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    uriVariables: ['dummyId' => new Link(fromClass: Dummy::class, identifiers: ['id']), 'id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{id}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                            shortName: 'AttributeResource',
                            uriVariables: ['dummyId' => new Link(fromClass: Dummy::class, identifiers: ['id']), 'id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])],
                        ),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/attribute_resources/by_name/{name}',
                    uriVariables: 'name',
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/attribute_resources/by_name/{name}',
                    uriVariables: ['name'],
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    uriVariables: ['dummyId' => [Dummy::class, 'id'], 'id' => [AttributeResource::class, 'id']],
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    uriVariables: ['dummyId' => ['from_class' => Dummy::class, 'identifiers' => ['id']], 'id' => ['from_class' => AttributeResource::class, 'identifiers' => ['id']]],
                ),
            ]),
        );

        $uriTemplateResourceMetadataCollectionFactory = new UriTemplateResourceMetadataCollectionFactory($linkFactory, $pathSegmentNameGeneratorProphecy->reveal(), $resourceCollectionMetadataFactoryProphecy->reveal());

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')],
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    operations: [
                        '_api_/attribute_resources/{id}.{_format}_get' => new Get(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')], name: '_api_/attribute_resources/{id}.{_format}_get'),
                        '_api_/attribute_resources/{id}.{_format}_put' => new Put(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')], name: '_api_/attribute_resources/{id}.{_format}_put'),
                        '_api_/attribute_resources/{id}.{_format}_delete' => new Delete(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')], name: '_api_/attribute_resources/{id}.{_format}_delete'),
                        '_api_/attribute_resources.{_format}_get_collection' => new GetCollection(uriTemplate: '/attribute_resources.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', name: '_api_/attribute_resources.{_format}_get_collection'),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    uriVariables: ['dummyId' => new Link(fromClass: Dummy::class, identifiers: ['id'], parameterName: 'dummyId'), 'id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{id}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                            shortName: 'AttributeResource',
                            uriVariables: ['dummyId' => new Link(fromClass: Dummy::class, identifiers: ['id'], parameterName: 'dummyId'), 'id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')],
                            extraProperties: ['user_defined_uri_template' => true],
                            name: '_api_/dummy/{dummyId}/attribute_resources/{id}_get'
                        ),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/attribute_resources/by_name/{name}',
                    uriVariables: ['name' => new Link(fromClass: AttributeResource::class, identifiers: ['name'], parameterName: 'name')],
                    operations: [],
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/attribute_resources/by_name/{name}',
                    uriVariables: ['name' => new Link(fromClass: AttributeResource::class, identifiers: ['name'], parameterName: 'name')],
                    operations: [],
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    uriVariables: ['dummyId' => new Link(fromClass: Dummy::class, identifiers: [], parameterName: 'dummyId', fromProperty: 'id'), 'id' => new Link(fromClass: AttributeResource::class, identifiers: [], parameterName: 'id', fromProperty: 'id')],
                    operations: [],
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    uriVariables: ['dummyId' => new Link(fromClass: Dummy::class, identifiers: ['id'], parameterName: 'dummyId'), 'id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')],
                    operations: [],
                ),
            ]),
            $uriTemplateResourceMetadataCollectionFactory->create(AttributeResource::class)
        );
    }
}
