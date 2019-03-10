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

namespace ApiPlatform\Core\Tests\Operation\Factory;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactory;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Tests\Fixtures\DummyValidatedEntity;
use ApiPlatform\Core\Tests\Fixtures\Metadata\Property\MappingPropertyMetadataFactory;
use ApiPlatform\Core\Tests\Fixtures\Metadata\Property\MappingPropertyNameCollectionMetadataFactory;
use ApiPlatform\Core\Tests\Fixtures\Metadata\Resource\MappingResourceMetadataFactory;
use ApiPlatform\Core\Tests\Fixtures\RelatedDummyEntity;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class SubresourceOperationFactoryTest extends TestCase
{
    public function testCreate()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => new ResourceMetadata('dummyEntity'),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['foo', 'subresource', 'subcollection']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = DummyEntity::class,
                $property = 'foo'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = true)),
                $resourceClass = DummyEntity::class,
                $property = 'subcollection'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_get_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                    ['subresource', RelatedDummyEntity::class, false],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource.{_format}',
                'operation_name' => 'subresource_another_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_get_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                    ['subresource', RelatedDummyEntity::class, false],
                    ['anotherSubresource', DummyEntity::class, false],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_get_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subcollections_get_subresource',
                'path' => '/dummy_entities/{id}/subcollections.{_format}',
                'operation_name' => 'subcollections_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_get_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                    ['subcollection', RelatedDummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}/another_subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                    ['subcollection', RelatedDummyEntity::class, true],
                    ['anotherSubresource', DummyEntity::class, false],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}/another_subresource/subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    public function testCreateByOverriding()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity'))
                ->withSubresourceOperations([
                    'subcollections_get_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars',
                    ],
                    'subcollections_another_subresource_get_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
                    ],
                ]),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['foo', 'subresource', 'subcollection']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = DummyEntity::class,
                $property = 'foo'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = true)),
                $resourceClass = DummyEntity::class,
                $property = 'subcollection'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_get_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                    ['subresource', RelatedDummyEntity::class, false],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource.{_format}',
                'operation_name' => 'subresource_another_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_get_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                    ['subresource', RelatedDummyEntity::class, false],
                    ['anotherSubresource', DummyEntity::class, false],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_get_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subcollections_get_subresource',
                'path' => '/dummy_entities/{id}/foobars',
                'operation_name' => 'subcollections_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_get_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                    ['subcollection', RelatedDummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
                'operation_name' => 'subcollections_another_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                    ['subcollection', RelatedDummyEntity::class, true],
                    ['anotherSubresource', DummyEntity::class, false],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar/subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    public function testCreateWithMaxDepth()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false, $maxDepth = 1)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    /**
     * Test for issue: https://github.com/api-platform/core/issues/1711.
     */
    public function testCreateWithMaxDepthMultipleSubresources()
    {
        /**
         * DummyEntity -subresource-> RelatedDummyEntity -anotherSubresource-> DummyEntity
         * DummyEntity -secondSubresource-> dummyValidatedEntity -moreSubresource-> RelatedDummyEntity.
         */
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
            DummyValidatedEntity::class => new ResourceMetadata('dummyValidatedEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource', 'secondSubresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['moreSubresource']),
                $resourceClass = DummyValidatedEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false, $maxDepth = 1)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyValidatedEntity::class, $collection = false, $maxDepth = 2)),
                $resourceClass = DummyEntity::class,
                $property = 'secondSubresource'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false)),
                $resourceClass = DummyValidatedEntity::class,
                $property = 'moreSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_get_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyValidatedEntity::class,
                'shortNames' => ['dummyValidatedEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/second_subresource.{_format}',
                'operation_name' => 'second_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_more_subresource_get_subresource' => [
                'property' => 'moreSubresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyValidatedEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                    ['secondSubresource', DummyValidatedEntity::class, false],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_more_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/second_subresource/more_subresource.{_format}',
                'operation_name' => 'second_subresource_more_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    /**
     * Test for issue: https://github.com/api-platform/core/issues/2103.
     */
    public function testCreateWithMaxDepthMultipleSubresourcesSameMaxDepth()
    {
        /**
         * DummyEntity -subresource (maxDepth=1)-> RelatedDummyEntity -anotherSubresource-> DummyEntity
         * DummyEntity -secondSubresource (maxDepth=1)-> dummyValidatedEntity -moreSubresource-> RelatedDummyEntity.
         */
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
            DummyValidatedEntity::class => new ResourceMetadata('dummyValidatedEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource', 'secondSubresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['moreSubresource']),
                $resourceClass = DummyValidatedEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false, $maxDepth = 1)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyValidatedEntity::class, $collection = false, $maxDepth = 1)),
                $resourceClass = DummyEntity::class,
                $property = 'secondSubresource'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false)),
                $resourceClass = DummyValidatedEntity::class,
                $property = 'moreSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_get_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyValidatedEntity::class,
                'shortNames' => ['dummyValidatedEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/second_subresource.{_format}',
                'operation_name' => 'second_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    public function testCreateSelfReferencingSubresources()
    {
        /**
         * DummyEntity -subresource-> DummyEntity -subresource-> DummyEntity ...
         */
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource']),
                $resourceClass = DummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    public function testCreateWithEnd()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['id']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = true)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false))->withIdentifier(true),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'id'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $result = $subresourceOperationFactory->create(DummyEntity::class);
        $this->assertEquals([
            'api_dummy_entities_subresources_get_subresource' => [
                'property' => 'subresource',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subresources_get_subresource',
                'path' => '/dummy_entities/{id}/subresources.{_format}',
                'operation_name' => 'subresources_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresources_item_get_subresource' => [
                'property' => 'id',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                    ['subresource', RelatedDummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subresources_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresources/{subresource}.{_format}',
                'operation_name' => 'subresources_item_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $result);
    }

    public function testCreateWithEndButNoCollection()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['id']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false))->withIdentifier(true),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'id'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $result = $subresourceOperationFactory->create(DummyEntity::class);
        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $result);
    }

    public function testCreateWithRootResourcePrefix()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => new ResourceMetadata('dummyEntity', null, null, null, null, ['route_prefix' => 'root_resource_prefix']),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false, $maxDepth = 1)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true],
                ],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/root_resource_prefix/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }
}
