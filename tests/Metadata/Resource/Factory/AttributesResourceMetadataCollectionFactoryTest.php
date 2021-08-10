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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeDefaultOperations;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResources;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class AttributesResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        $attributeResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory();

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    types: ['AttributeResource'],
                    normalizationContext: ['skip_null_values' => true],
                    class: AttributeResource::class,
                    operations: [
                        '_api_AttributeResource_get' => new Get(
                            shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', types: ['AttributeResource'], normalizationContext: ['skip_null_values' => true]
                        ),
                        '_api_AttributeResource_put' => new Put(
                            shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', types: ['AttributeResource'], normalizationContext: ['skip_null_values' => true]
                        ),
                        '_api_AttributeResource_delete' => new Delete(
                            shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', types: ['AttributeResource'], normalizationContext: ['skip_null_values' => true]
                        ),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    types: ['AttributeResource'],
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}.{_format}',
                    identifiers: ['dummyId' => [Dummy::class, 'id'], 'identifier' => [AttributeResource::class, 'identifier']],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{identifier}.{_format}_get' => new Get(
                            class: AttributeResource::class,
                            types: ['AttributeResource'],
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}.{_format}',
                            shortName: 'AttributeResource',
                            identifiers: ['dummyId' => [Dummy::class, 'id'], 'identifier' => [AttributeResource::class, 'identifier']],
                            inputFormats: ['json' => ['application/merge-patch+json']]
                        ),
                        '_api_/dummy/{dummyId}/attribute_resources/{identifier}.{_format}_patch' => new Patch(
                            class: AttributeResource::class,
                            types: ['AttributeResource'],
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}.{_format}',
                            shortName: 'AttributeResource',
                            identifiers: ['dummyId' => [Dummy::class, 'id'], 'identifier' => [AttributeResource::class, 'identifier']],
                            inputFormats: ['json' => ['application/merge-patch+json']]
                        ),
                    ],
                    inputFormats: ['json' => ['application/merge-patch+json']]
                ),
            ]),
            $attributeResourceMetadataCollectionFactory->create(AttributeResource::class)
        );

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResources::class, [
                new ApiResource(
                    uriTemplate: '/attribute_resources.{_format}',
                    shortName: 'AttributeResources',
                    types: ['AttributeResources'],
                    normalizationContext: ['skip_null_values' => true],
                    class: AttributeResources::class,
                    operations: [
                        '_api_/attribute_resources.{_format}_get_collection' => new GetCollection(
                            shortName: 'AttributeResources', class: AttributeResources::class, controller: 'api_platform.action.placeholder', uriTemplate: '/attribute_resources.{_format}', types: ['AttributeResources'], normalizationContext: ['skip_null_values' => true],
                        ),
                        '_api_/attribute_resources.{_format}_post' => new Post(
                            shortName: 'AttributeResources', class: AttributeResources::class, controller: 'api_platform.action.placeholder', uriTemplate: '/attribute_resources.{_format}', types: ['AttributeResources'], normalizationContext: ['skip_null_values' => true],
                        ),
                    ]
                ),
            ]),
            $attributeResourceMetadataCollectionFactory->create(AttributeResources::class)
        );

        $operation = new Operation(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder', types: ['AttributeDefaultOperations'], collection: false);
        $this->assertEquals(new ResourceMetadataCollection(AttributeDefaultOperations::class, [
            new ApiResource(
                shortName: 'AttributeDefaultOperations',
                types: ['AttributeDefaultOperations'],
                class: AttributeDefaultOperations::class,
                operations: [
                    '_api_AttributeDefaultOperations_get' => (new Get())->withOperation($operation),
                    '_api_AttributeDefaultOperations_get_collection' => (new GetCollection())->withOperation($operation),
                    '_api_AttributeDefaultOperations_post' => (new Post())->withOperation($operation)->withCollection(true),
                    '_api_AttributeDefaultOperations_put' => (new Put())->withOperation($operation),
                    '_api_AttributeDefaultOperations_patch' => (new Patch())->withOperation($operation),
                    '_api_AttributeDefaultOperations_delete' => (new Delete())->withOperation($operation),
                ]
            ),
        ]), $attributeResourceMetadataCollectionFactory->create(AttributeDefaultOperations::class));
    }
}
