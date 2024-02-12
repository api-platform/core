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

namespace ApiPlatform\Tests\Doctrine\Orm\Util;

use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class QueryBuilderHelperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider provideAddJoinOnce
     */
    public function testAddJoinOnce(?string $originAliasForJoinOnce, string $expectedAlias): void
    {
        $queryBuilder = new QueryBuilder($this->prophesize(EntityManagerInterface::class)->reveal());
        $queryBuilder->from(Dummy::class, 'f');
        $queryBuilder->from(Dummy::class, 'f2');
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
     * @dataProvider provideAddJoinOnce
     */
    public function testAddJoinOnceWithSpecifiedNewAlias(): void
    {
        $queryBuilder = new QueryBuilder($this->prophesize(EntityManagerInterface::class)->reveal());
        $queryBuilder->from(Dummy::class, 'f');

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

        $this->assertSame(RelatedDummy::class, $actual);
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

        $this->assertSame(RelatedDummy::class, $actual);
    }

    public static function provideAddJoinOnce(): \Iterator
    {
        yield [
            null,
            'b',
        ];
        yield [
            'f2',
            'b2',
        ];
    }
}
