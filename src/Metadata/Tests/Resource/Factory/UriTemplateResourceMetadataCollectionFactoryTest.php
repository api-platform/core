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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\LinkFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\UriTemplateResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeResource;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\MagicPropertyResource;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class UriTemplateResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate(): void
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
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])],
                    operations: [
                        new Get(
                            shortName: 'AttributeResource',
                            class: AttributeResource::class,
                            controller: 'api_platform.action.placeholder',
                            uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'])],
                            routePrefix: '/prefix',
                        ),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/attribute_resources/by_name/{name}',
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/attribute_resources/{foo}',
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
                        '_api_/attribute_resources/{id}{._format}_get' => new Get(uriTemplate: '/attribute_resources/{id}{._format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')], name: '_api_/attribute_resources/{id}{._format}_get'),
                        '_api_/attribute_resources/{id}{._format}_put' => new Put(uriTemplate: '/attribute_resources/{id}{._format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')], name: '_api_/attribute_resources/{id}{._format}_put'),
                        '_api_/attribute_resources/{id}{._format}_delete' => new Delete(uriTemplate: '/attribute_resources/{id}{._format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')], name: '_api_/attribute_resources/{id}{._format}_delete'),
                        '_api_/attribute_resources{._format}_get_collection' => new GetCollection(uriTemplate: '/attribute_resources{._format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', name: '_api_/attribute_resources{._format}_get_collection'),
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
                new ApiResource(
                    uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')],
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    operations: [
                        '_api_/prefix/attribute_resources/{id}{._format}_get' => new Get(
                            uriTemplate: '/attribute_resources/{id}{._format}',
                            shortName: 'AttributeResource',
                            class: AttributeResource::class,
                            controller: 'api_platform.action.placeholder',
                            uriVariables: ['id' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'id')],
                            routePrefix: '/prefix',
                            name: '_api_/prefix/attribute_resources/{id}{._format}_get'),
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
                    uriTemplate: '/attribute_resources/{foo}',
                    uriVariables: ['foo' => new Link(fromClass: AttributeResource::class, identifiers: ['id'], parameterName: 'foo')],
                    operations: [],
                ),
            ]),
            $uriTemplateResourceMetadataCollectionFactory->create(AttributeResource::class)
        );
    }

    /**
     * @see https://github.com/api-platform/core/issues/8167
     *
     * A legacy-loaded operation can carry uri variables that no longer match
     * its uri template (e.g. after a rename), forcing the "guess" branch to
     * fill in the missing one. On a class whose identifier is only reachable
     * through a magic accessor (like an Eloquent model), `property_exists()`
     * can never find it and used to silently fall back to "id". The fix must
     * consult the link factory instead, which resolves the identifier from
     * property metadata (`isIdentifier()`), not from the declared properties
     * of the class.
     */
    public function testResolvesMissingUriVariableIdentifierFromMetadataInsteadOfGuessingId(): void
    {
        $propertyNameCollectionFactory = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->method('create')->willReturn(new PropertyNameCollection(['uuid']));

        $propertyMetadataFactory = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->method('create')->willReturnCallback(
            static fn (string $resourceClass, string $property): ApiProperty => new ApiProperty(identifier: 'uuid' === $property)
        );

        $linkFactory = new LinkFactory($propertyNameCollectionFactory, $propertyMetadataFactory, $this->createStub(ResourceClassResolverInterface::class));

        // Simulates stale uri variables coming from a legacy (XML/YAML) loader: neither
        // key matches the current uri template's "{uuid}" variable, and there are two of
        // them where the route only expects one, so the uri template factory must rebuild
        // the uri variables from scratch instead of trusting what's already there.
        $operation = new Get(
            shortName: 'MagicPropertyResource',
            class: MagicPropertyResource::class,
            uriTemplate: '/magic_property_resources/{uuid}',
            uriVariables: [
                'legacyId' => new Link(fromClass: MagicPropertyResource::class, identifiers: ['legacyId']),
                'other' => new Link(fromClass: MagicPropertyResource::class, identifiers: ['other']),
            ],
            extraProperties: ['is_legacy_resource_metadata' => true],
        );

        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceMetadataCollection(MagicPropertyResource::class, [
            new ApiResource(
                shortName: 'MagicPropertyResource',
                class: MagicPropertyResource::class,
                operations: ['get' => $operation],
            ),
        ]));

        $uriTemplateResourceMetadataCollectionFactory = new UriTemplateResourceMetadataCollectionFactory($linkFactory, $this->createStub(PathSegmentNameGeneratorInterface::class), $decorated);

        $result = $uriTemplateResourceMetadataCollectionFactory->create(MagicPropertyResource::class);
        $resultOperation = iterator_to_array($result[0]->getOperations())['get'];

        $this->assertSame(['uuid'], $resultOperation->getUriVariables()['uuid']->getIdentifiers());
    }
}
