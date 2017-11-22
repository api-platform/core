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

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactory;
use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Tests\Fixtures\RelatedDummyEntity;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class SubresourceOperationFactoryTest extends TestCase
{
    public function testCreate()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummyEntity'));
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyEntity'));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['foo', 'subresource', 'subcollection']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['bar', 'anotherSubresource']));

        $subresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class));
        $subresourceMetadataCollection = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, true));
        $anotherSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'foo')->shouldBeCalled()->willReturn(new PropertyMetadata());
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->shouldBeCalled()->willReturn($subresourceMetadata);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subcollection')->shouldBeCalled()->willReturn($subresourceMetadataCollection);
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'bar')->shouldBeCalled()->willReturn(new PropertyMetadata());
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'anotherSubresource')->shouldBeCalled()->willReturn($anotherSubresourceMetadata);

        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresource');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subcollection', true)->shouldBeCalled()->willReturn('subcollections');
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity', true)->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('anotherSubresource', false)->shouldBeCalled()->willReturn('another_subresource');

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $pathSegmentNameGeneratorProphecy->reveal());

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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummyEntity'));
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn((new ResourceMetadata('dummyEntity'))->withSubresourceOperations([
            'subcollections_get_subresource' => [
                'path' => '/dummy_entities/{id}/foobars',
            ],
            'subcollections_another_subresource_get_subresource' => [
                'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
            ],
        ]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['foo', 'subresource', 'subcollection']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['bar', 'anotherSubresource']));

        $subresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class));
        $subresourceMetadataCollection = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, true));
        $anotherSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'foo')->shouldBeCalled()->willReturn(new PropertyMetadata());
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->shouldBeCalled()->willReturn($subresourceMetadata);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subcollection')->shouldBeCalled()->willReturn($subresourceMetadataCollection);
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'bar')->shouldBeCalled()->willReturn(new PropertyMetadata());
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'anotherSubresource')->shouldBeCalled()->willReturn($anotherSubresourceMetadata);

        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresource');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subcollection', true)->shouldBeCalled()->willReturn('subcollections');
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity', true)->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('anotherSubresource', false)->shouldBeCalled()->willReturn('another_subresource');

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $pathSegmentNameGeneratorProphecy->reveal());

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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummyEntity'));
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyEntity'));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['subresource']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['bar', 'anotherSubresource']));

        $subresourceMetadataCollectionWithMaxDepth = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, false, 1));
        $anotherSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->shouldBeCalled()->willReturn($subresourceMetadataCollectionWithMaxDepth);
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'bar')->shouldBeCalled()->willReturn(new PropertyMetadata());
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'anotherSubresource')->shouldBeCalled()->willReturn($anotherSubresourceMetadata);

        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity', true)->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresource');

        $subresourceOperationFactory = new SubresourceOperationFactory(
                $resourceMetadataFactoryProphecy->reveal(),
                $propertyNameCollectionFactoryProphecy->reveal(),
                $propertyMetadataFactoryProphecy->reveal(),
                $pathSegmentNameGeneratorProphecy->reveal()
        );

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
}
