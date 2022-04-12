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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeDefaultOperations;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResources;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class AttributesResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function getDefaultGraphqlOperations(string $shortName, string $class): array
    {
        return [
            'collection_query' => new QueryCollection(shortName: $shortName, class: $class, normalizationContext: ['skip_null_values' => true]),
            'item_query' => new Query(shortName: $shortName, class: $class, normalizationContext: ['skip_null_values' => true]),
            'update' => new Mutation(shortName: $shortName, class: $class, normalizationContext: ['skip_null_values' => true], name: 'update', description: "Updates a $shortName."),
            'delete' => new Mutation(shortName: $shortName, class: $class, normalizationContext: ['skip_null_values' => true], delete: true, name: 'delete', description: "Deletes a $shortName."),
            'create' => new Mutation(shortName: $shortName, class: $class, normalizationContext: ['skip_null_values' => true], name: 'create', description: "Creates a $shortName."),
        ];
    }

    public function testCreate(): void
    {
        $attributeResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory();

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    normalizationContext: ['skip_null_values' => true],
                    class: AttributeResource::class,
                    operations: [
                        '_api_AttributeResource_get' => new Get(
                            shortName: 'AttributeResource', class: AttributeResource::class, normalizationContext: ['skip_null_values' => true], priority: 1
                        ),
                        '_api_AttributeResource_put' => new Put(
                            shortName: 'AttributeResource', class: AttributeResource::class, normalizationContext: ['skip_null_values' => true], priority: 2
                        ),
                        '_api_AttributeResource_delete' => new Delete(
                            shortName: 'AttributeResource', class: AttributeResource::class, normalizationContext: ['skip_null_values' => true], priority: 3
                        ),
                    ],
                    graphQlOperations: $this->getDefaultGraphqlOperations('AttributeResource', AttributeResource::class)
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}.{_format}',
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{identifier}.{_format}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}.{_format}',
                            shortName: 'AttributeResource',
                            inputFormats: ['json' => ['application/merge-patch+json']],
                            priority: 4,
                            status: 301
                        ),
                        '_api_/dummy/{dummyId}/attribute_resources/{identifier}.{_format}_patch' => new Patch(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}.{_format}',
                            shortName: 'AttributeResource',
                            inputFormats: ['json' => ['application/merge-patch+json']],
                            priority: 5,
                            status: 301
                        ),
                    ],
                    inputFormats: ['json' => ['application/merge-patch+json']],
                    status: 301
                ),
            ]),
            $attributeResourceMetadataCollectionFactory->create(AttributeResource::class)
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
                            shortName: 'AttributeResources', class: AttributeResources::class, uriTemplate: '/attribute_resources.{_format}', normalizationContext: ['skip_null_values' => true], priority: 1
                        ),
                        '_api_/attribute_resources.{_format}_post' => new Post(
                            shortName: 'AttributeResources', class: AttributeResources::class, uriTemplate: '/attribute_resources.{_format}', normalizationContext: ['skip_null_values' => true], priority: 2
                        ),
                    ],
                    graphQlOperations: $this->getDefaultGraphqlOperations('AttributeResources', AttributeResources::class)
                ),
            ]),
            $attributeResourceMetadataCollectionFactory->create(AttributeResources::class)
        );
    }

    public function testCreateWithDefaults(): void
    {
        $attributeResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory(null, null, ['attributes' => ['cache_headers' => ['max_age' => 60], 'non_existing_attribute' => 'foo']]);

        $operation = new Operation(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, collection: false, cacheHeaders: ['max_age' => 60], paginationItemsPerPage: 10);
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
                cacheHeaders: ['max_age' => 60],
                paginationItemsPerPage: 10
            ),
        ]), $attributeResourceMetadataCollectionFactory->create(AttributeDefaultOperations::class));
    }

    public function testCreateShouldNotOverrideWithDefault(): void
    {
        $attributeResourceMetadataCollectionFactory = new AttributesResourceMetadataCollectionFactory(
            null, null, [
                'attributes' => [
                    'pagination_items_per_page' => 3,
                ],
            ]
        );

        $operation = new Operation(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, paginationItemsPerPage: 10);
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
                graphQlOperations: [],
                paginationItemsPerPage: 10
            ),
        ]), $attributeResourceMetadataCollectionFactory->create(AttributeDefaultOperations::class));
    }
}
