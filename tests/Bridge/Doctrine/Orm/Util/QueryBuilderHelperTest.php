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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class QueryBuilderHelperTest extends TestCase
{
    /**
     * @dataProvider provideAddJoinOnce
     */
    public function testAddJoinOnce(?string $originAliasForJoinOnce, string $expectedAlias)
    {
        $queryBuilder = new QueryBuilder($this->prophesize(EntityManagerInterface::class)->reveal());
        $queryBuilder->from('foo', 'f');
        $queryBuilder->from('foo', 'f2');
        $queryBuilder->join('f.bar', 'b');
        $queryBuilder->join('f2.bar', 'b2');

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        QueryBuilderHelper::addJoinOnce(
            $queryBuilder,
            $queryNameGenerator->reveal(),
            $originAliasForJoinOnce ?? 'f',
            'bar',
            null,
            null,
            null,
            $originAliasForJoinOnce
        );

        $this->assertSame($expectedAlias,
            $queryBuilder->getDQLPart('join')[$originAliasForJoinOnce ?? 'f'][0]->getAlias());
    }

    /**
     * @dataProvider provideAddJoinOnceWithMixedJoinTypes
     */
    public function testAddJoinOnceWithMixedJoinTypes(
        string $originAliasForJoinOnce,
        string $newAlias,
        string $joinType,
        string $expectedAlias,
        string $expectedJoinType
    ): void {
        $queryBuilder = new QueryBuilder($this->prophesize(EntityManagerInterface::class)->reveal());
        $queryBuilder->from('foo', 'f');
        $queryBuilder->from('foo', 'f2');
        $queryBuilder->join('f.bar', 'b');
        $queryBuilder->join('f2.bar', 'b2');
        $queryBuilder->leftJoin('f2.bar', 'bl2');

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $alias = QueryBuilderHelper::addJoinOnce(
            $queryBuilder,
            $queryNameGenerator->reveal(),
            $originAliasForJoinOnce,
            'bar',
            $joinType,
            null,
            null,
            $originAliasForJoinOnce,
            $newAlias
        );

        /** @var Join $join */
        foreach ($queryBuilder->getDQLPart('join')[$originAliasForJoinOnce] as $join) {
            if ($join->getAlias() === $alias) {
                $addedJoinType = $join->getJoinType();
            }
        }

        $this->assertEqualsCanonicalizing(
            [$expectedAlias, $expectedJoinType],
            [$alias, $addedJoinType ?? null]
        );
    }

    public function testAddJoinOnceWithSpecifiedNewAlias()
    {
        $queryBuilder = new QueryBuilder($this->prophesize(EntityManagerInterface::class)->reveal());
        $queryBuilder->from('foo', 'f');

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias(Argument::any())->shouldNotbeCalled();

        QueryBuilderHelper::addJoinOnce(
            $queryBuilder,
            $queryNameGenerator->reveal(),
            'f',
            'bar',
            null,
            null,
            null,
            null,
            'f_8'
        );

        $this->assertSame('f_8',
            $queryBuilder->getDQLPart('join')['f'][0]->getAlias());
    }

    public function testGetEntityClassByAliasWithJoinByAssociation(): void
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

        $actual = QueryBuilderHelper::getEntityClassByAlias('a_1', $queryBuilder, $managerRegistryProphecy->reveal());

        $this->assertEquals(RelatedDummy::class, $actual);
    }

    public function testGetEntityClassByAliasWithJoinByClass(): void
    {
        $relatedDummyMetadata = new ClassMetadata(RelatedDummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getClassMetadata(RelatedDummy::class)->willReturn($relatedDummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->from(Dummy::class, 'd');
        $queryBuilder->innerJoin(RelatedDummy::class, 'a_1', null, 'd.name = a_1.name');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->willReturn($entityManagerProphecy);

        $actual = QueryBuilderHelper::getEntityClassByAlias('a_1', $queryBuilder, $managerRegistryProphecy->reveal());

        $this->assertEquals(RelatedDummy::class, $actual);
    }

    public function provideAddJoinOnce(): array
    {
        return [
            [
                null,
                'b',
            ],
            [
                'f2',
                'b2',
            ],
        ];
    }

    public function provideAddJoinOnceWithMixedJoinTypes(): array
    {
        return [
            'Adding new join for already joined association but with different type' => [
                'f',
                'bl',
                Join::LEFT_JOIN,
                'bl',
                Join::LEFT_JOIN,
            ],
            'Adding already existing join with type left' => [
                'f2',
                'bl8',
                Join::LEFT_JOIN,
                'bl2',
                Join::LEFT_JOIN,
            ],
            'Adding already existing join with type inner' => [
                'f2',
                'b8',
                Join::INNER_JOIN,
                'b2',
                Join::INNER_JOIN,
            ],
        ];
    }
}
