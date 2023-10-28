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
use ApiPlatform\Metadata\HttpOperation;
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
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ItemProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testGetItemSingleIdentifier(): void
    {
        $returnObject = new \stdClass();

        $context = ['foo' => 'bar', 'fetch_data' => true];
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->willReturn($returnObject)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilderProphecy->andWhere('o.identifier = :identifier_p1')->shouldBeCalled();
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);
        $queryBuilderProphecy->setParameter('identifier_p1', 1, Types::INTEGER)->shouldBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();

        $managerRegistryProphecy = $this->getManagerRegistry(OperationResource::class, [
            'identifier' => [
                'type' => Types::INTEGER,
            ],
        ], $queryBuilder);

        /** @var HttpOperation */
        $operation = (new Get())->withUriVariables([
            'identifier' => (new Link())->withFromClass(OperationResource::class)
            ->withIdentifiers([
                0 => 'identifier',
            ]),
        ])->withClass(OperationResource::class)->withName('get');

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), OperationResource::class, ['identifier' => 1], $operation, $context)->shouldBeCalled();

        $dataProvider = new ItemProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);

        $this->assertEquals($returnObject, $dataProvider->provide($operation, ['identifier' => 1], $context));
    }

    public function testGetItemDoubleIdentifier(): void
    {
        $returnObject = new \stdClass();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->willReturn($returnObject)->shouldBeCalled();

        // $exprProphecy = $this->prophesize(Expr::class);
        // $exprProphecy->eq('o.ida', ':id_ida')->willReturn($comparisonProphecy)->shouldBeCalled();
        // $exprProphecy->eq('o.idb', ':id_idb')->willReturn($comparisonProphecy)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilderProphecy->andWhere('o.idb = :idb_p1')->shouldBeCalled();
        $queryBuilderProphecy->andWhere('o.ida = :ida_p2')->shouldBeCalled();
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        /** @var HttpOperation */
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

        $queryBuilderProphecy->setParameter('idb_p1', 2, Types::INTEGER)->shouldBeCalled();
        $queryBuilderProphecy->setParameter('ida_p2', 1, Types::INTEGER)->shouldBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();

        $managerRegistryProphecy = $this->getManagerRegistry(OperationResource::class, [
            'ida' => [
                'type' => Types::INTEGER,
            ],
            'idb' => [
                'type' => Types::INTEGER,
            ],
        ], $queryBuilder);

        $context = [];
        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), OperationResource::class, ['ida' => 1, 'idb' => 2], $operation, $context)->shouldBeCalled();

        $dataProvider = new ItemProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);

        $this->assertEquals($returnObject, $dataProvider->provide($operation, ['ida' => 1, 'idb' => 2], $context));
    }

    public function testQueryResultExtension(): void
    {
        $returnObject = new \stdClass();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->andWhere('o.identifier = :identifier_p1')->shouldBeCalled();
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);
        $queryBuilderProphecy->setParameter('identifier_p1', 1, Types::INTEGER)->shouldBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();

        $managerRegistryProphecy = $this->getManagerRegistry(OperationResource::class, [
            'identifier' => [
                'type' => Types::INTEGER,
            ],
        ], $queryBuilder);

        /** @var HttpOperation */
        $operation = (new Get())->withUriVariables([
            'identifier' => (new Link())->withFromClass(OperationResource::class)->withIdentifiers([
                0 => 'identifier',
            ]),
        ])->withClass(OperationResource::class)->withName('get');

        $context = [];
        $extensionProphecy = $this->prophesize(QueryResultItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), OperationResource::class, ['identifier' => 1], $operation, $context)->shouldBeCalled();
        $extensionProphecy->supportsResult(OperationResource::class, $operation, $context)->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($queryBuilder, OperationResource::class, $operation, $context)->willReturn($returnObject)->shouldBeCalled();

        $dataProvider = new ItemProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);

        $this->assertEquals($returnObject, $dataProvider->provide($operation, ['identifier' => 1], $context));
    }

    public function testCannotCreateQueryBuilder(): void
    {
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
        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifierFieldNames()->willReturn(array_keys($identifierFields));

        foreach ($identifierFields as $name => $field) {
            $classMetadataProphecy->getTypeOfField($name)->willReturn($field['type']);
        }

        $platformProphecy = $this->prophesize(AbstractPlatform::class);

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->getDatabasePlatform()->willReturn($platformProphecy);

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder);

        $managerProphecy = $this->prophesize(EntityManagerInterface::class);
        $managerProphecy->getClassMetadata($resourceClass)->willReturn($classMetadataProphecy->reveal());
        $managerProphecy->getConnection()->willReturn($connectionProphecy);
        $managerProphecy->getRepository($resourceClass)->willReturn($repositoryProphecy->reveal());

        foreach ($classMetadatas as $class => $classMetadata) {
            $managerProphecy->getClassMetadata($class)->willReturn($classMetadata);
        }

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass($resourceClass)->willReturn($managerProphecy->reveal());

        return $managerRegistryProphecy;
    }

    public function testGetSubresourceFromProperty(): void
    {
        $returnObject = new \stdClass();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->willReturn($returnObject)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->join(Employee::class, 'm_a1', 'WITH', 'o.id = m_a1.company')->shouldBeCalled();
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);
        $queryBuilderProphecy->andWhere('m_a1.id = :id_p1')->shouldBeCalled();
        $queryBuilderProphecy->setParameter('id_p1', 1, Types::INTEGER)->shouldBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();

        $employeeClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $employeeClassMetadataProphecy->getAssociationMapping('company')->willReturn([
            'type' => ClassMetadataInfo::TO_ONE,
            'fieldName' => 'company',
        ]);
        $employeeClassMetadataProphecy->getTypeOfField('id')->willReturn(Types::INTEGER);

        $managerRegistryProphecy = $this->getManagerRegistry(Company::class, [
            'id' => [
                'type' => Types::INTEGER,
            ],
        ], $queryBuilder, [
            Employee::class => $employeeClassMetadataProphecy->reveal(),
        ]);

        $managerProphecy = $this->prophesize(EntityManagerInterface::class);
        $managerRegistryProphecy->getManagerForClass(Employee::class)->willReturn($managerProphecy->reveal());

        /** @var HttpOperation */
        $operation = (new Get())->withUriVariables([
            'employeeId' => (new Link())->withFromClass(Employee::class)
                ->withIdentifiers([
                    0 => 'id',
                ])->withFromProperty('company'),
        ])->withName('getCompany')->withClass(Company::class);

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Company::class, ['employeeId' => 1], $operation, [])->shouldBeCalled();

        $dataProvider = new ItemProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);

        $this->assertEquals($returnObject, $dataProvider->provide($operation, ['employeeId' => 1]));
    }

    public function testHandleLinksCallable(): void
    {
        $class = 'foo';
        $resourceMetadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $query = $this->createStub(AbstractQuery::class);
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
}
