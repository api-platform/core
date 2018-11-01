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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryJoinParser;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class QueryJoinParserTest extends TestCase
{
    use PHPMock;

    public function testGetClassMetadataFromJoinAlias()
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getRootEntities()->willReturn(['Dummy']);
        $queryBuilder->getRootAliases()->willReturn(['d']);
        $queryBuilder->getDQLPart('join')->willReturn(['a_1' => [new Join('INNER_JOIN', 'relatedDummy', 'a_1', null, 'a_1.name = r.name')]]);
        $classMetadata = $this->prophesize(ClassMetadata::class);
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata('Dummy')->willReturn($classMetadata->reveal());
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass('Dummy')->willReturn($objectManager->reveal());
        $metadata = QueryJoinParser::getClassMetadataFromJoinAlias('a_1', $queryBuilder->reveal(), $managerRegistry->reveal());
        $this->assertEquals($metadata, $classMetadata->reveal());
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryJoinParser::getJoinRelationship()" is deprecated since 2.3 and will be removed in 3.0. Use "Doctrine\ORM\Query\Expr\Join::getJoin()" directly instead.
     */
    public function testGetJoinRelationshipWithJoin()
    {
        $join = new Join('INNER_JOIN', 'a_1.relatedDummy', 'a_1', null, 'a_1.name = r.name');
        $this->assertEquals('a_1.relatedDummy', QueryJoinParser::getJoinRelationship($join));
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryJoinParser::getJoinRelationship()" is deprecated since 2.3 and will be removed in 3.0. Use "Doctrine\ORM\Query\Expr\Join::getJoin()" directly instead.
     */
    public function testGetJoinRelationshipWithClassJoin()
    {
        $join = new Join('INNER_JOIN', RelatedDummy::class, 'a_1', null, 'a_1.name = r.name');
        $this->assertEquals(RelatedDummy::class, QueryJoinParser::getJoinRelationship($join));
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryJoinParser::getJoinAlias()" is deprecated since 2.3 and will be removed in 3.0. Use "Doctrine\ORM\Query\Expr\Join::getAlias()" directly instead.
     */
    public function testGetJoinAliasWithJoin()
    {
        $join = new Join('INNER_JOIN', 'relatedDummy', 'a_1', null, 'a_1.name = r.name');
        $this->assertEquals('a_1', QueryJoinParser::getJoinAlias($join));
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryJoinParser::getJoinAlias()" is deprecated since 2.3 and will be removed in 3.0. Use "Doctrine\ORM\Query\Expr\Join::getAlias()" directly instead.
     */
    public function testGetJoinAliasWithClassJoin()
    {
        $join = new Join('LEFT_JOIN', RelatedDummy::class, 'a_1', null, 'a_1.name = r.name');
        $this->assertEquals('a_1', QueryJoinParser::getJoinAlias($join));
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryJoinParser::getOrderByParts()" is deprecated since 2.3 and will be removed in 3.0. Use "Doctrine\ORM\Query\Expr\OrderBy::getParts()" directly instead.
     */
    public function testGetOrderByPartsWithOrderBy()
    {
        $orderBy = new OrderBy('name', 'asc');
        $this->assertEquals(['name asc'], QueryJoinParser::getOrderByParts($orderBy));
    }
}
