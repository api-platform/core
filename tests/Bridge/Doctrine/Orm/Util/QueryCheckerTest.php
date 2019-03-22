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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Util;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryCheckerTest extends TestCase
{
    public function testHasHavingClauseWithHavingClause()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getDQLPart('having')->willReturn(['having' => 'toto']);
        $this->assertTrue(QueryChecker::hasHavingClause($queryBuilder->reveal()));
    }

    public function testHasHavingClauseWithoutHavingClause()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getDQLPart('having')->willReturn(null);
        $this->assertFalse(QueryChecker::hasHavingClause($queryBuilder->reveal()));
    }

    public function testHasMaxResult()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getMaxResults()->willReturn(10);
        $this->assertTrue(QueryChecker::hasMaxResults($queryBuilder->reveal()));
    }

    public function testHasMaxResultWithNoMaxResult()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getMaxResults()->willReturn(null);
        $this->assertFalse(QueryChecker::hasMaxResults($queryBuilder->reveal()));
    }

    public function testHasRootEntityWithCompositeIdentifier()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn(['Dummy']);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $classMetadata = new ClassMetadata('Dummy');
        $classMetadata->isIdentifierComposite = true;
        $classMetadata->containsForeignIdentifier = false;
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata('Dummy')->willReturn($classMetadata);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass('Dummy')->willReturn($objectManager->reveal());
        $this->assertTrue(QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasRootEntityWithNoCompositeIdentifier()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn(['Dummy']);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $classMetadata = new ClassMetadata('Dummy');
        $classMetadata->isIdentifierComposite = false;
        $classMetadata->containsForeignIdentifier = true;
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata('Dummy')->willReturn($classMetadata);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass('Dummy')->willReturn($objectManager->reveal());
        $this->assertFalse(QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasRootEntityWithForeignKeyIdentifier()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn(['Dummy']);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $classMetadata = new ClassMetadata('Dummy');
        $classMetadata->setIdentifier(['id', 'name']);
        $classMetadata->isIdentifierComposite = false;
        $classMetadata->containsForeignIdentifier = true;
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata('Dummy')->willReturn($classMetadata);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass('Dummy')->willReturn($objectManager->reveal());
        $this->assertTrue(QueryChecker::hasRootEntityWithForeignKeyIdentifier($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasRootEntityWithNoForeignKeyIdentifier()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn(['Dummy']);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $classMetadata = new ClassMetadata('Dummy');
        $classMetadata->isIdentifierComposite = true;
        $classMetadata->containsForeignIdentifier = false;
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata('Dummy')->willReturn($classMetadata);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass('Dummy')->willReturn($objectManager->reveal());
        $this->assertFalse(QueryChecker::hasRootEntityWithForeignKeyIdentifier($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasOrderByOnFetchJoinedToManyAssociationWithoutJoin()
    {
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select(['d']);
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->orderBy('d.name', 'ASC');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $this->assertFalse(QueryChecker::hasOrderByOnFetchJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    public function testHasOrderByOnFetchJoinedToManyAssociationWithoutOrderBy()
    {
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select(['d', 'a_1']);
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $this->assertFalse(QueryChecker::hasOrderByOnFetchJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    public function testHasOrderByOnFetchJoinedToManyAssociationNotFetchJoined()
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
        $queryBuilder->select(['d', 'a_2']);
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');
        $queryBuilder->leftJoin('d.relatedDummy', 'a_2');
        $queryBuilder->orderBy('a_1.name', 'ASC');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $this->assertFalse(QueryChecker::hasOrderByOnFetchJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    public function testHasOrderByOnFetchJoinedToManyAssociationWithJoinByAssociation()
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
        $queryBuilder->select(['d', 'a_1']);
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');
        $queryBuilder->orderBy('a_1.name', 'ASC');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $this->assertTrue(QueryChecker::hasOrderByOnFetchJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker::hasOrderByOnToManyJoin()" is deprecated since 2.4 and will be removed in 3.0. Use "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker::hasOrderByOnFetchJoinedToManyAssociation()" instead.
     */
    public function testHasOrderByOnToManyJoinWithoutJoin()
    {
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select(['d']);
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->orderBy('d.name', 'ASC');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $this->assertFalse(QueryChecker::hasOrderByOnToManyJoin($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker::hasOrderByOnToManyJoin()" is deprecated since 2.4 and will be removed in 3.0. Use "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker::hasOrderByOnFetchJoinedToManyAssociation()" instead.
     */
    public function testHasOrderByOnToManyJoinWithoutOrderBy()
    {
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select(['d', 'a_1']);
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $this->assertFalse(QueryChecker::hasOrderByOnToManyJoin($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker::hasOrderByOnToManyJoin()" is deprecated since 2.4 and will be removed in 3.0. Use "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker::hasOrderByOnFetchJoinedToManyAssociation()" instead.
     */
    public function testHasOrderByOnToManyJoinNotFetchJoined()
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
        $queryBuilder->select(['d', 'a_2']);
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');
        $queryBuilder->leftJoin('d.relatedDummy', 'a_2');
        $queryBuilder->orderBy('a_1.name', 'ASC');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $this->assertFalse(QueryChecker::hasOrderByOnToManyJoin($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker::hasOrderByOnToManyJoin()" is deprecated since 2.4 and will be removed in 3.0. Use "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker::hasOrderByOnFetchJoinedToManyAssociation()" instead.
     */
    public function testHasOrderByOnToManyWithJoinByAssociation()
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
        $queryBuilder->select(['d', 'a_1']);
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');
        $queryBuilder->orderBy('a_1.name', 'ASC');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $this->assertTrue(QueryChecker::hasOrderByOnToManyJoin($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    public function testHasJoinedToManyAssociationWithoutJoin()
    {
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select(['d']);
        $queryBuilder->from(Dummy::class, 'd');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $this->assertFalse(QueryChecker::hasJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }

    public function testHasJoinedToManyAssociationWithJoinByAssociation()
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
        $queryBuilder->select(['d']);
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->leftJoin('d.relatedDummies', 'a_1');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $this->assertTrue(QueryChecker::hasJoinedToManyAssociation($queryBuilder, $managerRegistryProphecy->reveal()));
    }
}
