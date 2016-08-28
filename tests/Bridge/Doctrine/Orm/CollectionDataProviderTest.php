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

use ApiPlatform\Core\Bridge\Doctrine\Orm\CollectionDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCollection()
    {
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([])->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $exstensionProphecy = $this->prophesize(QueryCollectionExtensionInterface::class);
        $exstensionProphecy->applyToCollection($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Dummy::class, 'foo')->shouldBeCalled();

        $dataProvider = new CollectionDataProvider($managerRegistryProphecy->reveal(), [$exstensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->getCollection(Dummy::class, 'foo'));
    }

    public function testQueryResultExtension()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $exstensionProphecy = $this->prophesize(QueryResultExtensionInterface::class);
        $exstensionProphecy->applyToCollection($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Dummy::class, 'foo')->shouldBeCalled();
        $exstensionProphecy->supportsResult(Dummy::class, 'foo')->willReturn(true)->shouldBeCalled();
        $exstensionProphecy->getResult($queryBuilder)->willReturn([])->shouldBeCalled();

        $dataProvider = new CollectionDataProvider($managerRegistryProphecy->reveal(), [$exstensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->getCollection(Dummy::class, 'foo'));
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

        $dataProvider = new CollectionDataProvider($managerRegistryProphecy->reveal());
        $this->assertEquals([], $dataProvider->getCollection(Dummy::class, 'foo'));
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotSupportedException
     */
    public function testThrowResourceClassNotSupportedException()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $extenstensionProphecy = $this->prophesize(QueryResultExtensionInterface::class);

        $dataProvider = new CollectionDataProvider($managerRegistryProphecy->reveal(), [$extenstensionProphecy->reveal()]);
        $dataProvider->getCollection(Dummy::class, 'foo');
    }
}
