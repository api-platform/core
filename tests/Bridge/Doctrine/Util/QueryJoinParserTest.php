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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryJoinParser;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use phpmock\phpunit\PHPMock;

class QueryJoinParserTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    public function testGetClassMetadataFromJoinAlias()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn(['Dummy']);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $queryBuilder->getDQLPart('join')->willReturn(['a_1' => new Join('INNER_JOIN', 'relatedDummy', 'a_1', null, 'a_1.name = r.name')]);
        $classMetadata = $this->prophesize(ClassMetadata::class);
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata('Dummy')->willReturn($classMetadata->reveal());
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass('Dummy')->willReturn($objectManager->reveal());
        $metadata = QueryJoinParser::getClassMetadataFromJoinAlias('a_1', $queryBuilder->reveal(), $managerRegistry->reveal());
        $this->assertEquals($metadata, $classMetadata->reveal());
    }

    public function testGetJoinRelationshipWithJoin()
    {
        $join = new Join('INNER_JOIN', 'relatedDummy', 'a_1', null, 'a_1.name = r.name');
        $this->assertEquals('relatedDummy', QueryJoinParser::getJoinRelationship($join));
    }

    public function testGetJoinRelationshipWithReflection()
    {
        $methodExist = $this->getFunctionMock('ApiPlatform\Core\Bridge\Doctrine\Orm\Util', 'method_exists');
        $methodExist->expects($this->any())->with(Join::class, 'getJoin')->willReturn('false');
        $join = new Join('INNER_JOIN', 'relatedDummy', 'a_1', null, 'a_1.name = r.name');
        $this->assertEquals('relatedDummy', QueryJoinParser::getJoinRelationship($join));
    }

    public function testGetJoinAliasWithJoin()
    {
        $join = new Join('INNER_JOIN', 'relatedDummy', 'a_1', null, 'a_1.name = r.name');
        $this->assertEquals('a_1', QueryJoinParser::getJoinAlias($join));
    }

    public function testGetJoinAliasWithReflection()
    {
        $methodExist = $this->getFunctionMock('ApiPlatform\Core\Bridge\Doctrine\Orm\Util', 'method_exists');
        $methodExist->expects($this->any())->with(Join::class, 'getAlias')->willReturn('false');
        $join = new Join('INNER_JOIN', 'relatedDummy', 'a_1', null, 'a_1.name = r.name');
        $this->assertEquals('a_1', QueryJoinParser::getJoinAlias($join));
    }

    public function testGetOrderByPartsWithOrderBy()
    {
        $orderBy = new OrderBy('name', 'asc');
        $this->assertEquals(['name asc'], QueryJoinParser::getOrderByParts($orderBy));
    }

    public function testGetOrderByPartsWithReflection()
    {
        $methodExist = $this->getFunctionMock('ApiPlatform\Core\Bridge\Doctrine\Orm\Util', 'method_exists');
        $methodExist->expects($this->any())->with(OrderBy::class, 'getParts')->willReturn('false');
        $orderBy = new OrderBy('name', 'desc');
        $this->assertEquals(['name desc'], QueryJoinParser::getOrderByParts($orderBy));
    }
}
