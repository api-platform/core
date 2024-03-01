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

namespace ApiPlatform\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Exception\PropertyNotFoundException;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AbstractDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConcreteDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddableDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PropertyCollectionIriOnly;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PropertyCollectionIriOnlyRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UnknownDummy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
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
    use ProphecyTrait;

    public function testApplyToCollection(): void
    {
        $context = ['groups' => ['foo']];
        $callContext = ['serializer_groups' => ['foo']];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name', 'notindatabase', 'notreadable', 'embeddedDummy']);
        $relatedEmbedableCollection = new PropertyNameCollection(['name']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(EmbeddableDummy::class)->willReturn($relatedEmbedableCollection)->shouldBeCalled();

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

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', $callContext)->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', $callContext)->willReturn($namePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'embeddedDummy', $callContext)->willReturn($embeddedPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notindatabase', $callContext)->willReturn($notInDatabasePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notreadable', $callContext)->willReturn($notReadablePropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property && 'embeddedDummy' !== $property) {
                $relatedClassMetadataProphecy->hasField($property)->willReturn('notindatabase' !== $property)->shouldBeCalled();
            }
        }
        $relatedClassMetadataProphecy->hasField('embeddedDummy.name')->willReturn(true)->shouldBeCalled();

        $relatedClassMetadataProphecy->embeddedClasses = ['embeddedDummy' => ['class' => EmbeddableDummy::class]];

        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a2')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a1.{id,name,embeddedDummy.name}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relatedDummy2_a2.{id,name,embeddedDummy.name}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([]);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, null, $context);
    }

    public function testApplyToItem(): void
    {
        $context = ['groups' => ['foo']];
        $callContext = ['serializer_groups' => ['foo']];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name', 'embeddedDummy', 'notindatabase', 'notreadable', 'relation']);
        $relatedEmbedableCollection = new PropertyNameCollection(['name']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(EmbeddableDummy::class)->willReturn($relatedEmbedableCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willReturn(new PropertyNameCollection(['id']))->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(ThirdLevel::class)->willReturn(new PropertyNameCollection(['id']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy3', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy4', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy5', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'singleInheritanceRelation', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();

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

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', $callContext)->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', $callContext)->willReturn($namePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'embeddedDummy', $callContext)->willReturn($embeddedDummyPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notindatabase', $callContext)->willReturn($notInDatabasePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notreadable', $callContext)->willReturn($notReadablePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'relation', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'thirdLevel', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(UnknownDummy::class, 'id', $callContext)->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(ThirdLevel::class, 'id', $callContext)->willReturn($idPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => UnknownDummy::class],
            'relatedDummy3' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinTable' => ['joinColumns' => [['nullable' => false]]], 'targetEntity' => UnknownDummy::class],
            'relatedDummy4' => ['fetch' => ClassMetadata::FETCH_EAGER, 'targetEntity' => UnknownDummy::class],
            'relatedDummy5' => ['fetch' => ClassMetadata::FETCH_LAZY, 'targetEntity' => UnknownDummy::class],
            'singleInheritanceRelation' => ['fetch' => ClassMetadata::FETCH_EAGER, 'targetEntity' => AbstractDummy::class],
            'relatedDummies' => ['fetch' => ClassMetadata::FETCH_EAGER, 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property && 'embeddedDummy' !== $property) {
                $relatedClassMetadataProphecy->hasField($property)->willReturn('notindatabase' !== $property)->shouldBeCalled();
            }
        }
        $relatedClassMetadataProphecy->hasField('embeddedDummy.name')->willReturn(true)->shouldBeCalled();

        $relatedClassMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => UnknownDummy::class],
            'thirdLevel' => ['fetch' => ClassMetadata::FETCH_EAGER, 'targetEntity' => ThirdLevel::class, 'sourceEntity' => RelatedDummy::class, 'inversedBy' => 'relatedDummies', 'type' => ClassMetadata::TO_ONE],
        ];

        $relatedClassMetadataProphecy->embeddedClasses = ['embeddedDummy' => ['class' => EmbeddableDummy::class]];

        $singleInheritanceClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $singleInheritanceClassMetadataProphecy->subClasses = [ConcreteDummy::class];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->associationMappings = [];

        $thirdLevelMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $thirdLevelMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(AbstractDummy::class)->shouldBeCalled()->willReturn($singleInheritanceClassMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($unknownClassMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(ThirdLevel::class)->shouldBeCalled()->willReturn($thirdLevelMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);
        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('relatedDummy_a1.relation', 'relation_a2')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('relatedDummy_a1.thirdLevel', 'thirdLevel_a3')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a4')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('o.relatedDummy3', 'relatedDummy3_a5')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('o.relatedDummy4', 'relatedDummy4_a6')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('o.singleInheritanceRelation', 'singleInheritanceRelation_a7')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('o.relatedDummies', 'relatedDummies_a8')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('relatedDummies_a8.relation', 'relation_a9')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('relatedDummies_a8.thirdLevel', 'thirdLevel_a10')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a1.{id,name,embeddedDummy.name}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial thirdLevel_a3.{id}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relation_a2.{id}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relatedDummy2_a4.{id}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relatedDummy3_a5.{id}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relatedDummy4_a6.{id}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('singleInheritanceRelation_a7')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relatedDummies_a8.{id,name,embeddedDummy.name}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relation_a9.{id}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial thirdLevel_a10.{id}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([]);
        $queryBuilderProphecy->getDQLPart('select')->willReturn([]);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true);

        $orderExtensionTest->applyToItem($queryBuilder, new QueryNameGenerator(), Dummy::class, [], null, $context);
    }

    public function testCreateItemWithOperation(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', ['serializer_groups' => ['foo']])->shouldBeCalled()->willReturn(new ApiProperty());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'foo' => ['fetch' => 1],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true);
        $eagerExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, [], new Get(name: 'item_operation'), ['groups' => ['foo']]);
    }

    public function testCreateCollectionWithOperation(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', ['serializer_groups' => ['foo']])->shouldBeCalled()->willReturn(new ApiProperty());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'foo' => ['fetch' => 1],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, new GetCollection(name: 'collection_operation'), ['groups' => ['foo']]);
    }

    public function testDenormalizeItemWithCorrectResourceClass(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [];

        // Dummy is the correct class for the denormalization context serialization groups, and we're fetching RelatedDummy
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldNotBeCalled();
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true);
        $eagerExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), RelatedDummy::class, ['id' => 1], new Get(name: 'get', normalizationContext: ['groups' => ['foo']]), ['resource_class' => Dummy::class]);
    }

    public function testDenormalizeItemWithExistingGroups(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [];

        // groups exist from the context, we don't need to compute them again
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldNotBeCalled();
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true);
        $eagerExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), RelatedDummy::class, ['id' => 1], new Get(name: 'item_operation', normalizationContext: ['groups' => ['foo']]), [AbstractNormalizer::GROUPS => 'some_groups']);
    }

    public function testMaxJoinsReached(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The total number of joined relations has exceeded the specified maximum. Raise the limit if necessary with the "api_platform.eager_loading.max_joins" configuration key (https://api-platform.com/docs/core/performance/#eager-loading), or limit the maximum serialization depth using the "enable_max_depth" option of the Symfony serializer (https://symfony.com/doc/current/components/serializer.html#handling-serialization-depth).');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['dummy']);
        $dummyNameCollection = new PropertyNameCollection(['relatedDummy']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->willReturn($dummyNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $relatedPropertyMetadata = new ApiProperty();
        $relatedPropertyMetadata = $relatedPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'dummy', ['serializer_groups' => ['foo']])->willReturn($relatedPropertyMetadata)->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];
        $classMetadataProphecy->hasField('relatedDummy')->willReturn(true);

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [
            'dummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => Dummy::class],
        ];
        $relatedClassMetadataProphecy->hasField('dummy')->willReturn(true);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->innerJoin(Argument::type('string'), Argument::type('string'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect(Argument::type('string'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([]);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, null, ['groups' => ['foo']]);
    }

    public function testMaxDepth(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['dummy']);
        $dummyNameCollection = new PropertyNameCollection(['relatedDummy']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->willReturn($dummyNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $relatedPropertyMetadata = new ApiProperty();
        $relatedPropertyMetadata = $relatedPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'dummy', ['serializer_groups' => ['foo'], 'normalization_groups' => ['foo']])->willReturn($relatedPropertyMetadata)->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];
        $classMetadataProphecy->hasField('relatedDummy')->willReturn(true);

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [
            'dummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => Dummy::class],
        ];
        $relatedClassMetadataProphecy->hasField('dummy')->willReturn(true);

        $dummyClassMetadataInterfaceProphecy = $this->prophesize(ClassMetadataInterface::class);
        $relatedClassMetadataInterfaceProphecy = $this->prophesize(ClassMetadataInterface::class);
        $classMetadataFactoryProphecy = $this->prophesize(ClassMetadataFactoryInterface::class);

        $dummyAttributeMetadata = new AttributeMetadata('dummy');
        $dummyAttributeMetadata->setMaxDepth(2);

        $relatedAttributeMetadata = new AttributeMetadata('relatedDummy');
        $relatedAttributeMetadata->setMaxDepth(4);

        $dummyClassMetadataInterfaceProphecy->getAttributesMetadata()->willReturn(['relatedDummy' => $dummyAttributeMetadata]);
        $relatedClassMetadataInterfaceProphecy->getAttributesMetadata()->willReturn(['dummy' => $relatedAttributeMetadata]);

        $classMetadataFactoryProphecy->getMetadataFor(RelatedDummy::class)->willReturn($relatedClassMetadataInterfaceProphecy->reveal());
        $classMetadataFactoryProphecy->getMetadataFor(Dummy::class)->willReturn($dummyClassMetadataInterfaceProphecy->reveal());

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->innerJoin(Argument::type('string'), Argument::type('string'))->shouldBeCalledTimes(2)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect(Argument::type('string'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([]);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true, $classMetadataFactoryProphecy->reveal());
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: ['enable_max_depth' => 'true', 'groups' => ['foo']]));
    }

    public function testForceEager(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willReturn(new PropertyNameCollection(['id']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $idPropertyMetadata = new ApiProperty();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);

        $propertyMetadataFactoryProphecy->create(UnknownDummy::class, 'id', ['serializer_groups' => ['foobar'], 'normalization_groups' => 'foobar'])->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', ['serializer_groups' => ['foobar'], 'normalization_groups' => 'foobar'])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($unknownClassMetadataProphecy->reveal());

        $queryBuilderProphecy->innerJoin('o.relation', 'relation_a1')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relation_a1.{id}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([]);

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, true, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, [], new Get(normalizationContext: [AbstractNormalizer::GROUPS => 'foobar']));
    }

    public function testExtraLazy(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        // $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willReturn(new PropertyNameCollection(['id']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', ['serializer_groups' => ['foobar'], 'normalization_groups' => 'foobar'])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_EXTRA_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, true, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, [], new Get(normalizationContext: [AbstractNormalizer::GROUPS => 'foobar']));
    }

    public function testResourceClassNotFoundException(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willThrow(new ResourceClassNotFoundException());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, true, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, [], new Get(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    public function testPropertyNotFoundException(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willThrow(new PropertyNotFoundException());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, true, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, [], new Get(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    public function testResourceClassNotFoundExceptionPropertyNameCollection(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willThrow(new ResourceClassNotFoundException());

        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadata::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);
        $queryBuilderProphecy->innerJoin('o.relation', 'relation_a1')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([]);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, true, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, [], new Get(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    public function testAttributes(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $idPropertyMetadata = new ApiProperty();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new ApiProperty();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($namePropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummies' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property) {
                $relatedClassMetadataProphecy->hasField($property)->willReturn(true)->shouldBeCalled();
            }
        }

        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummies', 'relatedDummies_a1')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a2')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relatedDummies_a1.{id,name}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a2.{id,name}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([]);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    public function testNotInAttributes(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => 3, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo', AbstractNormalizer::ATTRIBUTES => ['relatedDummy']]));
    }

    public function testOnlyOneRelationNotInAttributes(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $idPropertyMetadata = new ApiProperty();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new ApiProperty();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($namePropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummies' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property) {
                $relatedClassMetadataProphecy->hasField($property)->willReturn(true)->shouldBeCalled();
            }
        }

        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a1.{id,name}')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([]);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo', AbstractNormalizer::ATTRIBUTES => ['relatedDummy' => ['id', 'name']]]));
    }

    public function testApplyToCollectionNoPartial(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [];
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a2')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('relatedDummy_a1')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('relatedDummy2_a2')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([]);
        $queryBuilderProphecy->getDQLPart('select')->willReturn([]);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    public function testApplyToCollectionWithANonReadableButFetchEagerProperty(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withFetchEager(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadable(false);

        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadable(false);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [];
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a2')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('relatedDummy_a1')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addSelect('relatedDummy2_a2')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([]);
        $queryBuilderProphecy->getDQLPart('select')->willReturn([]);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    /**
     * @dataProvider provideExistingJoinCases
     */
    public function testApplyToCollectionWithExistingJoin(string $joinType): void
    {
        $context = ['groups' => ['foo']];
        $callContext = ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);
        $queryBuilderProphecy->getDQLPart('join')->willReturn([
            'o' => [
                new Join($joinType, 'o.relatedDummy', 'existing_join_alias'),
            ],
        ]);
        $queryBuilderProphecy->getDQLPart('select')->willReturn([]);
        $queryBuilderProphecy->addSelect('existing_join_alias')->shouldBeCalledTimes(1)->willReturn($queryBuilderProphecy);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30, false);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']), $context);
    }

    public static function provideExistingJoinCases(): iterable
    {
        yield [Join::LEFT_JOIN];
        yield [Join::INNER_JOIN];
    }

    public function testApplyToCollectionWithAReadableButNotFetchEagerProperty(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withFetchEager(false);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadable(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', ['serializer_groups' => ['foo'], 'normalization_groups' => 'foo'])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldNotBecalled();

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldNotBeCalled();
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a2')->shouldNotBeCalled();
        $queryBuilderProphecy->addSelect('relatedDummy_a1')->shouldNotBeCalled();
        $queryBuilderProphecy->addSelect('relatedDummy2_a2')->shouldNotBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'foo']));
    }

    public function testAvoidFetchCollectionOnIriOnlyProperty(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new ApiProperty();
        $relationPropertyMetadata = $relationPropertyMetadata->withFetchEager(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadable(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withUriTemplate('/property-collection-relations');

        $propertyMetadataFactoryProphecy->create(PropertyCollectionIriOnly::class, 'propertyCollectionIriOnlyRelation', ['serializer_groups' => ['read'], 'normalization_groups' => 'read'])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'propertyCollectionIriOnlyRelation' => ['fetch' => ClassMetadata::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => PropertyCollectionIriOnlyRelation::class],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(PropertyCollectionIriOnly::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(PropertyCollectionIriOnlyRelation::class)->shouldNotBecalled();

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.propertyCollectionIriOnlyRelation', 'propertyCollectionIriOnlyRelation_a1')->shouldNotBeCalled();
        $queryBuilderProphecy->addSelect('propertyCollectionIriOnlyRelation_a1')->shouldNotBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), PropertyCollectionIriOnly::class, new GetCollection(normalizationContext: [AbstractNormalizer::GROUPS => 'read']));
    }
}
