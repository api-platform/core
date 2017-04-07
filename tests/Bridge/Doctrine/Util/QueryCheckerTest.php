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

namespace ApiPlatform\Core\Tests\Doctrine\Util;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;

class QueryCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function testHasHavingClauseWithHavingClause()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getDQLPart('having')->willReturn(['having' => 'toto']);
        $this->assertTrue(QueryChecker::hasHavingClause($queryBuilder->reveal()));
    }

    public function testHasHavingClauseWithEmptyHavingClause()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getDQLPart('having')->willReturn([]);
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
        $classMetadata->containsForeignIdentifier = true;
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
        $classMetadata->containsForeignIdentifier = false;
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
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata('Dummy')->willReturn($classMetadata);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass('Dummy')->willReturn($objectManager->reveal());
        $this->assertFalse(QueryChecker::hasRootEntityWithForeignKeyIdentifier($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasOrderByOnToManyJoinWithoutJoin()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn(['Dummy']);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $queryBuilder->getDQLPart('join')->willReturn([]);
        $queryBuilder->getDQLPart('orderBy')->willReturn(['name' => new OrderBy('name', 'asc')]);
        $classMetadata = $this->prophesize(ClassMetadata::class);
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata('Dummy')->willReturn($classMetadata->reveal());
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass('Dummy')->willReturn($objectManager->reveal());

        $this->assertFalse(QueryChecker::hasOrderByOnToManyJoin($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasOrderByOnToManyJoinWithoutOrderBy()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn(['Dummy']);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $queryBuilder->getDQLPart('join')->willReturn(['a_1' => new Join('INNER_JOIN', 'relatedDummy', 'a_1', null, 'a_1.name = r.name')]);
        $queryBuilder->getDQLPart('orderBy')->willReturn([]);
        $classMetadata = $this->prophesize(ClassMetadata::class);
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata('Dummy')->willReturn($classMetadata->reveal());
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass('Dummy')->willReturn($objectManager->reveal());

        $this->assertFalse(QueryChecker::hasOrderByOnToManyJoin($queryBuilder->reveal(), $managerRegistry->reveal()));
    }

    public function testHasOrderByOnToManyJoinWithoutJoinAndWithoutOrderBy()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn(['Dummy']);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $queryBuilder->getDQLPart('join')->willReturn([]);
        $queryBuilder->getDQLPart('orderBy')->willReturn([]);
        $classMetadata = $this->prophesize(ClassMetadata::class);
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata('Dummy')->willReturn($classMetadata->reveal());
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass('Dummy')->willReturn($objectManager->reveal());

        $this->assertFalse(QueryChecker::hasOrderByOnToManyJoin($queryBuilder->reveal(), $managerRegistry->reveal()));
    }
}
