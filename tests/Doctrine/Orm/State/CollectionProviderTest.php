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
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OperationResource;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CollectionProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testGetCollection(): void
    {
        $query = $this->createMock($this->getQueryClass());
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())->method('createQueryBuilder')->with('o')->willReturn($queryBuilder);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())->method('getRepository')->with(OperationResource::class)->willReturn($repository);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->once())->method('getManagerForClass')->with(OperationResource::class)->willReturn($manager);

        $operation = (new GetCollection())->withClass(OperationResource::class)->withName('getCollection');

        $extension = $this->createMock(QueryCollectionExtensionInterface::class);
        $extension->expects($this->once())->method('applyToCollection')->with(
            $queryBuilder,
            new QueryNameGenerator(),
            OperationResource::class,
            $operation,
            []
        );

        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);

        $dataProvider = new CollectionProvider($resourceMetadataCollectionFactory, $managerRegistry, [$extension]);
        $this->assertEquals([], $dataProvider->provide($operation));
    }

    public function testQueryResultExtension(): void
    {
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getRootAliases')->willReturn(['alias']);

        $repositoryMock = $this->createMock(EntityRepository::class);
        $repositoryMock->method('createQueryBuilder')->willReturn($queryBuilderMock);

        $managerMock = $this->createMock(ObjectManager::class);
        $managerMock->method('getClassMetadata')->willReturn(new ClassMetadata(OperationResource::class));
        $managerMock->method('getRepository')->willReturn($repositoryMock);

        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->willReturn($managerMock);

        $operation = (new GetCollection())->withClass(OperationResource::class);

        $extensionMock = $this->createMock(QueryResultCollectionExtensionInterface::class);
        $extensionMock->expects($this->once())
            ->method('applyToCollection')
            ->with(
                $queryBuilderMock,
                $this->isInstanceOf(QueryNameGeneratorInterface::class),
                OperationResource::class,
                $operation,
                []
            );
        $extensionMock->expects($this->once())
            ->method('supportsResult')
            ->with(OperationResource::class, $operation, [])
            ->willReturn(true);
        $extensionMock->expects($this->once())
            ->method('getResult')
            ->with($queryBuilderMock, OperationResource::class, $operation, [])
            ->willReturn([]);

        $dataProvider = new CollectionProvider(
            $this->createMock(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
            [$extensionMock]
        );

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
        $query = $this->createStub($this->getQueryClass());
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

    /**
     * Doctrine ORM 3 removed the final keyword but strong-typed return types.
     * In Doctrine ORM 2 we can mock the AbstractQuery instead, as Query is final.
     */
    private function getQueryClass(): string
    {
        if ((new \ReflectionClass(Query::class))->isFinal()) {
            return AbstractQuery::class;
        }

        return Query::class;
    }
}
