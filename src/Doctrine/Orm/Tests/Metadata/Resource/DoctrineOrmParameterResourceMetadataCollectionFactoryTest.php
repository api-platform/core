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

namespace ApiPlatform\Doctrine\Orm\Tests\Metadata\Resource;

use ApiPlatform\Doctrine\Orm\Metadata\Resource\DoctrineOrmParameterResourceMetadataCollectionFactory;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\RelatedDummy;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\ThirdLevel;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class DoctrineOrmParameterResourceMetadataCollectionFactoryTest extends TestCase
{
    private ManagerRegistry $managerRegistry;
    private EntityManagerInterface&Stub $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);

        $this->managerRegistry = $this->createStub(ManagerRegistry::class);
        $this->managerRegistry->method('getManagerForClass')->willReturn($this->entityManager);
    }

    public function testParameterWithoutNestedInfoPassedThrough(): void
    {
        $parameter = new QueryParameter(property: 'name', key: 'name');

        $collection = $this->createCollectionWithParameter($parameter, Dummy::class);
        $factory = $this->createFactory($collection);

        $result = $factory->create(Dummy::class);
        $resultParameter = $this->getFirstParameter($result);

        $this->assertArrayNotHasKey('nested_properties_info', $resultParameter->getExtraProperties());
    }

    public function testParameterWithNestedInfoGetsOrmLeafMetadata(): void
    {
        $leafMetadata = new ClassMetadata(RelatedDummy::class);
        $leafMetadata->mapField(['fieldName' => 'name', 'type' => 'string', 'columnName' => 'name']);
        $leafMetadata->mapManyToOne(['fieldName' => 'thirdLevel', 'targetEntity' => ThirdLevel::class, 'joinColumns' => [['name' => 'third_level_id', 'referencedColumnName' => 'id']]]);

        $targetMetadata = new ClassMetadata(ThirdLevel::class);
        $targetMetadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'columnName' => 'id', 'id' => true]);
        $targetMetadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        $this->entityManager->method('getClassMetadata')->willReturnMap([
            [RelatedDummy::class, $leafMetadata],
            [ThirdLevel::class, $targetMetadata],
        ]);

        $parameter = new QueryParameter(
            property: 'relatedDummy.thirdLevel',
            key: 'relatedDummy.thirdLevel',
            extraProperties: [
                'nested_properties_info' => ['relatedDummy.thirdLevel' => [
                    'relation_segments' => ['relatedDummy'],
                    'relation_classes' => [Dummy::class],
                    'leaf_property' => 'thirdLevel',
                    'leaf_class' => RelatedDummy::class,
                ]],
            ],
        );

        $collection = $this->createCollectionWithParameter($parameter, Dummy::class);
        $factory = $this->createFactory($collection);

        $result = $factory->create(Dummy::class);
        $resultParameter = $this->getFirstParameter($result);

        $nestedInfo = $resultParameter->getExtraProperties()['nested_properties_info']['relatedDummy.thirdLevel'];
        $this->assertArrayHasKey('orm_leaf_metadata', $nestedInfo);

        $ormLeaf = $nestedInfo['orm_leaf_metadata'];
        $this->assertFalse($ormLeaf['has_field']);
        $this->assertTrue($ormLeaf['has_association']);
        $this->assertFalse($ormLeaf['is_collection_valued']);
        $this->assertFalse($ormLeaf['is_inverse_side']);
        $this->assertSame(ThirdLevel::class, $ormLeaf['association_target_class']);
        $this->assertSame('id', $ormLeaf['identifier_field']);
        $this->assertSame('integer', $ormLeaf['identifier_type']);
    }

    public function testCollectionValuedAssociationDetected(): void
    {
        $leafMetadata = new ClassMetadata(Dummy::class);
        $leafMetadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'columnName' => 'id', 'id' => true]);
        $leafMetadata->mapManyToMany(['fieldName' => 'relatedDummies', 'targetEntity' => RelatedDummy::class, 'joinTable' => ['name' => 'dummy_related', 'joinColumns' => [['name' => 'dummy_id', 'referencedColumnName' => 'id']], 'inverseJoinColumns' => [['name' => 'related_id', 'referencedColumnName' => 'id']]]]);

        $targetMetadata = new ClassMetadata(RelatedDummy::class);
        $targetMetadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'columnName' => 'id', 'id' => true]);
        $targetMetadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        $this->entityManager->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $leafMetadata],
            [RelatedDummy::class, $targetMetadata],
        ]);

        $parameter = new QueryParameter(
            property: 'parent.relatedDummies',
            key: 'parent.relatedDummies',
            extraProperties: [
                'nested_properties_info' => ['parent.relatedDummies' => [
                    'relation_segments' => ['parent'],
                    'relation_classes' => [RelatedDummy::class],
                    'leaf_property' => 'relatedDummies',
                    'leaf_class' => Dummy::class,
                ]],
            ],
        );

        $collection = $this->createCollectionWithParameter($parameter, RelatedDummy::class);
        $factory = $this->createFactory($collection);

        $result = $factory->create(RelatedDummy::class);
        $resultParameter = $this->getFirstParameter($result);

        $ormLeaf = $resultParameter->getExtraProperties()['nested_properties_info']['parent.relatedDummies']['orm_leaf_metadata'];
        $this->assertTrue($ormLeaf['has_association']);
        $this->assertTrue($ormLeaf['is_collection_valued']);
        $this->assertSame(RelatedDummy::class, $ormLeaf['association_target_class']);
    }

    public function testFieldPropertyProducesFieldMetadata(): void
    {
        $leafMetadata = new ClassMetadata(RelatedDummy::class);
        $leafMetadata->mapField(['fieldName' => 'name', 'type' => 'string', 'columnName' => 'name']);

        $this->entityManager->method('getClassMetadata')->willReturnMap([
            [RelatedDummy::class, $leafMetadata],
        ]);

        $parameter = new QueryParameter(
            property: 'relatedDummy.name',
            key: 'relatedDummy.name',
            extraProperties: [
                'nested_properties_info' => ['relatedDummy.name' => [
                    'relation_segments' => ['relatedDummy'],
                    'relation_classes' => [Dummy::class],
                    'leaf_property' => 'name',
                    'leaf_class' => RelatedDummy::class,
                ]],
            ],
        );

        $collection = $this->createCollectionWithParameter($parameter, Dummy::class);
        $factory = $this->createFactory($collection);

        $result = $factory->create(Dummy::class);
        $resultParameter = $this->getFirstParameter($result);

        $ormLeaf = $resultParameter->getExtraProperties()['nested_properties_info']['relatedDummy.name']['orm_leaf_metadata'];
        $this->assertTrue($ormLeaf['has_field']);
        $this->assertFalse($ormLeaf['has_association']);
        $this->assertFalse($ormLeaf['is_collection_valued']);
        $this->assertNull($ormLeaf['association_target_class']);
    }

    public function testAlreadyEnrichedParameterNotProcessedAgain(): void
    {
        $parameter = new QueryParameter(
            property: 'relatedDummy.thirdLevel',
            key: 'relatedDummy.thirdLevel',
            extraProperties: [
                'nested_properties_info' => ['relatedDummy.thirdLevel' => [
                    'relation_segments' => ['relatedDummy'],
                    'relation_classes' => [Dummy::class],
                    'leaf_property' => 'thirdLevel',
                    'leaf_class' => RelatedDummy::class,
                    'orm_leaf_metadata' => [
                        'has_field' => false,
                        'has_association' => true,
                        'is_collection_valued' => false,
                        'is_inverse_side' => false,
                        'association_target_class' => ThirdLevel::class,
                        'identifier_field' => 'id',
                        'identifier_type' => 'integer',
                    ],
                ]],
            ],
        );

        $collection = $this->createCollectionWithParameter($parameter, Dummy::class);
        $factory = $this->createFactory($collection);

        $result = $factory->create(Dummy::class);
        $resultParameter = $this->getFirstParameter($result);

        $nestedInfo = $resultParameter->getExtraProperties()['nested_properties_info']['relatedDummy.thirdLevel'];
        $this->assertSame('integer', $nestedInfo['orm_leaf_metadata']['identifier_type']);
    }

    public function testNonOrmManagedClassSkippedGracefully(): void
    {
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn(null);

        $parameter = new QueryParameter(
            property: 'relatedDummy.name',
            key: 'relatedDummy.name',
            extraProperties: [
                'nested_properties_info' => ['relatedDummy.name' => [
                    'relation_segments' => ['relatedDummy'],
                    'relation_classes' => [Dummy::class],
                    'leaf_property' => 'name',
                    'leaf_class' => RelatedDummy::class,
                ]],
            ],
        );

        $collection = $this->createCollectionWithParameter($parameter, Dummy::class);

        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn($collection);

        $factory = new DoctrineOrmParameterResourceMetadataCollectionFactory($managerRegistry, $decorated);
        $result = $factory->create(Dummy::class);
        $resultParameter = $this->getFirstParameter($result);

        $nestedInfo = $resultParameter->getExtraProperties()['nested_properties_info']['relatedDummy.name'];
        $this->assertArrayNotHasKey('orm_leaf_metadata', $nestedInfo);
    }

    private function createFactory(ResourceMetadataCollection $collection): DoctrineOrmParameterResourceMetadataCollectionFactory
    {
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn($collection);

        return new DoctrineOrmParameterResourceMetadataCollectionFactory($this->managerRegistry, $decorated);
    }

    private function createCollectionWithParameter(QueryParameter $parameter, string $resourceClass): ResourceMetadataCollection
    {
        $parameters = new Parameters();
        $parameters->add($parameter->getKey(), $parameter);

        $operation = (new GetCollection())->withClass($resourceClass)->withStateOptions(new Options(entityClass: $resourceClass))->withParameters($parameters);
        $operations = new Operations();
        $operations->add('_api_'.$resourceClass.'_GetCollection', $operation);

        $resource = (new ApiResource())->withOperations($operations)->withClass($resourceClass);

        return new ResourceMetadataCollection($resourceClass, [$resource]);
    }

    private function getFirstParameter(ResourceMetadataCollection $collection): QueryParameter
    {
        foreach ($collection as $resource) {
            foreach ($resource->getOperations() as $operation) {
                foreach ($operation->getParameters() as $parameter) {
                    if (!$parameter instanceof QueryParameter) {
                        continue;
                    }

                    return $parameter;
                }
            }
        }

        $this->fail('No parameter found in collection');
    }
}
