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

namespace ApiPlatform\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class OrderExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testApplyToCollectionWithValidOrder(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilderProphecy->getDQLPart('orderBy')->shouldBeCalled()->willReturn([]);
        $queryBuilderProphecy->addOrderBy('o.name', 'asc')->shouldBeCalled()->willReturn($queryBuilderProphecy);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc');
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToCollectionWithWrongOrder(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilderProphecy->getDQLPart('orderBy')->shouldBeCalled()->willReturn([]);
        $queryBuilderProphecy->addOrderBy('o.name', 'asc')->shouldNotBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension();
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToCollectionWithOrderOverridden(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilderProphecy->getDQLPart('orderBy')->shouldBeCalled()->willReturn([]);
        $queryBuilderProphecy->addOrderBy('o.foo', 'DESC')->shouldBeCalled()->willReturn($queryBuilderProphecy);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc');
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(order: ['foo' => 'DESC']));
    }

    public function testApplyToCollectionWithOrderOverriddenWithNoDirection(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilderProphecy->getDQLPart('orderBy')->shouldBeCalled()->willReturn([]);
        $queryBuilderProphecy->addOrderBy('o.foo', 'ASC')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addOrderBy('o.bar', 'DESC')->shouldBeCalled()->willReturn($queryBuilderProphecy);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc');
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(order: ['foo', 'bar' => 'DESC']));
    }

    public function testApplyToCollectionWithOrderOverriddenWithAssociation(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilderProphecy->getDQLPart('orderBy')->shouldBeCalled()->willReturn([]);
        $queryBuilderProphecy->getDQLPart('join')->willReturn(['o' => []])->shouldBeCalled();
        $queryBuilderProphecy->innerJoin('o.author', 'author_a1', null, null)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->addOrderBy('author_a1.name', 'ASC')->shouldBeCalled()->willReturn($queryBuilderProphecy);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc');
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, new GetCollection(order: ['author.name']));
    }

    public function testApplyToCollectionWithOrderOverriddenWithEmbeddedAssociation(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getDQLPart('orderBy')->shouldBeCalled()->willReturn([]);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->addOrderBy('o.embeddedDummy.dummyName', 'DESC')->shouldBeCalled()->willReturn($queryBuilderProphecy);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['id']);
        $classMetadataProphecy->embeddedClasses = ['embeddedDummy' => []];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(EmbeddedDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy->reveal());

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc');
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), EmbeddedDummy::class, new GetCollection(order: ['embeddedDummy.dummyName' => 'DESC']));
    }

    public function testApplyToCollectionWithExistingOrderByDql(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilderProphecy->getDQLPart('orderBy')->shouldBeCalled()->willReturn([new OrderBy('o.name')]);
        $queryBuilderProphecy->getEntityManager()->shouldNotBeCalled();
        $queryBuilderProphecy->getRootAliases()->shouldNotBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension();
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }
}
