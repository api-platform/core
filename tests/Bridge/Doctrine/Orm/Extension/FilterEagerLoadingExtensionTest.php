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

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeItem;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeLabel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class FilterEagerLoadingExtensionTest extends \PHPUnit_Framework_TestCase
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
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class, null);
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

        $classMetadata = new ClassMetadataInfo(CompositeRelation::class);
        $classMetadata->isIdentifierComposite = true;
        $classMetadata->identifier = ['item', 'label'];

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
        $queryNameGenerator->generateJoinAlias('item')->shouldBeCalled()->willReturn('item_2');
        $queryNameGenerator->generateJoinAlias('label')->shouldBeCalled()->willReturn('label_2');
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
            ->where('item.field1 = :foo')
            ->setParameter('foo', 1);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('item')->shouldBeCalled()->willReturn('item_2');
        $queryNameGenerator->generateJoinAlias('label')->shouldBeCalled()->willReturn('label_2');
        $queryNameGenerator->generateJoinAlias('o')->shouldBeCalled()->willReturn('o_2');

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), false);
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

    private function toDQLString(string $dql): string
    {
        return preg_replace(['/\s+/', '/\(\s/', '/\s\)/'], [' ', '(', ')'], $dql);
    }
}
