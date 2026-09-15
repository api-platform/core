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

namespace ApiPlatform\Doctrine\Orm\Tests\Extension;

use ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\AbstractDummy;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\ConcreteDummy;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\EmbeddableDummy;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\PropertyCollectionIriOnly;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\PropertyCollectionIriOnlyRelation;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\RelatedDummy;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\ThirdLevel;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\UnknownDummy;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class EagerLoadingExtensionTest extends TestCase
{
    public function testApplyToCollection(): void
    {
        $context = ['groups' => ['foo']];
        $callContext = ['serializer_groups' => ['foo']];

        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name', 'notindatabase', 'notreadable', 'embeddedDummy']);
        $relatedEmbedableCollection = new PropertyNameCollection(['name']);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyNameCollectionFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [RelatedDummy::class, $relatedNameCollection],
            [EmbeddableDummy::class, $relatedEmbedableCollection],
        ]);

        $idPropertyMetadata = new ApiProperty();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new ApiProperty();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);
        $embeddedPropertyMetadata = new ApiProperty();
        $embeddedPropertyMetadata = $embeddedPropertyMetadata->withReadable(true);
        $notInDatabasePropertyMetadata = new ApiProperty();
        $notInDatabasePropertyMetadata = $notInDatabasePropertyMetadata->withReadable(true);
        $notReadablePropertyMetadata = new ApiProperty();
        $notReadablePropertyMetadata = $notReadablePropertyMetadata->withReadable(false);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummy', $callContext, $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy2', $callContext, $relationPropertyMetadata],
            [RelatedDummy::class, 'id', $callContext, $idPropertyMetadata],
            [RelatedDummy::class, 'name', $callContext, $namePropertyMetadata],
            [RelatedDummy::class, 'embeddedDummy', $callContext, $embeddedPropertyMetadata],
            [RelatedDummy::class, 'notindatabase', $callContext, $notInDatabasePropertyMetadata],
            [RelatedDummy::class, 'notreadable', $callContext, $notReadablePropertyMetadata],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: true)], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: false)], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);

        $hasFieldMap = [];
        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property && 'embeddedDummy' !== $property) {
                $hasFieldMap[] = [$property, 'notindatabase' !== $property];
            }
        }
        $hasFieldMap[] = ['embeddedDummy.name', true];
        $relatedClassMetadataMock->expects($this->atLeastOnce())->method('hasField')->willReturnMap($hasFieldMap);

        $relatedClassMetadataMock->embeddedClasses = ['embeddedDummy' => ['class' => EmbeddableDummy::class]];

        $relatedClassMetadataMock->associationMappings = [];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
        ]);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->expects($this->exactly(1))->method('leftJoin')->with('o.relatedDummy', 'relatedDummy_a1')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->exactly(1))->method('innerJoin')->with('o.relatedDummy2', 'relatedDummy2_a2')->willReturn($queryBuilderMock);
        $actualSelects = [];
        $queryBuilderMock->expects($this->exactly(2))->method('addSelect')
            ->willReturnCallback(static function (string $select) use (&$actualSelects, $queryBuilderMock): QueryBuilder {
                $actualSelects[] = $select;

                return $queryBuilderMock;
            });
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
        ]);

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, null, $context);

        $this->assertSame([
            'partial relatedDummy_a1.{id,name,embeddedDummy.name}',
            'partial relatedDummy2_a2.{id,name,embeddedDummy.name}',
        ], $actualSelects);
    }

    public function testApplyToItem(): void
    {
        $context = ['groups' => ['foo']];
        $callContext = ['serializer_groups' => ['foo']];

        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name', 'embeddedDummy', 'notindatabase', 'notreadable', 'relation']);
        $relatedEmbedableCollection = new PropertyNameCollection(['name']);

        $propertyNameCollectionFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [RelatedDummy::class, $relatedNameCollection],
            [EmbeddableDummy::class, $relatedEmbedableCollection],
            [UnknownDummy::class, new PropertyNameCollection(['id'])],
            [ThirdLevel::class, new PropertyNameCollection(['id'])],
        ]);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $idPropertyMetadata = new ApiProperty();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new ApiProperty();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);
        $embeddedDummyPropertyMetadata = new ApiProperty();
        $embeddedDummyPropertyMetadata = $embeddedDummyPropertyMetadata->withReadable(true);
        $notInDatabasePropertyMetadata = new ApiProperty();
        $notInDatabasePropertyMetadata = $notInDatabasePropertyMetadata->withReadable(true);
        $notReadablePropertyMetadata = new ApiProperty();
        $notReadablePropertyMetadata = $notReadablePropertyMetadata->withReadable(false);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummy', $callContext, $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy2', $callContext, $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy3', $callContext, $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy4', $callContext, $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy5', $callContext, $relationPropertyMetadata],
            [Dummy::class, 'singleInheritanceRelation', $callContext, $relationPropertyMetadata],
            [Dummy::class, 'relatedDummies', $callContext, $relationPropertyMetadata],
            [RelatedDummy::class, 'id', $callContext, $idPropertyMetadata],
            [RelatedDummy::class, 'name', $callContext, $namePropertyMetadata],
            [RelatedDummy::class, 'embeddedDummy', $callContext, $embeddedDummyPropertyMetadata],
            [RelatedDummy::class, 'notindatabase', $callContext, $notInDatabasePropertyMetadata],
            [RelatedDummy::class, 'notreadable', $callContext, $notReadablePropertyMetadata],
            [RelatedDummy::class, 'relation', $callContext, $relationPropertyMetadata],
            [RelatedDummy::class, 'thirdLevel', $callContext, $relationPropertyMetadata],
            [UnknownDummy::class, 'id', $callContext, $idPropertyMetadata],
            [ThirdLevel::class, 'id', $callContext, $idPropertyMetadata],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: true)], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: false)], 'targetEntity' => UnknownDummy::class],
            'relatedDummy3' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinTable' => ['joinColumns' => [new JoinColumn(nullable: false)]], 'targetEntity' => UnknownDummy::class],
            'relatedDummy4' => ['fetch' => ClassMetadata::FETCH_EAGER, 'targetEntity' => UnknownDummy::class],
            'relatedDummy5' => ['fetch' => ClassMetadata::FETCH_LAZY, 'targetEntity' => UnknownDummy::class],
            'singleInheritanceRelation' => ['fetch' => ClassMetadata::FETCH_EAGER, 'targetEntity' => AbstractDummy::class],
            'relatedDummies' => ['fetch' => ClassMetadata::FETCH_EAGER, 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);

        $hasFieldMap = [];
        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property && 'embeddedDummy' !== $property) {
                $hasFieldMap[] = [$property, 'notindatabase' !== $property];
            }
        }
        $hasFieldMap[] = ['embeddedDummy.name', true];
        $relatedClassMetadataMock->expects($this->atLeastOnce())->method('hasField')->willReturnMap($hasFieldMap);

        $relatedClassMetadataMock->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: false)], 'targetEntity' => UnknownDummy::class],
            'thirdLevel' => ['fetch' => ClassMetadata::FETCH_EAGER, 'targetEntity' => ThirdLevel::class, 'sourceEntity' => RelatedDummy::class, 'inversedBy' => 'relatedDummies', 'type' => ClassMetadata::TO_ONE],
        ];

        $relatedClassMetadataMock->embeddedClasses = ['embeddedDummy' => ['class' => EmbeddableDummy::class]];

        $singleInheritanceClassMetadataMock = $this->createMock(ClassMetadata::class);
        $singleInheritanceClassMetadataMock->subClasses = [ConcreteDummy::class];

        $unknownClassMetadataMock = $this->createMock(ClassMetadata::class);
        $unknownClassMetadataMock->associationMappings = [];

        $thirdLevelMetadataMock = $this->createMock(ClassMetadata::class);
        $thirdLevelMetadataMock->associationMappings = [];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
            [AbstractDummy::class, $singleInheritanceClassMetadataMock],
            [UnknownDummy::class, $unknownClassMetadataMock],
            [ThirdLevel::class, $thirdLevelMetadataMock],
        ]);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);
        $queryBuilderMock->expects($this->exactly(1))->method('innerJoin')->with('o.relatedDummy2', 'relatedDummy2_a4')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->exactly(9))->method('leftJoin')->willReturnMap([
            ['o.relatedDummy', 'relatedDummy_a1', $queryBuilderMock],
            ['relatedDummy_a1.relation', 'relation_a2', $queryBuilderMock],
            ['relatedDummy_a1.thirdLevel', 'thirdLevel_a3', $queryBuilderMock],
            ['o.relatedDummy3', 'relatedDummy3_a5', $queryBuilderMock],
            ['o.relatedDummy4', 'relatedDummy4_a6', $queryBuilderMock],
            ['o.singleInheritanceRelation', 'singleInheritanceRelation_a7', $queryBuilderMock],
            ['o.relatedDummies', 'relatedDummies_a8', $queryBuilderMock],
            ['relatedDummies_a8.relation', 'relation_a9', $queryBuilderMock],
            ['relatedDummies_a8.thirdLevel', 'thirdLevel_a10', $queryBuilderMock],
        ]);
        $actualSelects = [];
        $queryBuilderMock->expects($this->exactly(10))->method('addSelect')
            ->willReturnCallback(static function (string $select) use (&$actualSelects, $queryBuilderMock): QueryBuilder {
                $actualSelects[] = $select;

                return $queryBuilderMock;
            });
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
            ['select', []],
        ]);

        $queryBuilder = $queryBuilderMock;
        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true);

        $orderExtensionTest->applyToItem($queryBuilder, new QueryNameGenerator(), Dummy::class, [], null, $context);

        // addSelect calls are grouped by target entity (not emitted in traversal order), so the exact
        // interleaving between independently-joined branches is an aggregation implementation detail,
        // not something this test asserts on; compare as sets instead of an ordered sequence.
        $this->assertEqualsCanonicalizing([
            'partial relatedDummy_a1.{id,name,embeddedDummy.name}',
            'partial thirdLevel_a3.{id}',
            'partial relation_a2.{id}',
            'partial relatedDummy2_a4.{id}',
            'partial relatedDummy3_a5.{id}',
            'partial relatedDummy4_a6.{id}',
            'singleInheritanceRelation_a7',
            'partial relatedDummies_a8.{id,name,embeddedDummy.name}',
            'partial relation_a9.{id}',
            'partial thirdLevel_a10.{id}',
        ], $actualSelects);
    }

    public function testCreateItemWithOperation(): void
    {
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->with(Dummy::class, 'foo', ['serializer_groups' => ['foo']])->willReturn(new ApiProperty());

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'foo' => ['fetch' => 1],
        ];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(Dummy::class)->willReturn($classMetadataMock);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->with('select')->willReturn([]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true);
        $eagerExtensionTest->applyToItem($queryBuilderMock, new QueryNameGenerator(), Dummy::class, [], new Get(name: 'item_operation'), ['groups' => ['foo']]);
    }

    public function testCreateCollectionWithOperation(): void
    {
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->with(Dummy::class, 'foo', ['serializer_groups' => ['foo']])->willReturn(new ApiProperty());

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'foo' => ['fetch' => 1],
        ];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(Dummy::class)->willReturn($classMetadataMock);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->with('select')->willReturn([]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilderMock, new QueryNameGenerator(), Dummy::class, new GetCollection(name: 'collection_operation'), ['groups' => ['foo']]);
    }

    public function testDenormalizeItemWithCorrectResourceClass(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [];

        // Dummy is the correct class for the denormalization context serialization groups, and we're fetching RelatedDummy
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(RelatedDummy::class)->willReturn($classMetadataMock);
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->with('select')->willReturn([]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true);
        $eagerExtensionTest->applyToItem($queryBuilderMock, new QueryNameGenerator(), RelatedDummy::class, ['id' => 1], new Get(name: 'get', normalizationContext: ['groups' => ['foo']]), ['resource_class' => Dummy::class]);
    }

    public function testDenormalizeItemWithExistingGroups(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [];

        // groups exist from the context, we don't need to compute them again
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(RelatedDummy::class)->willReturn($classMetadataMock);
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->with('select')->willReturn([]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true);
        $eagerExtensionTest->applyToItem($queryBuilderMock, new QueryNameGenerator(), RelatedDummy::class, ['id' => 1], new Get(name: 'item_operation', normalizationContext: ['groups' => ['foo']]), [AbstractNormalizer::GROUPS => 'some_groups']);
    }

    public function testContextSwitch(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryMock->expects($this->atLeastOnce())->method('create')->with(RelatedDummy::class)->willReturn($relatedNameCollection);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);

        $idPropertyMetadata = new ApiProperty();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new ApiProperty();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);

        // When called via `relatedDummies` without context switch

        // When called via `relatedDummy` with context switch
        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummies', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [RelatedDummy::class, 'id', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $idPropertyMetadata],
            [RelatedDummy::class, 'name', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $namePropertyMetadata],
            [RelatedDummy::class, 'id', ['normalization_groups' => ['bar'], 'denormalization_groups' => ['foo']], $idPropertyMetadata],
            [RelatedDummy::class, 'name', ['normalization_groups' => ['bar'], 'denormalization_groups' => ['foo']], $namePropertyMetadata],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummies' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);

        $hasFieldMap = [];
        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property && 'embeddedDummy' !== $property) {
                $hasFieldMap[] = [$property, true];
            }
        }
        $relatedClassMetadataMock->expects($this->atLeastOnce())->method('hasField')->willReturnMap($hasFieldMap);

        $dummyClassMetadataInterfaceMock = $this->createMock(ClassMetadataInterface::class);
        $relatedClassMetadataInterfaceMock = $this->createMock(ClassMetadataInterface::class);
        $classMetadataFactoryMock = $this->createMock(ClassMetadataFactoryInterface::class);

        $relatedDummyAttributeMetadata = new AttributeMetadata('relatedDummy');
        $relatedDummyAttributeMetadata->setNormalizationContextForGroups(['groups' => ['bar']], ['foo']);

        $dummyClassMetadataInterfaceMock->method('getAttributesMetadata')->willReturn(['relatedDummy' => $relatedDummyAttributeMetadata]);
        $relatedClassMetadataInterfaceMock->method('getAttributesMetadata')->willReturn([]);

        $classMetadataFactoryMock->method('getMetadataFor')->willReturnMap([
            [RelatedDummy::class, $relatedClassMetadataInterfaceMock],
            [Dummy::class, $dummyClassMetadataInterfaceMock],
        ]);

        $relatedClassMetadataMock->associationMappings = [];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
        ]);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->expects($this->exactly(2))->method('leftJoin')->willReturnMap([
            ['o.relatedDummies', 'relatedDummies_a1', $queryBuilderMock],
            ['o.relatedDummy', 'relatedDummy_a2', $queryBuilderMock],
        ]);
        $actualSelects = [];
        $queryBuilderMock->expects($this->exactly(2))->method('addSelect')
            ->willReturnCallback(static function (string $select) use (&$actualSelects, $queryBuilderMock): QueryBuilder {
                $actualSelects[] = $select;

                return $queryBuilderMock;
            });
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
        ]);

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true, $classMetadataFactoryMock);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));

        $this->assertSame([
            'partial relatedDummies_a1.{id,name}',
            'partial relatedDummy_a2.{id,name}',
        ], $actualSelects);
    }

    public function testSameEntityWithDifferentPartialProperties(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryMock->expects($this->atLeastOnce())->method('create')->with(RelatedDummy::class)->willReturn($relatedNameCollection);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);

        $idPropertyMetadata = (new ApiProperty())->withIdentifier(true);
        $namePropertyMetadataGroupA = (new ApiProperty())->withReadable(true);
        // the property Name IS NOT readable in group B
        $namePropertyMetadataGroupB = (new ApiProperty())->withReadable(false);

        // When called via `relatedDummy1`

        // When called via `relatedDummy2`
        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummy1', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy2', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [RelatedDummy::class, 'id', ['normalization_groups' => ['A'], 'denormalization_groups' => ['foo']], $idPropertyMetadata],
            [RelatedDummy::class, 'name', ['normalization_groups' => ['A'], 'denormalization_groups' => ['foo']], $namePropertyMetadataGroupA],
            [RelatedDummy::class, 'id', ['normalization_groups' => ['B'], 'denormalization_groups' => ['foo']], $idPropertyMetadata],
            [RelatedDummy::class, 'name', ['normalization_groups' => ['B'], 'denormalization_groups' => ['foo']], $namePropertyMetadataGroupB],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummy1' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);

        $hasFieldMap = [];
        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property && 'embeddedDummy' !== $property) {
                $hasFieldMap[] = [$property, true];
            }
        }
        $relatedClassMetadataMock->expects($this->atLeastOnce())->method('hasField')->willReturnMap($hasFieldMap);

        $dummyClassMetadataInterfaceMock = $this->createMock(ClassMetadataInterface::class);
        $relatedClassMetadataInterfaceMock = $this->createMock(ClassMetadataInterface::class);
        $classMetadataFactoryMock = $this->createMock(ClassMetadataFactoryInterface::class);

        $relatedDummy1AttributeMetadata = new AttributeMetadata('relatedDummy');
        $relatedDummy1AttributeMetadata->setNormalizationContextForGroups(['groups' => ['A']], ['foo']);

        $relatedDummy2AttributeMetadata = new AttributeMetadata('relatedDummy');
        $relatedDummy2AttributeMetadata->setNormalizationContextForGroups(['groups' => ['B']], ['foo']);

        $dummyClassMetadataInterfaceMock->method('getAttributesMetadata')->willReturn(['relatedDummy1' => $relatedDummy1AttributeMetadata, 'relatedDummy2' => $relatedDummy2AttributeMetadata]);
        $relatedClassMetadataInterfaceMock->method('getAttributesMetadata')->willReturn([]);

        $classMetadataFactoryMock->method('getMetadataFor')->willReturnMap([
            [RelatedDummy::class, $relatedClassMetadataInterfaceMock],
            [Dummy::class, $dummyClassMetadataInterfaceMock],
        ]);

        $relatedClassMetadataMock->associationMappings = [];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
        ]);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->expects($this->exactly(2))->method('leftJoin')->willReturnMap([
            ['o.relatedDummy1', 'relatedDummy1_a1', $queryBuilderMock],
            ['o.relatedDummy2', 'relatedDummy2_a2', $queryBuilderMock],
        ]);
        $actualSelects = [];
        $queryBuilderMock->expects($this->exactly(2))->method('addSelect')
            ->willReturnCallback(static function (string $select) use (&$actualSelects, $queryBuilderMock): QueryBuilder {
                $actualSelects[] = $select;

                return $queryBuilderMock;
            });
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
        ]);

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true, $classMetadataFactoryMock);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));

        // here is the purpose of this test: name is not readable in group B, BUT it is part of the partial selection because it is readable in group A
        $this->assertSame([
            'partial relatedDummy1_a1.{id,name}',
            'partial relatedDummy2_a2.{id,name}',
        ], $actualSelects);
    }

    public function testMaxJoinsReached(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The total number of joined relations has exceeded the specified maximum. Raise the limit if necessary with the "api_platform.eager_loading.max_joins" configuration key (https://api-platform.com/docs/core/performance/#eager-loading), or limit the maximum serialization depth using the "enable_max_depth" option of the Symfony serializer (https://symfony.com/doc/current/components/serializer.html#handling-serialization-depth).');

        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['dummy']);
        $dummyNameCollection = new PropertyNameCollection(['relatedDummy']);

        $propertyNameCollectionFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [RelatedDummy::class, $relatedNameCollection],
            [Dummy::class, $dummyNameCollection],
        ]);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $relatedPropertyMetadata = new ApiProperty();
        $relatedPropertyMetadata = $relatedPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']], $relationPropertyMetadata],
            [RelatedDummy::class, 'dummy', ['serializer_groups' => ['foo']], $relatedPropertyMetadata],
        ]);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: false)], 'targetEntity' => RelatedDummy::class],
        ];
        $classMetadataMock->method('hasField')->with('relatedDummy')->willReturn(true);

        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);
        $relatedClassMetadataMock->associationMappings = [
            'dummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: false)], 'targetEntity' => Dummy::class],
        ];
        $relatedClassMetadataMock->method('hasField')->with('dummy')->willReturn(true);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->method('innerJoin')->with($this->isString(), $this->isString())->willReturn($queryBuilderMock);
        $queryBuilderMock->method('addSelect')->with($this->isString())->willReturn($queryBuilderMock);
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
        ]);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilderMock, new QueryNameGenerator(), Dummy::class, null, ['groups' => ['foo']]);
    }

    public function testMaxDepth(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['dummy']);
        $dummyNameCollection = new PropertyNameCollection(['relatedDummy']);

        $propertyNameCollectionFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [RelatedDummy::class, $relatedNameCollection],
            [Dummy::class, $dummyNameCollection],
        ]);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $relatedPropertyMetadata = new ApiProperty();
        $relatedPropertyMetadata = $relatedPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => ['foo']], $relationPropertyMetadata],
            [RelatedDummy::class, 'dummy', ['serializer_groups' => ['foo'], 'normalization_groups' => ['foo']], $relatedPropertyMetadata],
        ]);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: false)], 'targetEntity' => RelatedDummy::class],
        ];
        $classMetadataMock->method('hasField')->with('relatedDummy')->willReturn(true);

        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);
        $relatedClassMetadataMock->associationMappings = [
            'dummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: false)], 'targetEntity' => Dummy::class],
        ];
        $relatedClassMetadataMock->method('hasField')->with('dummy')->willReturn(true);

        $dummyClassMetadataInterfaceMock = $this->createMock(ClassMetadataInterface::class);
        $relatedClassMetadataInterfaceMock = $this->createMock(ClassMetadataInterface::class);
        $classMetadataFactoryMock = $this->createMock(ClassMetadataFactoryInterface::class);

        $dummyAttributeMetadata = new AttributeMetadata('dummy');
        $dummyAttributeMetadata->setMaxDepth(2);

        $relatedAttributeMetadata = new AttributeMetadata('relatedDummy');
        $relatedAttributeMetadata->setMaxDepth(4);

        $dummyClassMetadataInterfaceMock->method('getAttributesMetadata')->willReturn(['relatedDummy' => $dummyAttributeMetadata]);
        $relatedClassMetadataInterfaceMock->method('getAttributesMetadata')->willReturn(['dummy' => $relatedAttributeMetadata]);

        $classMetadataFactoryMock->method('getMetadataFor')->willReturnMap([
            [RelatedDummy::class, $relatedClassMetadataInterfaceMock],
            [Dummy::class, $dummyClassMetadataInterfaceMock],
        ]);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->expects($this->exactly(2))->method('innerJoin')->with($this->isString(), $this->isString())->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->atLeastOnce())->method('addSelect')->with($this->isString())->willReturn($queryBuilderMock);
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
        ]);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true, $classMetadataFactoryMock);
        $eagerExtensionTest->applyToCollection($queryBuilderMock, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: ['enable_max_depth' => 'true', 'groups' => ['foo']]));
    }

    public function testForceEager(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryMock->expects($this->atLeastOnce())->method('create')->with(UnknownDummy::class)->willReturn(new PropertyNameCollection(['id']));

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $idPropertyMetadata = new ApiProperty();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [UnknownDummy::class, 'id', ['serializer_groups' => ['foobar'], 'normalization_groups' => 'foobar'], $idPropertyMetadata],
            [Dummy::class, 'relation', ['serializer_groups' => ['foobar'], 'normalization_groups' => 'foobar'], $relationPropertyMetadata],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [new JoinColumn(nullable: false)]],
        ];

        $unknownClassMetadataMock = $this->createMock(ClassMetadata::class);
        $unknownClassMetadataMock->associationMappings = [];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [UnknownDummy::class, $unknownClassMetadataMock],
        ]);

        $queryBuilderMock->expects($this->exactly(1))->method('innerJoin')->with('o.relation', 'relation_a1')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->exactly(1))->method('addSelect')->with('partial relation_a1.{id}')->willReturn($queryBuilderMock);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['join', []],
            ['select', []],
        ]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, true, true);
        $orderExtensionTest->applyToItem($queryBuilderMock, new QueryNameGenerator(), Dummy::class, [], new Get(normalizationContext: [AbstractNormalizer::GROUPS => 'foobar']));
    }

    public function testExtraLazy(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->with(Dummy::class, 'relation', ['serializer_groups' => ['foobar'], 'normalization_groups' => 'foobar'])->willReturn($relationPropertyMetadata);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_EXTRA_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];

        $unknownClassMetadataMock = $this->createMock(ClassMetadata::class);
        $unknownClassMetadataMock->associationMappings = [];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(Dummy::class)->willReturn($classMetadataMock);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->with('select')->willReturn([]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, true, true);
        $orderExtensionTest->applyToItem($queryBuilderMock, new QueryNameGenerator(), Dummy::class, [], new Get(normalizationContext: [AbstractNormalizer::GROUPS => 'foobar']));
    }

    public function testResourceClassNotFoundException(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryMock->method('create')->with(Dummy::class, 'relation', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willThrowException(new ResourceClassNotFoundException());

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(Dummy::class)->willReturn($classMetadataMock);
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->with('select')->willReturn([]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, true, true);
        $orderExtensionTest->applyToItem($queryBuilderMock, new QueryNameGenerator(), Dummy::class, [], new Get(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    public function testPropertyNotFoundException(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryMock->method('create')->with(Dummy::class, 'relation', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willThrowException(new PropertyNotFoundException());

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(Dummy::class)->willReturn($classMetadataMock);
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->with('select')->willReturn([]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, true, true);
        $orderExtensionTest->applyToItem($queryBuilderMock, new QueryNameGenerator(), Dummy::class, [], new Get(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    public function testResourceClassNotFoundExceptionPropertyNameCollection(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryMock->method('create')->with(UnknownDummy::class)->willThrowException(new ResourceClassNotFoundException());

        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);
        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryMock->method('create')->with(Dummy::class, 'relation', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [new JoinColumn(nullable: false)]],
        ];
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->exactly(2))->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [UnknownDummy::class, $classMetadataMock],
        ]);
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);
        $queryBuilderMock->expects($this->exactly(1))->method('innerJoin')->with('o.relation', 'relation_a1')->willReturn($queryBuilderMock);
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
        ]);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, true, true);
        $orderExtensionTest->applyToItem($queryBuilderMock, new QueryNameGenerator(), Dummy::class, [], new Get(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    public function testAttributes(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryMock->expects($this->atLeastOnce())->method('create')->with(RelatedDummy::class)->willReturn($relatedNameCollection);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);

        $idPropertyMetadata = new ApiProperty();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new ApiProperty();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummies', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [RelatedDummy::class, 'id', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $idPropertyMetadata],
            [RelatedDummy::class, 'name', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $namePropertyMetadata],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummies' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);

        $hasFieldMap = [];
        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property) {
                $hasFieldMap[] = [$property, true];
            }
        }
        $relatedClassMetadataMock->expects($this->atLeastOnce())->method('hasField')->willReturnMap($hasFieldMap);

        $relatedClassMetadataMock->associationMappings = [];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
        ]);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->expects($this->exactly(2))->method('leftJoin')->willReturnMap([
            ['o.relatedDummies', 'relatedDummies_a1', $queryBuilderMock],
            ['o.relatedDummy', 'relatedDummy_a2', $queryBuilderMock],
        ]);
        $actualSelects = [];
        $queryBuilderMock->expects($this->exactly(2))->method('addSelect')
            ->willReturnCallback(static function (string $select) use (&$actualSelects, $queryBuilderMock): QueryBuilder {
                $actualSelects[] = $select;

                return $queryBuilderMock;
            });
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
        ]);

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));

        $this->assertSame([
            'partial relatedDummies_a1.{id,name}',
            'partial relatedDummy_a2.{id,name}',
        ], $actualSelects);
    }

    public function testNotInAttributes(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->with(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummy' => ['fetch' => 3, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);
        $relatedClassMetadataMock->associationMappings = [];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(Dummy::class)->willReturn($classMetadataMock);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->with('select')->willReturn([]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo', AbstractNormalizer::ATTRIBUTES => ['relatedDummy']]));
    }

    public function testOnlyOneRelationNotInAttributes(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryMock->expects($this->atLeastOnce())->method('create')->with(RelatedDummy::class)->willReturn($relatedNameCollection);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);

        $idPropertyMetadata = new ApiProperty();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new ApiProperty();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummies', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [RelatedDummy::class, 'id', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $idPropertyMetadata],
            [RelatedDummy::class, 'name', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $namePropertyMetadata],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummies' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);

        $hasFieldMap = [];
        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property) {
                $hasFieldMap[] = [$property, true];
            }
        }
        $relatedClassMetadataMock->expects($this->atLeastOnce())->method('hasField')->willReturnMap($hasFieldMap);

        $relatedClassMetadataMock->associationMappings = [];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->exactly(2))->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
        ]);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->expects($this->exactly(1))->method('leftJoin')->with('o.relatedDummy', 'relatedDummy_a1')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->exactly(1))->method('addSelect')->with('partial relatedDummy_a1.{id,name}')->willReturn($queryBuilderMock);
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
        ]);

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo', AbstractNormalizer::ATTRIBUTES => ['relatedDummy' => ['id', 'name']]]));
    }

    public function testApplyToCollectionNoPartial(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy2', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: true)], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: false)], 'targetEntity' => RelatedDummy::class],
        ];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);
        $relatedClassMetadataMock->associationMappings = [];
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
        ]);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->expects($this->exactly(1))->method('leftJoin')->with('o.relatedDummy', 'relatedDummy_a1')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->exactly(1))->method('innerJoin')->with('o.relatedDummy2', 'relatedDummy2_a2')->willReturn($queryBuilderMock);
        $actualSelects = [];
        $queryBuilderMock->expects($this->exactly(2))->method('addSelect')
            ->willReturnCallback(static function (string $select) use (&$actualSelects, $queryBuilderMock): QueryBuilder {
                $actualSelects[] = $select;

                return $queryBuilderMock;
            });
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
            ['select', []],
        ]);

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));

        $this->assertSame(['relatedDummy_a1', 'relatedDummy2_a2'], $actualSelects);
    }

    public function testApplyToCollectionWithANonReadableButFetchEagerProperty(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withFetchEager(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadable(false);

        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadable(false);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy2', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: true)], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [new JoinColumn(nullable: false)], 'targetEntity' => RelatedDummy::class],
        ];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);
        $relatedClassMetadataMock->associationMappings = [];
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
        ]);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->expects($this->exactly(1))->method('leftJoin')->with('o.relatedDummy', 'relatedDummy_a1')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->exactly(1))->method('innerJoin')->with('o.relatedDummy2', 'relatedDummy2_a2')->willReturn($queryBuilderMock);
        $actualSelects = [];
        $queryBuilderMock->expects($this->exactly(2))->method('addSelect')
            ->willReturnCallback(static function (string $select) use (&$actualSelects, $queryBuilderMock): QueryBuilder {
                $actualSelects[] = $select;

                return $queryBuilderMock;
            });
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', []],
            ['select', []],
        ]);

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));

        $this->assertSame(['relatedDummy_a1', 'relatedDummy2_a2'], $actualSelects);
    }

    #[DataProvider('provideExistingJoinCases')]
    public function testApplyToCollectionWithExistingJoin(string $joinType): void
    {
        $context = ['groups' => ['foo']];
        $callContext = ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'];

        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->with(Dummy::class, 'relatedDummy', $callContext)->willReturn($relationPropertyMetadata);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataMock = $this->createMock(ClassMetadata::class);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->exactly(2))->method('getClassMetadata')->willReturnMap([
            [Dummy::class, $classMetadataMock],
            [RelatedDummy::class, $relatedClassMetadataMock],
        ]);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);
        $queryBuilderMock->method('getDQLPart')->willReturnMap([
            ['select', []],
            ['join', ['o' => [new Join($joinType, 'o.relatedDummy', 'existing_join_alias')]]],
            ['select', []],
        ]);
        $queryBuilderMock->expects($this->exactly(1))->method('addSelect')->with('existing_join_alias')->willReturn($queryBuilderMock);

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30, false);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']), $context);
    }

    public static function provideExistingJoinCases(): iterable
    {
        yield [Join::LEFT_JOIN];
        yield [Join::INNER_JOIN];
    }

    public function testApplyToCollectionWithAReadableButNotFetchEagerProperty(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withFetchEager(false);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadable(true);

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->willReturnMap([
            [Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
            [Dummy::class, 'relatedDummy2', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'], $relationPropertyMetadata],
        ]);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(Dummy::class)->willReturn($classMetadataMock);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->with('select')->willReturn([]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->expects($this->never())->method('leftJoin')->with('o.relatedDummy', 'relatedDummy_a1');
        $queryBuilderMock->expects($this->never())->method('innerJoin')->with('o.relatedDummy2', 'relatedDummy2_a2');
        $queryBuilderMock->expects($this->never())->method('addSelect');

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    public function testAvoidFetchCollectionOnIriOnlyProperty(): void
    {
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withFetchEager(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadable(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withUriTemplate('/property-collection-relations');

        $propertyMetadataFactoryMock->expects($this->atLeastOnce())->method('create')->with(PropertyCollectionIriOnly::class, 'propertyCollectionIriOnlyRelation', ['serializer_groups' => ['read'], 'normalization_groups' => 'read'])->willReturn($relationPropertyMetadata);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->associationMappings = [
            'propertyCollectionIriOnlyRelation' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => PropertyCollectionIriOnlyRelation::class],
        ];

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->atLeastOnce())->method('getClassMetadata')->with(PropertyCollectionIriOnly::class)->willReturn($classMetadataMock);

        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('getDQLPart')->with('select')->willReturn([]);
        $queryBuilderMock->method('getEntityManager')->willReturn($emMock);

        $queryBuilderMock->expects($this->never())->method('leftJoin')->with('o.propertyCollectionIriOnlyRelation', 'propertyCollectionIriOnlyRelation_a1');
        $queryBuilderMock->expects($this->never())->method('addSelect')->with('propertyCollectionIriOnlyRelation_a1');

        $queryBuilder = $queryBuilderMock;
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryMock, $propertyMetadataFactoryMock, 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), PropertyCollectionIriOnly::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'read']));
    }
}
