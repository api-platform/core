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

namespace ApiPlatform\Tests\Doctrine\Orm\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OperationResource;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class CollectionProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testGetCollection(): void
    {
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([])->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['alias']);
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getClassMetadata(OperationResource::class)->willReturn(new ClassMetadata(OperationResource::class));
        $managerProphecy->getRepository(OperationResource::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $operation = (new GetCollection())->withClass(OperationResource::class)->withName('getCollection');

        $extensionProphecy = $this->prophesize(QueryCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), OperationResource::class, $operation, [])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->provide($operation));
    }

    public function testQueryResultExtension(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['alias']);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getClassMetadata(OperationResource::class)->willReturn(new ClassMetadata(OperationResource::class));
        $managerProphecy->getRepository(OperationResource::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $operation = (new GetCollection())->withClass(OperationResource::class);

        $extensionProphecy = $this->prophesize(QueryResultCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), OperationResource::class, $operation, [])->shouldBeCalled();
        $extensionProphecy->supportsResult(OperationResource::class, $operation, [])->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($queryBuilder, OperationResource::class, $operation, [])->willReturn([])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->provide($operation));
    }

    public function testCannotCreateQueryBuilder(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository class must have a "createQueryBuilder" method.');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(OperationResource::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistryProphecy->reveal());
        $operation = (new GetCollection())->withClass(OperationResource::class)->withName('getCollection');
        $this->assertEquals([], $dataProvider->provide($operation));
    }

    public function testHandleLinksCallable(): void
    {
        $class = 'foo';
        $resourceMetadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $query = $this->createStub(AbstractQuery::class);
        $query->method('getResult')->willReturn([]);
        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('getQuery')->willReturn($query);
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('createQueryBuilder')->willReturn($qb);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($manager);
        $operation = new GetCollection(class: $class, stateOptions: new Options(handleLinks: fn () => $this->assertTrue(true)));
        $dataProvider = new CollectionProvider($resourceMetadata, $managerRegistry);
        $dataProvider->provide($operation, ['id' => 1]);
    }
}
