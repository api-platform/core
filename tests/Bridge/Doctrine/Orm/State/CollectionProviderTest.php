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

namespace ApiPlatform\Tests\Bridge\Doctrine\Orm\State;

use ApiPlatform\Bridge\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OperationResource;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class CollectionProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @requires PHP 8.0
     */
    public function testGetCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([])->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(OperationResource::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $resourceMetadataFactoryProphecy->create(OperationResource::class)->willReturn(new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations(['getCollection' => new GetCollection()]))]));

        $extensionProphecy = $this->prophesize(QueryCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), OperationResource::class, 'getCollection', [])->shouldBeCalled();

        $dataProvider = new CollectionProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->provide(OperationResource::class, [], 'getCollection'));
    }

    /**
     * @requires PHP 8.0
     */
    public function testQueryResultExtension()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(OperationResource::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryResultCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), OperationResource::class, null, [])->shouldBeCalled();
        $extensionProphecy->supportsResult(OperationResource::class, null, [])->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($queryBuilder, OperationResource::class, null, [])->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy->create(OperationResource::class)->willReturn(new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations(['' => new GetCollection()]))]));

        $dataProvider = new CollectionProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->provide(OperationResource::class));
    }

    /**
     * @requires PHP 8.0
     */
    public function testCannotCreateQueryBuilder()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository class must have a "createQueryBuilder" method.');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(OperationResource::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $dataProvider = new CollectionProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal());
        $this->assertEquals([], $dataProvider->provide(OperationResource::class));
    }

    /**
     * @requires PHP 8.0
     */
    public function testSupportedClass()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $resourceMetadataFactoryProphecy->create(OperationResource::class)->willReturn(new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations(['getCollection' => new GetCollection()]))]));
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($entityManagerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryResultCollectionExtensionInterface::class);

        $dataProvider = new CollectionProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertTrue($dataProvider->supports(OperationResource::class, [], 'getCollection'));
    }

    /**
     * @requires PHP 8.0
     */
    public function testUnsupportedClass()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryResultCollectionExtensionInterface::class);

        $dataProvider = new CollectionProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertFalse($dataProvider->supports(Dummy::class));
    }

    /**
     * @requires PHP 8.0
     */
    public function testNotCollectionOperation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $resourceMetadataFactoryProphecy->create(OperationResource::class)->willReturn(new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations(['get' => new Get()]))]));
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($entityManagerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryResultCollectionExtensionInterface::class);

        $dataProvider = new CollectionProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertFalse($dataProvider->supports(OperationResource::class, [], 'get'));
    }
}
