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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @group legacy
 */
class QueryJoinParserTest extends TestCase
{
    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryJoinParser::getClassMetadataFromJoinAlias()" is deprecated since 2.4 and will be removed in 3.0. Use "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper::getEntityClassByAlias()" instead.
     */
    public function testGetClassMetadataFromJoinAliasWithJoinByAssociation(): void
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
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->innerJoin('d.relatedDummies', 'a_1');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $actual = QueryJoinParser::getClassMetadataFromJoinAlias('a_1', $queryBuilder, $managerRegistryProphecy->reveal());

        $this->assertEquals($relatedDummyMetadata, $actual);
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryJoinParser::getClassMetadataFromJoinAlias()" is deprecated since 2.4 and will be removed in 3.0. Use "ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper::getEntityClassByAlias()" instead.
     */
    public function testGetClassMetadataFromJoinAliasWithJoinByClass(): void
    {
        $relatedDummyMetadata = new ClassMetadata(RelatedDummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getClassMetadata(RelatedDummy::class)->willReturn($relatedDummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->innerJoin(RelatedDummy::class, 'a_1', null, 'd.name = a_1.name');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $actual = QueryJoinParser::getClassMetadataFromJoinAlias('a_1', $queryBuilder, $managerRegistryProphecy->reveal());

        $this->assertEquals($relatedDummyMetadata, $actual);
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
