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

namespace ApiPlatform\Doctrine\Orm\Tests\Util;

use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\RelatedDummy;
use ApiPlatform\Doctrine\Orm\Util\QueryChecker;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class QueryCheckerTest extends TestCase
{
    use ProphecyTrait;

    public function testHasHavingClauseWithHavingClause(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getDQLPart('having')->willReturn(['having' => 'toto']);
        $this->assertTrue(QueryChecker::hasHavingClause($queryBuilder->reveal()));
    }

    public function testHasHavingClauseWithoutHavingClause(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getDQLPart('having')->willReturn(null);
        $this->assertFalse(QueryChecker::hasHavingClause($queryBuilder->reveal()));
    }

    public function testHasMaxResult(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getMaxResults()->willReturn(10);
        $this->assertTrue(QueryChecker::hasMaxResults($queryBuilder->reveal()));
    }

    public function testHasMaxResultWithNoMaxResult(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getMaxResults()->willReturn(null);
        $this->assertFalse(QueryChecker::hasMaxResults($queryBuilder->reveal()));
    }

    public function testHasRootEntityWithCompositeIdentifier(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn([Dummy::class]);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $classMetadata = new ClassMetadata(Dummy::class);
        $classMetadata->isIdentifierComposite = true;
        $classMetadata->containsForeignIdentifier = false;
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->willReturn($classMetadata);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->willReturn($objectManager->reveal());
        $this->assertTrue(QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasRootEntityWithNoCompositeIdentifier(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn([Dummy::class]);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $classMetadata = new ClassMetadata(Dummy::class);
        $classMetadata->isIdentifierComposite = false;
        $classMetadata->containsForeignIdentifier = true;
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->willReturn($classMetadata);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->willReturn($objectManager->reveal());
        $this->assertFalse(QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasRootEntityWithForeignKeyIdentifier(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn([Dummy::class]);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $classMetadata = new ClassMetadata(Dummy::class);
        $classMetadata->setIdentifier(['id', 'name']);
        $classMetadata->isIdentifierComposite = false;
        $classMetadata->containsForeignIdentifier = true;
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->willReturn($classMetadata);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->willReturn($objectManager->reveal());
        $this->assertTrue(QueryChecker::hasRootEntityWithForeignKeyIdentifier($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasRootEntityWithNoForeignKeyIdentifier(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn([Dummy::class]);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $classMetadata = new ClassMetadata(Dummy::class);
        $classMetadata->isIdentifierComposite = true;
        $classMetadata->containsForeignIdentifier = false;
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->willReturn($classMetadata);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->willReturn($objectManager->reveal());
        $this->assertFalse(QueryChecker::hasRootEntityWithForeignKeyIdentifier($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasOrderByOnFetchJoinedToManyAssociationWithoutJoin(): void
    {
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('d');
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->orderBy('d.name', 'ASC');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $this->assertFalse(QueryChecker::hasOrderByOnFetchJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    public function testHasOrderByOnFetchJoinedToManyAssociationWithoutOrderBy(): void
    {
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('d', 'a_1');
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $this->assertFalse(QueryChecker::hasOrderByOnFetchJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    public function testHasOrderByOnFetchJoinedToManyAssociationNotFetchJoined(): void
    {
        $dummyMetadata = new ClassMetadata(Dummy::class);
        $dummyMetadata->mapManyToMany([
            'fieldName' => 'relatedDummies',
            'targetEntity' => RelatedDummy::class,
        ]);
        $dummyMetadata->mapManyToOne([
            'fieldName' => 'relatedDummy',
            'targetEntity' => RelatedDummy::class,
        ]);

        $relatedDummyMetadata = new ClassMetadata(RelatedDummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getClassMetadata(Dummy::class)->willReturn($dummyMetadata);
        $entityManagerProphecy->getClassMetadata(RelatedDummy::class)->willReturn($relatedDummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('d', 'a_2');
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');
        $queryBuilder->leftJoin('d.relatedDummy', 'a_2');
        $queryBuilder->orderBy('a_1.name', 'ASC');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $this->assertTrue(QueryChecker::hasOrderByOnFetchJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    public function testHasOrderByOnFetchJoinedToManyAssociationWithJoinByAssociation(): void
    {
        $dummyMetadata = new ClassMetadata(Dummy::class);
        $dummyMetadata->mapManyToMany([
            'fieldName' => 'relatedDummies',
            'targetEntity' => RelatedDummy::class,
        ]);

        $relatedDummyMetadata = new ClassMetadata(RelatedDummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getClassMetadata(Dummy::class)->willReturn($dummyMetadata);
        $entityManagerProphecy->getClassMetadata(RelatedDummy::class)->willReturn($relatedDummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('d', 'a_1');
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');
        $queryBuilder->orderBy('a_1.name', 'ASC');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $this->assertTrue(QueryChecker::hasOrderByOnFetchJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    public function testHasJoinedToManyAssociationWithoutJoin(): void
    {
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('d');
        $queryBuilder->from(Dummy::class, 'd');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $this->assertFalse(QueryChecker::hasJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    public function testHasJoinedToManyAssociationWithJoinByAssociation(): void
    {
        $dummyMetadata = new ClassMetadata(Dummy::class);
        $dummyMetadata->mapManyToMany([
            'fieldName' => 'relatedDummies',
            'targetEntity' => RelatedDummy::class,
        ]);

        $relatedDummyMetadata = new ClassMetadata(RelatedDummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getClassMetadata(Dummy::class)->willReturn($dummyMetadata);
        $entityManagerProphecy->getClassMetadata(RelatedDummy::class)->willReturn($relatedDummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('d');
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $this->assertTrue(QueryChecker::hasJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }
}
