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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeConfigOperations;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeDefaultOperations;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeOnlyOperation;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeResource;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeResources;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\ExtraPropertiesResource;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\PasswordResource;
use ApiPlatform\Metadata\Tests\Fixtures\State\AttributeResourceProcessor;
use ApiPlatform\Metadata\Tests\Fixtures\State\AttributeResourceProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class AttributesResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function getDefaultGraphqlOperations(string $shortName, string $class, mixed $provider = null): array
    {
        return [
            'collection_query' => new QueryCollection(shortName: $shortName, class: $class, normalizationContext: ['skip_null_values' => true], provider: $provider),
            'item_query' => new Query(shortName: $shortName, class: $class, normalizationContext: ['skip_null_values' => true], provider: $provider),
            'update' => new Mutation(shortName: $shortName, class: $class, normalizationContext: ['skip_null_values' => true], name: 'update', description: "Updates a $shortName.", provider: $provider),
            'delete' => new DeleteMutation(shortName: $shortName, class: $class, normalizationContext: ['skip_null_values' => true], name: 'delete', description: "Deletes a $shortName.", provider: $provider),
            'create' => new Mutation(shortName: $shortName, class: $class, normalizationContext: ['skip_null_values' => true], name: 'create', description: "Creates a $shortName.", provider: $provider),
        ];
    }

    public function testCreate(): void
    {
        $attributeResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory(graphQlEnabled: true);

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    normalizationContext: ['skip_null_values' => true],
                    class: AttributeResource::class,
                    provider: AttributeResourceProvider::class,
                    operations: [
                        '_api_AttributeResource_get' => new Get(
                            shortName: 'AttributeResource', class: AttributeResource::class, normalizationContext: ['skip_null_values' => true], priority: 1, provider: AttributeResourceProvider::class,
                        ),
                        '_api_AttributeResource_put' => new Put(
                            shortName: 'AttributeResource', class: AttributeResource::class, normalizationContext: ['skip_null_values' => true], priority: 2, provider: AttributeResourceProvider::class,
                        ),
                        '_api_AttributeResource_delete' => new Delete(
                            shortName: 'AttributeResource', class: AttributeResource::class, normalizationContext: ['skip_null_values' => true], priority: 3, provider: AttributeResourceProvider::class,
                        ),
                    ],
                    graphQlOperations: $this->getDefaultGraphqlOperations('AttributeResource', AttributeResource::class, AttributeResourceProvider::class)
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}{._format}',
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{identifier}{._format}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}{._format}',
                            shortName: 'AttributeResource',
                            inputFormats: ['json' => ['application/merge-patch+json']],
                            priority: 4,
                            status: 301,
                            provider: AttributeResourceProvider::class,
                            // @noRector \Rector\Php81\Rector\Array_\FirstClassCallableRector
                            processor: [AttributeResourceProcessor::class, 'process']
                        ),
                        '_api_/dummy/{dummyId}/attribute_resources/{identifier}{._format}_patch' => new Patch(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}{._format}',
                            shortName: 'AttributeResource',
                            inputFormats: ['json' => ['application/merge-patch+json']],
                            priority: 5,
                            status: 301,
                            provider: AttributeResourceProvider::class,
                            // @noRector \Rector\Php81\Rector\Array_\FirstClassCallableRector
                            processor: [AttributeResourceProcessor::class, 'process']
                        ),
                    ],
                    inputFormats: ['json' => ['application/merge-patch+json']],
                    status: 301,
                    provider: AttributeResourceProvider::class,
                    // @noRector \Rector\Php81\Rector\Array_\FirstClassCallableRector
                    processor: [AttributeResourceProcessor::class, 'process']
                ),
            ]),
            $attributeResourceMetadataCollectionFactory->create(AttributeResource::class)
        );

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResources::class, [
                new ApiResource(
                    uriTemplate: '/attribute_resources{._format}',
                    shortName: 'AttributeResources',
                    normalizationContext: ['skip_null_values' => true],
                    class: AttributeResources::class,
                    provider: AttributeResourceProvider::class,
                    operations: [
                        '_api_/attribute_resources{._format}_get_collection' => new GetCollection(
                            shortName: 'AttributeResources', class: AttributeResources::class, uriTemplate: '/attribute_resources{._format}', normalizationContext: ['skip_null_values' => true], priority: 1, provider: AttributeResourceProvider::class,
                        ),
                        '_api_/attribute_resources{._format}_post' => new Post(
                            shortName: 'AttributeResources', class: AttributeResources::class, uriTemplate: '/attribute_resources{._format}', normalizationContext: ['skip_null_values' => true], priority: 2, provider: AttributeResourceProvider::class,
                        ),
                    ],
                    graphQlOperations: $this->getDefaultGraphqlOperations('AttributeResources', AttributeResources::class, AttributeResourceProvider::class)
                ),
            ]),
            $attributeResourceMetadataCollectionFactory->create(AttributeResources::class)
        );
    }

    public function testCreateWithDefaults(): void
    {
        $attributeResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory(null, null, [
            'cache_headers' => [
                'max_age' => 60,
                'shared_max_age' => 120,
                'public' => true,
            ],
            'extra_properties' => ['standard_put' => true],
            'non_existing_attribute' => 'foo',
        ]);

        // Check the AttributeDefaultOperations it specifies a shared_max_age that should not be overridden
        $operation = new HttpOperation(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, cacheHeaders: ['max_age' => 60, 'shared_max_age' => 60, 'public' => true], paginationItemsPerPage: 10, extraProperties: ['non_existing_attribute' => 'foo', 'generated_operation' => true, 'standard_put' => true]);

        $this->assertEquals(new ResourceMetadataCollection(AttributeDefaultOperations::class, [
            new ApiResource(
                shortName: 'AttributeDefaultOperations',
                class: AttributeDefaultOperations::class,
                graphQlOperations: [],
                operations: [
                    '_api_AttributeDefaultOperations_get' => (new Get())->withOperation($operation),
                    '_api_AttributeDefaultOperations_get_collection' => (new GetCollection())->withOperation($operation),
                    '_api_AttributeDefaultOperations_post' => (new Post())->withOperation($operation),
                    '_api_AttributeDefaultOperations_put' => (new Put())->withOperation($operation),
                    '_api_AttributeDefaultOperations_patch' => (new Patch())->withOperation($operation),
                    '_api_AttributeDefaultOperations_delete' => (new Delete())->withOperation($operation),
                ],
                cacheHeaders: ['max_age' => 60, 'shared_max_age' => 60, 'public' => true],
                paginationItemsPerPage: 10,
                extraProperties: ['non_existing_attribute' => 'foo', 'standard_put' => true]
            ),
        ]), $attributeResourceMetadataCollectionFactory->create(AttributeDefaultOperations::class));
    }

    public function testCreateWithConfigOperations(): void
    {
        $attributesResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory(defaults: ['operations' => [Get::class, Post::class]]);

        $this->assertEquals(new ResourceMetadataCollection(AttributeConfigOperations::class, [
            new ApiResource(
                shortName: 'AttributeConfigOperations',
                operations: [
                    '_api_AttributeConfigOperations_get' => new Get(shortName: 'AttributeConfigOperations', class: AttributeConfigOperations::class, priority: 0, extraProperties: ['generated_operation' => true]),
                    '_api_AttributeConfigOperations_post' => new Post(shortName: 'AttributeConfigOperations', class: AttributeConfigOperations::class, priority: 1, extraProperties: ['generated_operation' => true]),
                ],
                class: AttributeConfigOperations::class,
            ),
        ]), $attributesResourceMetadataCollectionFactory->create(AttributeConfigOperations::class));
    }

    public function testCreateShouldNotOverrideWithDefault(): void
    {
        $attributeResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory(
            null, null, [
                'pagination_items_per_page' => 3,
            ]
        );

        $operation = new HttpOperation(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, paginationItemsPerPage: 10, cacheHeaders: ['shared_max_age' => 60], extraProperties: ['generated_operation' => true]);
        $this->assertEquals(new ResourceMetadataCollection(AttributeDefaultOperations::class, [
            new ApiResource(
                shortName: 'AttributeDefaultOperations',
                class: AttributeDefaultOperations::class,
                operations: [
                    '_api_AttributeDefaultOperations_get' => (new Get())->withOperation($operation),
                    '_api_AttributeDefaultOperations_get_collection' => (new GetCollection())->withOperation($operation),
                    '_api_AttributeDefaultOperations_post' => (new Post())->withOperation($operation),
                    '_api_AttributeDefaultOperations_put' => (new Put())->withOperation($operation),
                    '_api_AttributeDefaultOperations_patch' => (new Patch())->withOperation($operation),
                    '_api_AttributeDefaultOperations_delete' => (new Delete())->withOperation($operation),
                ],
                cacheHeaders: ['shared_max_age' => 60],
                graphQlOperations: [],
                paginationItemsPerPage: 10
            ),
        ]), $attributeResourceMetadataCollectionFactory->create(AttributeDefaultOperations::class));
    }

    public function testExtraProperties(): void
    {
        $attributeResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory();
        $extraPropertiesResource = $attributeResourceMetadataCollectionFactory->create(ExtraPropertiesResource::class);

        $this->assertEquals($extraPropertiesResource[0]->getExtraProperties(), ['foo' => 'bar']);
        $this->assertEquals($extraPropertiesResource->getOperation('_api_ExtraPropertiesResource_get')->getExtraProperties(), ['foo' => 'bar']);
    }

    public function testOverrideNameWithoutOperations(): void
    {
        $attributeResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory();

        $operation = new HttpOperation(shortName: 'AttributeOnlyOperation', class: AttributeOnlyOperation::class);
        $this->assertEquals(new ResourceMetadataCollection(AttributeOnlyOperation::class, [
            new ApiResource(
                shortName: 'AttributeOnlyOperation',
                class: AttributeOnlyOperation::class,
                operations: [
                    'my own name' => (new Get(name: 'my own name', priority: 1))->withOperation($operation),
                ]
            ),
        ]), $attributeResourceMetadataCollectionFactory->create(AttributeOnlyOperation::class));
    }

    /**
     * Tests issue #5235.
     */
    public function testNameDeclarationShouldNotBeRemoved(): void
    {
        $attributeResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory();

        $metadataCollection = $attributeResourceMetadataCollectionFactory->create(PasswordResource::class);
        $operations = $metadataCollection[0]->getOperations();
        $this->assertTrue($operations->has('password_set'));
        $this->assertTrue($operations->has('password_reset_token'));
        $this->assertTrue($operations->has('password_reset'));
    }
}
