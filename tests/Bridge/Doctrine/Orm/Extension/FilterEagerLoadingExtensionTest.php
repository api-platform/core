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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Api\ResourceClassResolver;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeItem;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeLabel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTravel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class FilterEagerLoadingExtensionTest extends TestCase
{
    public function testIsNoForceEagerCollectionAttributes()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class, null, null, null, [
            'get' => [
                'force_eager' => false,
            ],
        ], null));

        $em = $this->prophesize(EntityManager::class);
        $em->getClassMetadata(DummyCar::class)->shouldBeCalled()->willReturn(new ClassMetadataInfo(DummyCar::class));

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->getDQLPart('where')->shouldNotBeCalled();
        $qb->getEntityManager()->willReturn($em);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class, 'get');
    }

    public function testIsNoForceEagerResource()
    {
        $em = $this->prophesize(EntityManager::class);
        $em->getClassMetadata(DummyCar::class)->shouldBeCalled()->willReturn(new ClassMetadataInfo(DummyCar::class));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class, null, null, null, [
            'get' => [],
        ], ['force_eager' => false]));

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->getDQLPart('where')->shouldNotBeCalled();
        $qb->getEntityManager()->willReturn($em);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class);
    }

    public function testIsForceEagerConfig()
    {
        $em = $this->prophesize(EntityManager::class);
        $em->getClassMetadata(DummyCar::class)->shouldBeCalled()->willReturn(new ClassMetadataInfo(DummyCar::class));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class, null, null, null, [
            'get' => [],
        ]));

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->getDQLPart('where')->shouldNotBeCalled();
        $qb->getEntityManager()->willReturn($em);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), false);
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class, 'get');
    }

    public function testHasNoWherePart()
    {
        $em = $this->prophesize(EntityManager::class);
        $em->getClassMetadata(DummyCar::class)->shouldBeCalled()->willReturn(new ClassMetadataInfo(DummyCar::class));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->getDQLPart('where')->shouldBeCalled()->willReturn(null);
        $qb->getEntityManager()->willReturn($em);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class, 'get');
    }

    public function testHasNoJoinPart()
    {
        $em = $this->prophesize(EntityManager::class);
        $em->getClassMetadata(DummyCar::class)->shouldBeCalled()->willReturn(new ClassMetadataInfo(DummyCar::class));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->getDQLPart('where')->shouldBeCalled()->willReturn(new Expr\Andx());
        $qb->getDQLPart('join')->shouldBeCalled()->willReturn(null);
        $qb->getRootAliases()->shouldBeCalled()->willReturn(['o']);
        $qb->getEntityManager()->willReturn($em);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class, 'get');
    }

    public function testApplyCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());
        $em->getClassMetadata(DummyCar::class)->shouldBeCalled()->willReturn(new ClassMetadataInfo(DummyCar::class));

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o')
            ->from(DummyCar::class, 'o')
            ->leftJoin('o.colors', 'colors')
            ->where('o.colors = :foo')
            ->setParameter('foo', 1);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('colors')->shouldBeCalled()->willReturn('colors_2');
        $queryNameGenerator->generateJoinAlias('o')->shouldBeCalled()->willReturn('o_2');

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), DummyCar::class, 'get');

        $this->assertEquals('SELECT o FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o LEFT JOIN o.colors colors WHERE o IN(SELECT o_2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o_2 LEFT JOIN o_2.colors colors_2 WHERE o_2.colors = :foo)', $qb->getDQL());
    }

    public function testApplyCollectionWithManualJoin()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyTravel::class]));

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());
        $em->getClassMetadata(DummyCar::class)->shouldBeCalled()->willReturn(new ClassMetadataInfo(DummyCar::class));

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o')
            ->from(DummyCar::class, 'o')
            ->leftJoin('o.colors', 'colors')
            ->join(DummyTravel::class, 't_a3', Expr\Join::WITH, 'o.id = t_a3.car AND t_a3.passenger = :user')
            ->where('o.colors = :foo')
            ->andwhere('t_a3.confirmed = :confirmation')
            ->setParameter('foo', 1)
            ->setParameter('user', 2)
            ->setParameter('confirmation', true);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('colors')->shouldBeCalled()->willReturn('colors_2');
        $queryNameGenerator->generateJoinAlias('o')->shouldBeCalled()->willReturn('o_2');
        $queryNameGenerator->generateJoinAlias('t_a3')->shouldBeCalled()->willReturn('t_a3_a20');

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true, new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal()));
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), DummyCar::class, 'get');

        $expected = <<<'SQL'
SELECT o
FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o
LEFT JOIN o.colors colors
INNER JOIN ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTravel t_a3 WITH o.id = t_a3.car AND t_a3.passenger = :user
WHERE o IN(
  SELECT o_2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o_2
  LEFT JOIN o_2.colors colors_2
  INNER JOIN ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTravel t_a3_a20 WITH o_2.id = t_a3_a20.car AND t_a3_a20.passenger = :user
  WHERE o_2.colors = :foo AND t_a3_a20.confirmed = :confirmation
)
SQL;

        $this->assertEquals($this->toDQLString($expected), $qb->getDQL());
    }

    public function testApplyCollectionCorrectlyReplacesJoinCondition()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyTravel::class]));

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());
        $em->getClassMetadata(DummyCar::class)->shouldBeCalled()->willReturn(new ClassMetadataInfo(DummyCar::class));

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o')
            ->from(DummyCar::class, 'o')
            ->leftJoin('o.colors', 'colors', 'ON', 'o.id = colors.car AND colors.id IN (1,2,3)')
            ->where('o.colors = :foo')
            ->setParameter('foo', 1);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('colors')->shouldBeCalled()->willReturn('colors_2');
        $queryNameGenerator->generateJoinAlias('o')->shouldBeCalled()->willReturn('o_2');

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true, new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal()));
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), DummyCar::class, 'get');

        $expected = <<<'SQL'
SELECT o
FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o
LEFT JOIN o.colors colors ON o.id = colors.car AND colors.id IN (1,2,3)
WHERE o IN(
  SELECT o_2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o_2
  LEFT JOIN o_2.colors colors_2 ON o_2.id = colors_2.car AND colors_2.id IN (1,2,3)
  WHERE o_2.colors = :foo
)
SQL;

        $this->assertEquals($this->toDQLString($expected), $qb->getDQL());
    }

    /**
     * https://github.com/api-platform/core/issues/1021.
     */
    public function testHiddenOrderBy()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());
        $em->getClassMetadata(DummyCar::class)->shouldBeCalled()->willReturn(new ClassMetadataInfo(DummyCar::class));

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o', 'CASE WHEN o.dateCreated IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dateCreated_null_rank')
            ->from(DummyCar::class, 'o')
            ->leftJoin('o.colors', 'colors')
            ->where('o.colors = :foo')
            ->orderBy('_o_dateCreated_null_rank DESC')
            ->setParameter('foo', 1);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('colors')->shouldBeCalled()->willReturn('colors_2');
        $queryNameGenerator->generateJoinAlias('o')->shouldBeCalled()->willReturn('o_2');
        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), DummyCar::class, 'get');

        $expected = <<<SQL
SELECT o, CASE WHEN o.dateCreated IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dateCreated_null_rank
FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o
LEFT JOIN o.colors colors
WHERE o IN(
  SELECT o_2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o_2
  LEFT JOIN o_2.colors colors_2
  WHERE o_2.colors = :foo
) ORDER BY _o_dateCreated_null_rank DESC ASC
SQL;

        $this->assertEquals($this->toDQLString($expected), $qb->getDQL());
    }

    public function testGroupBy()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());
        $em->getClassMetadata(DummyCar::class)->shouldBeCalled()->willReturn(new ClassMetadataInfo(DummyCar::class));

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o', 'count(o.id) as counter')
            ->from(DummyCar::class, 'o')
            ->leftJoin('o.colors', 'colors')
            ->where('o.colors = :foo')
            ->orderBy('o.colors')
            ->groupBy('o.colors')
            ->having('counter > 3')
            ->setParameter('foo', 1);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('colors')->shouldBeCalled()->willReturn('colors_2');
        $queryNameGenerator->generateJoinAlias('o')->shouldBeCalled()->willReturn('o_2');
        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), DummyCar::class, 'get');

        $expected = <<<SQL
SELECT o, count(o.id) as counter
FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o
LEFT JOIN o.colors colors WHERE o
IN(
  SELECT o_2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o_2
  LEFT JOIN o_2.colors colors_2
  WHERE o_2.colors = :foo
)
GROUP BY o.colors HAVING counter > 3
ORDER BY o.colors ASC
SQL;

        $this->assertEquals($this->toDQLString($expected), $qb->getDQL());
    }

    public function testCompositeIdentifiers()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(CompositeRelation::class)->willReturn(new ResourceMetadata(CompositeRelation::class));

        $classMetadataProphecy = $this->prophesize(ClassMetadataInfo::class);
        $classMetadataProphecy->getIdentifier()->willReturn(['item', 'label']);
        $classMetadataProphecy->getAssociationMappings()->willReturn(['item' => ['fetch' => ClassMetadataInfo::FETCH_EAGER]]);
        $classMetadataProphecy->hasAssociation('item')->shouldBeCalled()->willReturn(true);
        $classMetadataProphecy->hasAssociation('label')->shouldBeCalled()->willReturn(true);

        $classMetadata = $classMetadataProphecy->reveal();
        $classMetadata->isIdentifierComposite = true;

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());
        $em->getClassMetadata(CompositeRelation::class)->shouldBeCalled()->willReturn($classMetadata);

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o')
            ->from(CompositeRelation::class, 'o')
            ->innerJoin('o.compositeItem', 'item')
            ->innerJoin('o.compositeLabel', 'label')
            ->where('item.field1 = :foo')
            ->setParameter('foo', 1);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('compositeItem')->shouldBeCalled()->willReturn('item_2');
        $queryNameGenerator->generateJoinAlias('compositeLabel')->shouldBeCalled()->willReturn('label_2');
        $queryNameGenerator->generateJoinAlias('o')->shouldBeCalled()->willReturn('o_2');

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), CompositeRelation::class, 'get');

        $expected = <<<SQL
SELECT o
FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation o
INNER JOIN o.compositeItem item
INNER JOIN o.compositeLabel label
WHERE o.item IN(
    SELECT IDENTITY(o_2.item) FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation o_2
    INNER JOIN o_2.compositeItem item_2
    INNER JOIN o_2.compositeLabel label_2
    WHERE item_2.field1 = :foo
) AND o.label IN(
    SELECT IDENTITY(o_2.label) FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation o_2
    INNER JOIN o_2.compositeItem item_2
    INNER JOIN o_2.compositeLabel label_2
    WHERE item_2.field1 = :foo
)
SQL;

        $this->assertEquals($this->toDQLString($expected), $qb->getDQL());
    }

    public function testFetchEagerWithNoForceEager()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(CompositeRelation::class)->willReturn(new ResourceMetadata(CompositeRelation::class));
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $classMetadata = new ClassMetadataInfo(CompositeRelation::class);
        $classMetadata->isIdentifierComposite = true;
        $classMetadata->identifier = ['item', 'label'];
        $classMetadata->associationMappings = [
            'item' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => CompositeItem::class],
            'label' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => CompositeLabel::class],
        ];

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());
        $em->getClassMetadata(CompositeRelation::class)->shouldBeCalled()->willReturn($classMetadata);

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o')
            ->from(CompositeRelation::class, 'o')
            ->innerJoin('o.compositeItem', 'item')
            ->innerJoin('o.compositeLabel', 'label')
            ->leftJoin('o.foo', 'foo', 'WITH', 'o.bar = item.foo')
            ->leftJoin(DummyCar::class, 'car', 'WITH', 'car.id = o.car')
            ->where('item.field1 = :foo')
            ->setParameter('foo', 1);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('compositeItem')->shouldBeCalled()->willReturn('item_2');
        $queryNameGenerator->generateJoinAlias('compositeLabel')->shouldBeCalled()->willReturn('label_2');
        $queryNameGenerator->generateJoinAlias('o')->shouldBeCalled()->willReturn('o_2');

        $queryNameGenerator->generateJoinAlias('foo')->shouldBeCalled()->willReturn('foo_2');
        $queryNameGenerator->generateJoinAlias(DummyCar::class)->shouldNotBeCalled();

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), false);
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), CompositeRelation::class, 'get');

        $expected = <<<DQL
SELECT o
FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation o
INNER JOIN o.compositeItem item
INNER JOIN o.compositeLabel label
LEFT JOIN o.foo foo WITH o.bar = item.foo
LEFT JOIN ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar car WITH car.id = o.car
WHERE o.item IN(
    SELECT IDENTITY(o_2.item) FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation o_2
    INNER JOIN o_2.compositeItem item_2
    INNER JOIN o_2.compositeLabel label_2
    LEFT JOIN o_2.foo foo_2 WITH o_2.bar = item_2.foo
    WHERE item_2.field1 = :foo
) AND o.label IN(
    SELECT IDENTITY(o_2.label) FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation o_2
    INNER JOIN o_2.compositeItem item_2
    INNER JOIN o_2.compositeLabel label_2
    LEFT JOIN o_2.foo foo_2 WITH o_2.bar = item_2.foo
    WHERE item_2.field1 = :foo
)
DQL;

        $this->assertEquals($this->toDQLString($expected), $qb->getDQL());
    }

    public function testCompositeIdentifiersWithAssociation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(CompositeRelation::class)->willReturn(new ResourceMetadata(CompositeRelation::class));

        $classMetadataProphecy = $this->prophesize(ClassMetadataInfo::class);
        $classMetadataProphecy->getIdentifier()->willReturn(['item', 'label', 'bar']);
        $classMetadataProphecy->getAssociationMappings()->willReturn(['item' => ['fetch' => ClassMetadataInfo::FETCH_EAGER]]);
        $classMetadataProphecy->hasAssociation('item')->shouldBeCalled()->willReturn(true);
        $classMetadataProphecy->hasAssociation('label')->shouldBeCalled()->willReturn(true);
        $classMetadataProphecy->hasAssociation('bar')->shouldBeCalled()->willReturn(false);

        $classMetadata = $classMetadataProphecy->reveal();
        $classMetadata->isIdentifierComposite = true;

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());
        $em->getClassMetadata(CompositeRelation::class)->shouldBeCalled()->willReturn($classMetadata);

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o')
            ->from(CompositeRelation::class, 'o')
            ->innerJoin('o.compositeItem', 'item')
            ->innerJoin('o.compositeLabel', 'label')
            ->where('item.field1 = :foo AND o.bar = :bar')
            ->setParameter('foo', 1)
            ->setParameter('bar', 2);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('compositeItem')->shouldBeCalled()->willReturn('item_2');
        $queryNameGenerator->generateJoinAlias('compositeLabel')->shouldBeCalled()->willReturn('label_2');
        $queryNameGenerator->generateJoinAlias('o')->shouldBeCalled()->willReturn('o_2');

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), CompositeRelation::class, 'get');

        $expected = <<<SQL
SELECT o
FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation o
INNER JOIN o.compositeItem item
INNER JOIN o.compositeLabel label
WHERE (o.item IN(
    SELECT IDENTITY(o_2.item) FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation o_2
    INNER JOIN o_2.compositeItem item_2
    INNER JOIN o_2.compositeLabel label_2
    WHERE item_2.field1 = :foo AND o_2.bar = :bar
)) AND (o.label IN(
    SELECT IDENTITY(o_2.label) FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation o_2
    INNER JOIN o_2.compositeItem item_2
    INNER JOIN o_2.compositeLabel label_2
    WHERE item_2.field1 = :foo AND o_2.bar = :bar
))
SQL;

        $this->assertEquals($this->toDQLString($expected), $qb->getDQL());
    }

    public function testCompositeIdentifiersWithoutAssociation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(CompositeRelation::class)->willReturn(new ResourceMetadata(CompositeRelation::class));

        $classMetadataProphecy = $this->prophesize(ClassMetadataInfo::class);
        $classMetadataProphecy->getIdentifier()->willReturn(['foo', 'bar']);
        $classMetadataProphecy->getAssociationMappings()->willReturn(['item' => ['fetch' => ClassMetadataInfo::FETCH_EAGER]]);
        $classMetadataProphecy->hasAssociation('foo')->shouldBeCalled()->willReturn(false);
        $classMetadataProphecy->hasAssociation('bar')->shouldBeCalled()->willReturn(false);

        $classMetadata = $classMetadataProphecy->reveal();
        $classMetadata->isIdentifierComposite = true;

        $em = $this->prophesize(EntityManager::class);
        $em->getClassMetadata(CompositeRelation::class)->shouldBeCalled()->willReturn($classMetadata);

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o')
            ->from(CompositeRelation::class, 'o')
            ->innerJoin('o.compositeItem', 'item')
            ->innerJoin('o.compositeLabel', 'label')
            ->where('item.field1 = :foo AND o.bar = :bar')
            ->setParameter('foo', 1)
            ->setParameter('bar', 2);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), CompositeRelation::class, 'get');

        $expected = <<<SQL
SELECT o
FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation o
INNER JOIN o.compositeItem item
INNER JOIN o.compositeLabel label
WHERE item.field1 = :foo AND o.bar = :bar
SQL;

        $this->assertEquals($this->toDQLString($expected), $qb->getDQL());
    }

    private function toDQLString(string $dql): string
    {
        return preg_replace(['/\s+/', '/\(\s/', '/\s\)/'], [' ', '(', ')'], $dql);
    }
}
