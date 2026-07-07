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

namespace ApiPlatform\Doctrine\Orm\Tests\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Company;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Employee;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\OperationResource;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Model\EmployeeApi;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
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
            ->method('andWhere');
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);
        $queryBuilderMock->expects($this->exactly(2))
            ->method('setParameter');

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

    public function testGetItemWithFetchDataFalseOnSubresourceFiltersParentLink(): void
    {
        $reference = new Employee();

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->method('getIdentifierFieldNames')->willReturn(['id']);

        $managerMock = $this->createMock(EntityManagerInterface::class);
        $managerMock->method('getClassMetadata')->with(Employee::class)->willReturn($classMetadataMock);
        $managerMock->expects($this->once())
            ->method('getReference')
            ->with(Employee::class, ['id' => 2])
            ->willReturn($reference);

        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->with(Employee::class)->willReturn($managerMock);

        $operation = (new Get())->withUriVariables([
            'companyId' => (new Link())->withFromClass(Company::class)->withToProperty('company'),
            'id' => (new Link())->withFromClass(Employee::class)->withIdentifiers(['id']),
        ])->withName('get')->withClass(Employee::class);

        $dataProvider = new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
        );

        $this->assertSame($reference, $dataProvider->provide($operation, ['companyId' => 1, 'id' => 2], ['fetch_data' => false]));
    }

    public function testGetItemWithFetchDataFalseMapsRenamedIdentifierUriVariable(): void
    {
        $reference = new Employee();

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->method('getIdentifierFieldNames')->willReturn(['id']);

        $managerMock = $this->createMock(EntityManagerInterface::class);
        $managerMock->method('getClassMetadata')->with(Employee::class)->willReturn($classMetadataMock);
        $managerMock->expects($this->once())
            ->method('getReference')
            ->with(Employee::class, ['id' => 2])
            ->willReturn($reference);

        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->with(Employee::class)->willReturn($managerMock);

        // The identifier uriVariable is named "employeeId" while the entity's own identifier field is "id".
        $operation = (new Get())->withUriVariables([
            'companyId' => (new Link())->withFromClass(Company::class)->withToProperty('company'),
            'employeeId' => (new Link())->withFromClass(Employee::class)->withIdentifiers(['id'])->withParameterName('employeeId'),
        ])->withName('get')->withClass(Employee::class);

        $dataProvider = new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
        );

        $this->assertSame($reference, $dataProvider->provide($operation, ['companyId' => 1, 'employeeId' => 2], ['fetch_data' => false]));
    }

    public function testGetItemWithFetchDataFalseFallsBackToQueryWhenOwnIdentifierMissing(): void
    {
        $returnObject = new \stdClass();

        $queryMock = $this->createMock($this->getQueryClass());
        $queryMock->method('getOneOrNullResult')->willReturn($returnObject);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getQuery')->willReturn($queryMock);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->method('getIdentifierFieldNames')->willReturn(['id']);

        $repositoryMock = $this->createMock(EntityRepository::class);
        $repositoryMock->method('createQueryBuilder')->with('o')->willReturn($queryBuilderMock);

        $managerMock = $this->createMock(EntityManagerInterface::class);
        $managerMock->method('getClassMetadata')->willReturn($classMetadataMock);
        $managerMock->method('getRepository')->willReturn($repositoryMock);
        // Only the parent link is provided: the own identifier cannot be resolved to a reference,
        // so we must fall back to the query that resolves the link instead of calling getReference().
        $managerMock->expects($this->never())->method('getReference');

        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->willReturn($managerMock);

        $operation = (new Get())->withUriVariables([
            'companyId' => (new Link())->withFromClass(Company::class)->withToProperty('company')->withIdentifiers(['id']),
            'id' => (new Link())->withFromClass(Employee::class)->withIdentifiers(['id']),
        ])->withName('get')->withClass(Employee::class);

        $dataProvider = new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
        );

        $this->assertSame($returnObject, $dataProvider->provide($operation, ['companyId' => 1], ['fetch_data' => false]));
    }

    public function testGetItemWithFetchDataFalseFallsBackToQueryWhenIdentifierIsNotADoctrineIdentifier(): void
    {
        $returnObject = new \stdClass();

        $queryMock = $this->createMock($this->getQueryClass());
        $queryMock->method('getOneOrNullResult')->willReturn($returnObject);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getQuery')->willReturn($queryMock);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);

        // The Doctrine identifier is "id" while the resource exposes "uuid" as its API identifier:
        // getReference() cannot be built from "uuid", so we must fall back to the query.
        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->method('getIdentifierFieldNames')->willReturn(['id']);

        $repositoryMock = $this->createMock(EntityRepository::class);
        $repositoryMock->method('createQueryBuilder')->with('o')->willReturn($queryBuilderMock);

        $managerMock = $this->createMock(EntityManagerInterface::class);
        $managerMock->method('getClassMetadata')->willReturn($classMetadataMock);
        $managerMock->method('getRepository')->willReturn($repositoryMock);
        $managerMock->expects($this->never())->method('getReference');

        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->willReturn($managerMock);

        $operation = (new Get())->withUriVariables([
            'uuid' => (new Link())->withFromClass(Employee::class)->withIdentifiers(['uuid'])->withParameterName('uuid'),
        ])->withName('get')->withClass(Employee::class);

        $dataProvider = new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
        );

        $this->assertSame($returnObject, $dataProvider->provide($operation, ['uuid' => '61817181-0ecc-42fb-a6e7-d97f2ddcb344'], ['fetch_data' => false]));
    }

    public function testGetItemWithFetchDataFalseReturnsReferenceForStateOptionsResource(): void
    {
        $reference = new Employee();

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->method('getIdentifierFieldNames')->willReturn(['id']);

        $managerMock = $this->createMock(EntityManagerInterface::class);
        $managerMock->method('getClassMetadata')->with(Employee::class)->willReturn($classMetadataMock);
        $managerMock->expects($this->once())
            ->method('getReference')
            ->with(Employee::class, ['id' => 2])
            ->willReturn($reference);

        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->with(Employee::class)->willReturn($managerMock);

        // DTO resource: the operation class (EmployeeApi) differs from the stateOptions entity class (Employee),
        // so the identifier-self link's fromClass is the resource class, not the entity class.
        $operation = (new Get())->withUriVariables([
            'id' => (new Link())->withFromClass(EmployeeApi::class)->withIdentifiers(['id']),
        ])->withStateOptions(new Options(entityClass: Employee::class))->withName('get')->withClass(EmployeeApi::class);

        // The reference fast-path must return before the item query is built, so item extensions never run.
        $extensionMock = $this->createMock(QueryItemExtensionInterface::class);
        $extensionMock->expects($this->never())->method('applyToItem');

        $dataProvider = new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
            [$extensionMock]
        );

        $this->assertSame($reference, $dataProvider->provide($operation, ['id' => 2], ['fetch_data' => false]));
    }

    public function testGetItemWithFetchDataFalseFallsBackToQueryForStateOptionsResourceWhenIdentifierIsNotADoctrineIdentifier(): void
    {
        $returnObject = new \stdClass();

        $queryMock = $this->createMock($this->getQueryClass());
        $queryMock->method('getOneOrNullResult')->willReturn($returnObject);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getQuery')->willReturn($queryMock);
        $queryBuilderMock->method('getRootAliases')->willReturn(['o']);

        // Even for a DTO resource, the entity-identifier guard holds: the resource exposes "uuid" as its
        // API identifier while the Doctrine identifier is "id", so getReference() cannot be built and we
        // must fall back to the link-resolving query.
        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->method('getIdentifierFieldNames')->willReturn(['id']);

        $repositoryMock = $this->createMock(EntityRepository::class);
        $repositoryMock->method('createQueryBuilder')->with('o')->willReturn($queryBuilderMock);

        $managerMock = $this->createMock(EntityManagerInterface::class);
        $managerMock->method('getClassMetadata')->willReturn($classMetadataMock);
        $managerMock->method('getRepository')->willReturn($repositoryMock);
        $managerMock->expects($this->never())->method('getReference');

        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->method('getManagerForClass')->willReturn($managerMock);

        // A no-op handleLinks mirrors what DoctrineOrmResourceCollectionMetadataFactory injects for a
        // stateOptions resource in production; the unit test bypasses that factory (see CollectionProviderTest).
        $operation = (new Get())->withUriVariables([
            'uuid' => (new Link())->withFromClass(EmployeeApi::class)->withIdentifiers(['uuid'])->withParameterName('uuid'),
        ])->withStateOptions(new Options(entityClass: Employee::class, handleLinks: static fn () => null))->withName('get')->withClass(EmployeeApi::class);

        $dataProvider = new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistryMock,
        );

        $this->assertSame($returnObject, $dataProvider->provide($operation, ['uuid' => '61817181-0ecc-42fb-a6e7-d97f2ddcb344'], ['fetch_data' => false]));
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
            ->willReturnCallback(static function ($name) use ($getTypeOfFieldExpectations) {
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
            ->willReturnCallback(static function ($name) use ($classMetadataExpectations) {
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
        $employeeClassMetadataMock->method('hasAssociation')->with('company')->willReturn(true);
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
        $class = \stdClass::class;
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
