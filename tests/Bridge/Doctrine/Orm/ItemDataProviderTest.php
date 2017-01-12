<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Doctrine\Orm;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\ItemDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\IdentifierManagerInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Prophecy\Argument;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ItemDataProviderTest extends \PHPUnit_Framework_TestCase
{
    private function getIdentifierManagerProphecy($input, $resourceClass, $output)
    {
        $objectManager = Argument::type(ObjectManager::class);
        $identifierManagerProphecy = $this->prophesize(IdentifierManagerInterface::class);
        $identifierManagerProphecy->normalizeIdentifiers($input, $objectManager, $resourceClass)->willReturn($output);

        return $identifierManagerProphecy->reveal();
    }

    public function testGetItemSingleIdentifier()
    {
        $context = ['foo' => 'bar', 'fetch_data' => true];
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->willReturn([])->shouldBeCalled();

        $comparisonProphecy = $this->prophesize(Comparison::class);
        $comparison = $comparisonProphecy->reveal();

        $exprProphecy = $this->prophesize(Expr::class);
        $exprProphecy->eq('o.id', ':id_id')->willReturn($comparisonProphecy)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilderProphecy->expr()->willReturn($exprProphecy->reveal())->shouldBeCalled();
        $queryBuilderProphecy->andWhere($comparison)->shouldBeCalled();
        $queryBuilderProphecy->setParameter(':id_id', 1)->shouldBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Dummy::class, ['id' => 1], 'foo', $context)->shouldBeCalled();

        $identifierManagerProphecy = $this->getIdentifierManagerProphecy(1, Dummy::class, ['id' => 1]);

        $dataProvider = new ItemDataProvider($managerRegistryProphecy->reveal(), $identifierManagerProphecy, [$extensionProphecy->reveal()]);

        $this->assertEquals([], $dataProvider->getItem(Dummy::class, 1, 'foo', $context));
    }

    public function testGetItemDoubleIdentifier()
    {
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->willReturn([])->shouldBeCalled();

        $comparisonProphecy = $this->prophesize(Comparison::class);
        $comparison = $comparisonProphecy->reveal();

        $exprProphecy = $this->prophesize(Expr::class);
        $exprProphecy->eq('o.ida', ':id_ida')->willReturn($comparisonProphecy)->shouldBeCalled();
        $exprProphecy->eq('o.idb', ':id_idb')->willReturn($comparisonProphecy)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilderProphecy->expr()->willReturn($exprProphecy->reveal())->shouldBeCalled();
        $queryBuilderProphecy->andWhere($comparison)->shouldBeCalled();

        $queryBuilderProphecy->setParameter(':id_ida', 1)->shouldBeCalled();
        $queryBuilderProphecy->setParameter(':id_idb', 2)->shouldBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Dummy::class, ['ida' => 1, 'idb' => 2], 'foo', [])->shouldBeCalled();

        $identifierManagerProphecy = $this->getIdentifierManagerProphecy('ida=1;idb=2', Dummy::class, ['ida' => 1, 'idb' => 2]);

        $dataProvider = new ItemDataProvider($managerRegistryProphecy->reveal(), $identifierManagerProphecy, [$extensionProphecy->reveal()]);

        $this->assertEquals([], $dataProvider->getItem(Dummy::class, 'ida=1;idb=2', 'foo'));
    }

    public function testQueryResultExtension()
    {
        $comparisonProphecy = $this->prophesize(Comparison::class);
        $comparison = $comparisonProphecy->reveal();

        $exprProphecy = $this->prophesize(Expr::class);
        $exprProphecy->eq('o.id', ':id_id')->willReturn($comparisonProphecy)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($exprProphecy->reveal())->shouldBeCalled();
        $queryBuilderProphecy->andWhere($comparison)->shouldBeCalled();
        $queryBuilderProphecy->setParameter(':id_id', 1)->shouldBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryResultItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Dummy::class, ['id' => 1], 'foo', [])->shouldBeCalled();
        $extensionProphecy->supportsResult(Dummy::class, 'foo')->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($queryBuilder)->willReturn([])->shouldBeCalled();

        $identifierManagerProphecy = $this->getIdentifierManagerProphecy(1, Dummy::class, ['id' => 1]);

        $dataProvider = new ItemDataProvider($managerRegistryProphecy->reveal(), $identifierManagerProphecy, [$extensionProphecy->reveal()]);

        $this->assertEquals([], $dataProvider->getItem(Dummy::class, 1, 'foo'));
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotSupportedException
     */
    public function testThrowResourceClassNotSupportedException()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);

        $identifierManagerProphecy = $this->getIdentifierManagerProphecy(1, Dummy::class, ['id' => 1]);

        $dataProvider = new ItemDataProvider($managerRegistryProphecy->reveal(), $identifierManagerProphecy, [$extensionProphecy->reveal()]);
        $dataProvider->getItem(Dummy::class, 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage The repository class must have a "createQueryBuilder" method.
     */
    public function testCannotCreateQueryBuilder()
    {
        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);

        $identifierManagerProphecy = $this->getIdentifierManagerProphecy('foo', Dummy::class, ['id' => 'foo']);

        (new ItemDataProvider($managerRegistryProphecy->reveal(), $identifierManagerProphecy, [$extensionProphecy->reveal()]))->getItem(Dummy::class, 'foo');
    }
}
