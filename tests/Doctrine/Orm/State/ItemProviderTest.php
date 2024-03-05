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

use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Company;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Employee;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OperationResource;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ItemProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testGetItemSingleIdentifier(): void
    {
        $returnObject = new \stdClass();

        $context = ['foo' => 'bar', 'fetch_data' => true];

        $queryMock = $this->createMock($this->getQueryClass());
        $queryMock->method('getOneOrNullResult')->willReturn($returnObject);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getQuery')->willReturn($queryMock);
        $queryBuilderMock->method('andWhere')->with('o.identifier = :identifier_p1');
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->method('setParameter')->with('identifier_p1', 1, Types::INTEGER);

        $managerMock = $this->getManagerRegistry(OperationResource::class, [
            'identifier' => [
                'type' => Types::INTEGER,
            ],
        ], $queryBuilderMock);
        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->with(OperationResource::class)->willReturn($managerMock);

        $operation = (new Get())->withUriVariables([
            'identifier' => (new Link())->withFromClass(OperationResource::class)
                ->withIdentifiers([0 => 'identifier']),
        ])->withClass(OperationResource::class)->withName('get');

        $extensionMock = $this->createMock(QueryItemExtensionInterface::class);
        $extensionMock->method('applyToItem')
            ->with($queryBuilderMock, $this->isInstanceOf(QueryNameGeneratorInterface::class), OperationResource::class, ['identifier' => 1], $operation, $context);

        $dataProvider = new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
            [$extensionMock]
        );

        $this->assertEquals($returnObject, $dataProvider->provide($operation, ['identifier' => 1], $context));
    }

    public function testGetItemDoubleIdentifier(): void
    {
        $returnObject = new \stdClass();

        $queryMock = $this->createMock($this->getQueryClass());
        $queryMock->method('getOneOrNullResult')->willReturn($returnObject);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getQuery')->willReturn($queryMock);
        $queryBuilderMock->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(['o.idb = :idb_p1'], ['o.ida = :ida_p2']);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(['idb_p1', 2, Types::INTEGER], ['ida_p2', 1, Types::INTEGER]);

        $operation = (new Get())->withUriVariables([
            'ida' => (new Link())->withFromClass(OperationResource::class)
                ->withIdentifiers([
                    0 => 'ida',
                ]),
            'idb' => (new Link())->withFromClass(OperationResource::class)
                ->withIdentifiers([
                    0 => 'idb',
                ]),
        ])->withName('get')->withClass(OperationResource::class);

        $managerMock = $this->getManagerRegistry(OperationResource::class, [
            'ida' => [
                'type' => Types::INTEGER,
            ],
            'idb' => [
                'type' => Types::INTEGER,
            ],
        ], $queryBuilderMock);
        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->with(OperationResource::class)->willReturn($managerMock);

        $context = [];
        $extensionMock = $this->createMock(QueryItemExtensionInterface::class);
        $extensionMock->expects($this->once())
            ->method('applyToItem')
            ->with($queryBuilderMock, $this->isInstanceOf(QueryNameGeneratorInterface::class), OperationResource::class, ['ida' => 1, 'idb' => 2], $operation, $context);

        $dataProvider = new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
            [$extensionMock]
        );

        $this->assertEquals($returnObject, $dataProvider->provide($operation, ['ida' => 1, 'idb' => 2], $context));
    }

    public function testQueryResultExtension(): void
    {
        $returnObject = new \stdClass();

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())->method('andWhere')->with('o.identifier = :identifier_p1');
        $queryBuilderMock->expects($this->once())->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->expects($this->once())->method('setParameter')->with('identifier_p1', 1, Types::INTEGER);

        $managerMock = $this->getManagerRegistry(OperationResource::class, [
            'identifier' => [
                'type' => Types::INTEGER,
            ],
        ], $queryBuilderMock);

        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->with(OperationResource::class)->willReturn($managerMock);

        $operation = (new Get())->withUriVariables([
            'identifier' => (new Link())->withFromClass(OperationResource::class)->withIdentifiers([
                0 => 'identifier',
            ]),
        ])->withClass(OperationResource::class)->withName('get');

        $context = [];
        $extensionMock = $this->createMock(QueryResultItemExtensionInterface::class);
        $extensionMock->expects($this->once())
            ->method('applyToItem')
            ->with($queryBuilderMock, $this->isInstanceOf(QueryNameGeneratorInterface::class), OperationResource::class, ['identifier' => 1], $operation, $context);
        $extensionMock->expects($this->once())
            ->method('supportsResult')
            ->with(OperationResource::class, $operation, $context)
            ->willReturn(true);
        $extensionMock->expects($this->once())
            ->method('getResult')
            ->with($queryBuilderMock, OperationResource::class, $operation, $context)
            ->willReturn($returnObject);

        $dataProvider = new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
            [$extensionMock]
        );

        $this->assertEquals($returnObject, $dataProvider->provide($operation, ['identifier' => 1], $context));
    }

    public function testCannotCreateQueryBuilder(): void
    {
        if (class_exists(AssociationMapping::class)) {
            $this->markTestSkipped();
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository class must have a "createQueryBuilder" method.');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);
        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifierFieldNames()->willReturn([
            'id',
        ]);
        $classMetadataProphecy->getTypeOfField('id')->willReturn(Types::INTEGER);

        $platformProphecy = $this->prophesize(AbstractPlatform::class);

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->getDatabasePlatform()->willReturn($platformProphecy);

        $managerProphecy = $this->prophesize(EntityManagerInterface::class);
        $managerProphecy->getClassMetadata(OperationResource::class)->willReturn($classMetadataProphecy->reveal());
        $managerProphecy->getConnection()->willReturn($connectionProphecy);
        $managerProphecy->getRepository(OperationResource::class)->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($managerProphecy->reveal());

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);

        $operation = (new Get())->withClass(OperationResource::class);
        $itemProvider = new ItemProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $itemProvider->provide($operation, ['id' => 1234], []);
    }

    /**
     * Gets a mocked manager registry.
     */
    private function getManagerRegistry(string $resourceClass, array $identifierFields, QueryBuilder $queryBuilder, array $classMetadatas = [])
    {
        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->method('getIdentifierFieldNames')->willReturn(array_keys($identifierFields));

        $getTypeOfFieldExpectations = [];
        foreach ($identifierFields as $name => $field) {
            $getTypeOfFieldExpectations[$name] = $field['type'];
        }

        $classMetadataMock->method('getTypeOfField')
            ->with($this->logicalOr(...array_keys($identifierFields)))
            ->willReturnCallback(function ($name) use ($getTypeOfFieldExpectations) {
                return $getTypeOfFieldExpectations[$name];
            });

        $platformMock = $this->createMock(AbstractPlatform::class);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('getDatabasePlatform')->willReturn($platformMock);

        $repositoryMock = $this->createMock(EntityRepository::class);
        $repositoryMock->method('createQueryBuilder')->with('o')->willReturn($queryBuilder);

        $managerMock = $this->createMock(EntityManagerInterface::class);
        $managerMock->method('getConnection')->willReturn($connectionMock);
        $managerMock->method('getRepository')->with($resourceClass)->willReturn($repositoryMock);

        $classMetadataExpectations = [$resourceClass => $classMetadataMock];

        foreach ($classMetadatas as $class => $classMetadata) {
            $classMetadataExpectations[$class] = $classMetadata;
        }

        $managerMock->method('getClassMetadata')
            ->with($this->logicalOr(...array_keys($classMetadataExpectations)))
            ->willReturnCallback(function ($name) use ($classMetadataExpectations) {
                return $classMetadataExpectations[$name];
            });

        return $managerMock;
    }

    public function testGetSubresourceFromProperty(): void
    {
        $returnObject = new \stdClass();

        $queryMock = $this->createMock($this->getQueryClass());
        $queryMock->method('getOneOrNullResult')->willReturn($returnObject);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())->method('join')->with(Employee::class, 'm_a1', 'WITH', 'o.id = m_a1.company');
        $queryBuilderMock->expects($this->once())->method('getQuery')->willReturn($queryMock);
        $queryBuilderMock->expects($this->once())->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->expects($this->once())->method('andWhere')->with('m_a1.id = :id_p1');
        $queryBuilderMock->expects($this->once())->method('setParameter')->with('id_p1', 1, Types::INTEGER);

        $employeeClassMetadataMock = $this->createMock(ClassMetadata::class);
        $employeeClassMetadataMock->method('getAssociationMapping')->with('company')->willReturn(
            class_exists(ManyToOneAssociationMapping::class) ?
                new ManyToOneAssociationMapping('company', Employee::class, Company::class) :
                [
                    'type' => ClassMetadata::TO_ONE,
                    'fieldName' => 'company',
                ]
        );

        $employeeClassMetadataMock->method('getTypeOfField')->with('id')->willReturn(Types::INTEGER);

        $managerMock = $this->getManagerRegistry(Company::class, [
            'id' => [
                'type' => Types::INTEGER,
            ],
        ], $queryBuilderMock, [
            Employee::class => $employeeClassMetadataMock,
        ]);

        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->with($this->logicalOr(Company::class, Employee::class))
            ->willReturn($managerMock);

        $operation = (new Get())->withUriVariables([
            'employeeId' => (new Link())->withFromClass(Employee::class)
                ->withIdentifiers([
                    0 => 'id',
                ])->withFromProperty('company'),
        ])->withName('getCompany')->withClass(Company::class);

        $extensionMock = $this->createMock(QueryItemExtensionInterface::class);
        $extensionMock->expects($this->once())
            ->method('applyToItem')
            ->with($queryBuilderMock, $this->isInstanceOf(QueryNameGeneratorInterface::class), Company::class, ['employeeId' => 1], $operation, []);

        $dataProvider = new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
            [$extensionMock]
        );

        $this->assertEquals($returnObject, $dataProvider->provide($operation, ['employeeId' => 1]));
    }

    public function testHandleLinksCallable(): void
    {
        $class = 'foo';
        $resourceMetadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $query = $this->createStub($this->getQueryClass());
        $query->method('getOneOrNullResult')->willReturn(null);
        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('getQuery')->willReturn($query);
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('createQueryBuilder')->willReturn($qb);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($manager);
        $operation = new Get(class: $class, stateOptions: new Options(handleLinks: fn () => $this->assertTrue(true)));
        $dataProvider = new ItemProvider($resourceMetadata, $managerRegistry);
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
