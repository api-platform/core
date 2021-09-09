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

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\UriVariablesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeDefaultOperations;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResources;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class UriVariablesResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        $propertyNameCollectionProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionProphecy->create(AttributeResource::class)->willReturn(new PropertyNameCollection(['id']));
        $propertyNameCollectionProphecy->create(AttributeDefaultOperations::class)->willReturn(new PropertyNameCollection(['id']));
        $propertyNameCollectionProphecy->create(AttributeResources::class)->willReturn(new PropertyNameCollection([]));
        $propertyMetadataProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataProphecy->create(AttributeResource::class, 'id')->willReturn(new ApiProperty(identifier: true));
        $propertyMetadataProphecy->create(AttributeDefaultOperations::class, 'id')->willReturn(new ApiProperty(identifier: true));
        $decorated = new AttributesResourceMetadataCollectionFactory();
        $identifierResourceMetadataCollectionFactory = new UriVariablesResourceMetadataCollectionFactory($decorated, $propertyNameCollectionProphecy->reveal(), $propertyMetadataProphecy->reveal());

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    normalizationContext: ['skip_null_values' => true],
                    class: AttributeResource::class,
                    uriVariables: ['id' => ['class' => AttributeResource::class, 'identifiers' => ['id']]],
                    operations: [
                        '_api_AttributeResource_get' => new Get(
                            shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', normalizationContext: ['skip_null_values' => true], uriVariables: ['id' => ['class' => AttributeResource::class, 'identifiers' => ['id']]], priority: 1
                        ),
                        '_api_AttributeResource_put' => new Put(
                            shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', normalizationContext: ['skip_null_values' => true], uriVariables: ['id' => ['class' => AttributeResource::class, 'identifiers' => ['id']]], priority: 2
                        ),
                        '_api_AttributeResource_delete' => new Delete(
                            shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', normalizationContext: ['skip_null_values' => true], uriVariables: ['id' => ['class' => AttributeResource::class, 'identifiers' => ['id']]], priority: 3
                        ),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',

                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}.{_format}',
                    uriVariables: ['dummyId' => ['class' => Dummy::class, 'identifiers' => ['id']], 'identifier' => ['class' => AttributeResource::class, 'identifiers' => ['identifier']]],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{identifier}.{_format}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}.{_format}',
                            shortName: 'AttributeResource',
                            uriVariables: ['dummyId' => ['class' => Dummy::class, 'identifiers' => ['id']], 'identifier' => ['class' => AttributeResource::class, 'identifiers' => ['identifier']]],
                            inputFormats: ['json' => ['application/merge-patch+json']], priority: 4
                        ),
                        '_api_/dummy/{dummyId}/attribute_resources/{identifier}.{_format}_patch' => new Patch(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}.{_format}',
                            shortName: 'AttributeResource',
                            uriVariables: ['dummyId' => ['class' => Dummy::class, 'identifiers' => ['id']], 'identifier' => ['class' => AttributeResource::class, 'identifiers' => ['identifier']]],
                            inputFormats: ['json' => ['application/merge-patch+json']], priority: 5
                        ),
                    ],
                    inputFormats: ['json' => ['application/merge-patch+json']]
                ),
            ]),
            $identifierResourceMetadataCollectionFactory->create(AttributeResource::class)
        );

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResources::class, [
                new ApiResource(
                    uriTemplate: '/attribute_resources.{_format}',
                    shortName: 'AttributeResources',
                    normalizationContext: ['skip_null_values' => true],
                    class: AttributeResources::class,
                    operations: [
                        '_api_/attribute_resources.{_format}_get_collection' => new GetCollection(
                            shortName: 'AttributeResources', class: AttributeResources::class, controller: 'api_platform.action.placeholder', uriTemplate: '/attribute_resources.{_format}', normalizationContext: ['skip_null_values' => true], priority: 1
                        ),
                        '_api_/attribute_resources.{_format}_post' => new Post(
                            shortName: 'AttributeResources', class: AttributeResources::class, controller: 'api_platform.action.placeholder', uriTemplate: '/attribute_resources.{_format}', normalizationContext: ['skip_null_values' => true], priority: 2
                        ),
                    ]
                ),
            ]),
            $identifierResourceMetadataCollectionFactory->create(AttributeResources::class)
        );

        $operation = new Operation(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder', collection: false);

        $this->assertEquals(new ResourceMetadataCollection(AttributeDefaultOperations::class, [
            new ApiResource(
                uriVariables: ['id' => ['class' => AttributeDefaultOperations::class,  'identifiers' => ['id']]],
                shortName: 'AttributeDefaultOperations',
                class: AttributeDefaultOperations::class,
                operations: [
                    '_api_AttributeDefaultOperations_get' => (new Get())->withOperation($operation)->withUriVariables(['id' => ['class' => AttributeDefaultOperations::class, 'identifiers' => ['id']]]),
                    '_api_AttributeDefaultOperations_get_collection' => (new GetCollection())->withOperation($operation),
                    '_api_AttributeDefaultOperations_post' => (new Post())->withOperation($operation),
                    '_api_AttributeDefaultOperations_put' => (new Put())->withOperation($operation)->withUriVariables(['id' => ['class' => AttributeDefaultOperations::class, 'identifiers' => ['id']]]),
                    '_api_AttributeDefaultOperations_patch' => (new Patch())->withOperation($operation)->withUriVariables(['id' => ['class' => AttributeDefaultOperations::class, 'identifiers' => ['id']]]),
                    '_api_AttributeDefaultOperations_delete' => (new Delete())->withOperation($operation)->withUriVariables(['id' => ['class' => AttributeDefaultOperations::class, 'identifiers' => ['id']]]),
                ]
            ),
        ]), $identifierResourceMetadataCollectionFactory->create(AttributeDefaultOperations::class));
    }

    public function testDecorated()
    {
        $propertyNameCollectionProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->create('String')->willReturn(new ResourceMetadataCollection('String', [new ApiResource(uriVariables: 'id')]));
        $decorated->create('Array')->willReturn(new ResourceMetadataCollection('String', [new ApiResource(uriVariables: ['id'])]));
        $identifierResourceMetadataCollectionFactory = new UriVariablesResourceMetadataCollectionFactory($decorated->reveal(), $propertyNameCollectionProphecy->reveal(), $propertyMetadataProphecy->reveal());

        $resourceMetadataCollection = $identifierResourceMetadataCollectionFactory->create('String');
        $this->assertEquals($resourceMetadataCollection[0]->getUriVariables(), ['id' => ['class' => 'String', 'identifiers' => ['id']]]);
        $resourceMetadataCollection = $identifierResourceMetadataCollectionFactory->create('Array');
        $this->assertEquals($resourceMetadataCollection[0]->getUriVariables(), ['id' => ['class' => 'Array', 'identifiers' => ['id']]]);
    }
}
