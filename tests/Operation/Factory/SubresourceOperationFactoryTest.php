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

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
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
use ApiPlatform\Core\Tests\Fixtures\DummyValidatedEntity;
use ApiPlatform\Core\Tests\Fixtures\RelatedDummyEntity;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class SubresourceOperationFactoryTest extends TestCase
{
    use ProphecyTrait;

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
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('anotherSubresource', false)->shouldBeCalled()->willReturn('another_subresource');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $pathSegmentNameGeneratorProphecy->reveal(), $identifiersExtractorProphecy->reveal());

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
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
                    ['id', DummyEntity::class, true, ['id']],
                    ['subresource', RelatedDummyEntity::class, false, ['id']],
                ],
                'identified_by' => ['id'],
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
                    ['id', DummyEntity::class, true, ['id']],
                    ['subresource', RelatedDummyEntity::class, false, ['id']],
                    ['anotherSubresource', DummyEntity::class, false, ['id']],
                ],
                'identified_by' => ['id'],
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
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
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
                    ['id', DummyEntity::class, true, ['id']],
                    ['subcollection', RelatedDummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
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
                    ['id', DummyEntity::class, true, ['id']],
                    ['subcollection', RelatedDummyEntity::class, true, ['id']],
                    ['anotherSubresource', DummyEntity::class, false, ['id']],
                ],
                'identified_by' => ['id'],
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
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('anotherSubresource', false)->shouldBeCalled()->willReturn('another_subresource');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $pathSegmentNameGeneratorProphecy->reveal(), $identifiersExtractorProphecy->reveal());

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
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
                    ['id', DummyEntity::class, true, ['id']],
                    ['subresource', RelatedDummyEntity::class, false, ['id']],
                ],
                'identified_by' => ['id'],
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
                    ['id', DummyEntity::class, true, ['id']],
                    ['subresource', RelatedDummyEntity::class, false, ['id']],
                    ['anotherSubresource', DummyEntity::class, false, ['id']],
                ],
                'identified_by' => ['id'],
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
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
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
                    ['id', DummyEntity::class, true, ['id']],
                    ['subcollection', RelatedDummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
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
                    ['id', DummyEntity::class, true, ['id']],
                    ['subcollection', RelatedDummyEntity::class, true, ['id']],
                    ['anotherSubresource', DummyEntity::class, false, ['id']],
                ],
                'identified_by' => ['id'],
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
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresource');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory(
                $resourceMetadataFactoryProphecy->reveal(),
                $propertyNameCollectionFactoryProphecy->reveal(),
                $propertyMetadataFactoryProphecy->reveal(),
                $pathSegmentNameGeneratorProphecy->reveal(),
                $identifiersExtractorProphecy->reveal()
        );

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummyEntity'));
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyEntity'));
        $resourceMetadataFactoryProphecy->create(DummyValidatedEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyValidatedEntity'));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['subresource', 'secondSubresource']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['bar', 'anotherSubresource']));
        $propertyNameCollectionFactoryProphecy->create(DummyValidatedEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['moreSubresource']));

        $subresourceMetadataCollectionWithMaxDepth = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, false, 1));
        $anotherSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false));
        $secondSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyValidatedEntity::class, false, 2));
        $moreSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, false));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->shouldBeCalled()->willReturn($subresourceMetadataCollectionWithMaxDepth);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'secondSubresource')->shouldBeCalled()->willReturn($secondSubresourceMetadata);
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'bar')->shouldBeCalled()->willReturn(new PropertyMetadata());
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'anotherSubresource')->shouldBeCalled()->willReturn($anotherSubresourceMetadata);
        $propertyMetadataFactoryProphecy->create(DummyValidatedEntity::class, 'moreSubresource')->shouldBeCalled()->willReturn($moreSubresourceMetadata);

        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresources');
        $pathSegmentNameGeneratorProphecy->getSegmentName('secondSubresource', false)->shouldBeCalled()->willReturn('second_subresources');
        $pathSegmentNameGeneratorProphecy->getSegmentName('moreSubresource', false)->shouldBeCalled()->willReturn('mode_subresources');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $pathSegmentNameGeneratorProphecy->reveal(),
            $identifiersExtractorProphecy->reveal()
        );

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresources.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_get_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyValidatedEntity::class,
                'shortNames' => ['dummyValidatedEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_second_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/second_subresources.{_format}',
                'operation_name' => 'second_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_more_subresource_get_subresource' => [
                'property' => 'moreSubresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyValidatedEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                    ['secondSubresource', DummyValidatedEntity::class, false, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_second_subresource_more_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/second_subresources/mode_subresources.{_format}',
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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyEntity'));
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummyEntity'));
        $resourceMetadataFactoryProphecy->create(DummyValidatedEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyValidatedEntity'));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['subresource', 'secondSubresource']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['bar', 'anotherSubresource']));
        $propertyNameCollectionFactoryProphecy->create(DummyValidatedEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['moreSubresource']));

        $subresourceMetadataCollectionWithMaxDepth = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, false, 1));
        $secondSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyValidatedEntity::class, false, 1));
        $anotherSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false));
        $moreSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, false));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->shouldBeCalled()->willReturn($subresourceMetadataCollectionWithMaxDepth);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'secondSubresource')->shouldBeCalled()->willReturn($secondSubresourceMetadata);
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'bar')->shouldBeCalled()->willReturn(new PropertyMetadata());
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'anotherSubresource')->shouldBeCalled()->willReturn($anotherSubresourceMetadata);
        $propertyMetadataFactoryProphecy->create(DummyValidatedEntity::class, 'moreSubresource')->shouldBeCalled()->willReturn($moreSubresourceMetadata);

        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresources');
        $pathSegmentNameGeneratorProphecy->getSegmentName('secondSubresource', false)->shouldBeCalled()->willReturn('second_subresources');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $pathSegmentNameGeneratorProphecy->reveal(),
            $identifiersExtractorProphecy->reveal()
        );

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresources.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_get_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyValidatedEntity::class,
                'shortNames' => ['dummyValidatedEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_second_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/second_subresources.{_format}',
                'operation_name' => 'second_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    public function testCreateSelfReferencingSubresources()
    {
        /**
         * DummyEntity -subresource-> DummyEntity --> DummyEntity ...
         */
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyEntity'));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['subresource']));

        $subresource = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->shouldBeCalled()->willReturn($subresource);

        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresources');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $pathSegmentNameGeneratorProphecy->reveal(),
            $identifiersExtractorProphecy->reveal()
        );

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresources.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    /**
     * Test for issue: https://github.com/api-platform/core/issues/2533.
     */
    public function testCreateWithDifferentMaxDepthSelfReferencingSubresources()
    {
        /**
         * subresource: maxDepth = 2
         * secondSubresource: maxDepth = 1
         * DummyEntity -subresource-> DummyEntity -secondSubresource-> DummyEntity ...
         * DummyEntity -secondSubresource-> DummyEntity !!!-subresource-> DummyEntity ...
         */
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyEntity'));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['subresource', 'secondSubresource']));

        $subresourceWithMaxDepthMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false, 2));
        $secondSubresourceWithMaxDepthMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false, 1));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->shouldBeCalled()->willReturn($subresourceWithMaxDepthMetadata);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'secondSubresource')->shouldBeCalled()->willReturn($secondSubresourceWithMaxDepthMetadata);

        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresources');
        $pathSegmentNameGeneratorProphecy->getSegmentName('secondSubresource', false)->shouldBeCalled()->willReturn('second_subresources');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $pathSegmentNameGeneratorProphecy->reveal(),
            $identifiersExtractorProphecy->reveal()
        );

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresources.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_second_subresource_get_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                    ['subresource', DummyEntity::class, false, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_subresource_second_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresources/second_subresources.{_format}',
                'operation_name' => 'subresource_second_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_get_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_second_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/second_subresources.{_format}',
                'operation_name' => 'second_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    public function testCreateWithEnd()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummyEntity'));
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyEntity'));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['subresource']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['id']));

        $subresourceMetadataCollection = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, true));
        $identifierSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false))->withIdentifier(true);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->shouldBeCalled()->willReturn($subresourceMetadataCollection);
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'id')->shouldBeCalled()->willReturn($identifierSubresourceMetadata);

        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', true)->shouldBeCalled()->willReturn('subresource');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory(
                $resourceMetadataFactoryProphecy->reveal(),
                $propertyNameCollectionFactoryProphecy->reveal(),
                $propertyMetadataFactoryProphecy->reveal(),
                $pathSegmentNameGeneratorProphecy->reveal(),
                $identifiersExtractorProphecy->reveal()
        );

        $result = $subresourceOperationFactory->create(DummyEntity::class);
        $this->assertEquals([
            'api_dummy_entities_subresources_get_subresource' => [
                'property' => 'subresource',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_subresources_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresources_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresources_item_get_subresource' => [
                'property' => 'id',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                    ['subresource', RelatedDummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_subresources_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/{subresource}.{_format}',
                'operation_name' => 'subresources_item_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $result);
    }

    public function testCreateWithEndButNoCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummyEntity'));
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyEntity'));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['subresource']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['id']));

        $subresourceMetadataCollection = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, false));
        $identifierSubresourceMetadata = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false))->withIdentifier(true);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->shouldBeCalled()->willReturn($subresourceMetadataCollection);
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'id')->shouldBeCalled()->willReturn($identifierSubresourceMetadata);

        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresource');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory(
                $resourceMetadataFactoryProphecy->reveal(),
                $propertyNameCollectionFactoryProphecy->reveal(),
                $propertyMetadataFactoryProphecy->reveal(),
                $pathSegmentNameGeneratorProphecy->reveal(),
                $identifiersExtractorProphecy->reveal()
        );

        $result = $subresourceOperationFactory->create(DummyEntity::class);
        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $result);
    }

    public function testCreateWithRootResourcePrefix()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummyEntity'));
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyEntity', null, null, null, null, ['route_prefix' => 'root_resource_prefix']));

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
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresource');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $pathSegmentNameGeneratorProphecy->reveal(),
            $identifiersExtractorProphecy->reveal()
        );

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/root_resource_prefix/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    public function testCreateSelfReferencingSubresourcesWithSubresources()
    {
        /**
         * DummyEntity -otherSubresource-> RelatedDummyEntity
         * DummyEntity -subresource (maxDepth=1) -> DummyEntity -otherSubresource-> RelatedDummyEntity.
         */
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('dummyEntity'));
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummyEntity'));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['subresource', 'otherSubresource']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->shouldBeCalled()->willReturn(new PropertyNameCollection([]));

        $subresource = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(DummyEntity::class, false, 1));
        $otherSubresourceSubresource = (new PropertyMetadata())->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, false));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->shouldBeCalled()->willReturn($subresource);
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'otherSubresource')->shouldBeCalled()->willReturn($otherSubresourceSubresource);

        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('dummyEntity')->shouldBeCalled()->willReturn('dummy_entities');
        $pathSegmentNameGeneratorProphecy->getSegmentName('subresource', false)->shouldBeCalled()->willReturn('subresources');
        $pathSegmentNameGeneratorProphecy->getSegmentName('otherSubresource', false)->shouldBeCalled()->willReturn('other_subresources');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $subresourceOperationFactory = new SubresourceOperationFactory(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $pathSegmentNameGeneratorProphecy->reveal(),
            $identifiersExtractorProphecy->reveal()
        );

        $this->assertEquals([
            'api_dummy_entities_subresource_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/subresources.{_format}',
                'operation_name' => 'subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_other_subresource_get_subresource' => [
                'property' => 'otherSubresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['relatedDummyEntity', 'dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, ['id']],
                ],
                'identified_by' => ['id'],
                'route_name' => 'api_dummy_entities_other_subresource_get_subresource',
                'path' => '/dummy_entities/{id}/other_subresources.{_format}',
                'operation_name' => 'other_subresource_get_subresource',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }
}
