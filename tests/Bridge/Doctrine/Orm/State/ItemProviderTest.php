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

use ApiPlatform\Bridge\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Company;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
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
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ItemProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @requires PHP 8.0
     */
    public function testGetItemSingleIdentifier()
    {
        $context = ['foo' => 'bar', 'fetch_data' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

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

        $resourceMetadataFactoryProphecy->create(OperationResource::class)->willReturn(new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations(['get' => (new Get())->withUriVariables([
            'identifier' => (new Link())->withFromClass("ApiPlatform\Tests\Fixtures\TestBundle\Entity\OperationResource")
            ->withIdentifiers([
                0 => 'identifier',
            ]),
        ])]))]));

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), OperationResource::class, ['identifier' => 1], 'get', $context)->shouldBeCalled();

        $dataProvider = new ItemProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);

        $this->assertEquals([], $dataProvider->provide(OperationResource::class, ['identifier' => 1], 'get', $context));
    }

    /**
     * @requires PHP 8.0
     */
    public function testGetItemDoubleIdentifier()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->willReturn([])->shouldBeCalled();

        // $exprProphecy = $this->prophesize(Expr::class);
        // $exprProphecy->eq('o.ida', ':id_ida')->willReturn($comparisonProphecy)->shouldBeCalled();
        // $exprProphecy->eq('o.idb', ':id_idb')->willReturn($comparisonProphecy)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilderProphecy->andWhere('o.idb = :idb_p1')->shouldBeCalled();
        $queryBuilderProphecy->andWhere('o.ida = :ida_p2')->shouldBeCalled();
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        $resourceMetadataFactoryProphecy->create(OperationResource::class)->willReturn(new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations(['get' => (new Get())->withUriVariables([
            'ida' => (new Link())->withFromClass('ApiPlatform\Tests\Fixtures\TestBundle\Entity\OperationResource')
                ->withIdentifiers([
                    0 => 'ida',
                ]),
            'idb' => (new Link())->withFromClass('ApiPlatform\Tests\Fixtures\TestBundle\Entity\OperationResource')
                ->withIdentifiers([
                    0 => 'idb',
                ]),
        ])]))]));

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

        $context = [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];
        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), OperationResource::class, ['ida' => 1, 'idb' => 2], 'get', $context)->shouldBeCalled();

        $dataProvider = new ItemProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);

        $this->assertEquals([], $dataProvider->provide(OperationResource::class, ['ida' => 1, 'idb' => 2], 'get', $context));
    }

    /**
     * @requires PHP 8.0
     */
    public function testQueryResultExtension()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

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

        $context = [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];
        $extensionProphecy = $this->prophesize(QueryResultItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), OperationResource::class, ['identifier' => 1], 'get', $context)->shouldBeCalled();
        $extensionProphecy->supportsResult(OperationResource::class, 'get', $context)->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($queryBuilder, OperationResource::class, 'get', $context)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy->create(OperationResource::class)->willReturn(new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations(['get' => (new Get())->withUriVariables([
            'identifier' => (new Link())->withFromClass("ApiPlatform\Tests\Fixtures\TestBundle\Entity\OperationResource")->withIdentifiers([
                0 => 'identifier',
            ]),
        ])]))]));

        $dataProvider = new ItemProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);

        $this->assertEquals([], $dataProvider->provide(OperationResource::class, ['identifier' => 1], 'get', $context));
    }

    /**
     * @requires PHP 8.0
     */
    public function testSupportedClass()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $resourceMetadataFactoryProphecy->create(OperationResource::class)->willReturn(new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations(['get' => new Get()]))]));
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($entityManagerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryResultItemExtensionInterface::class);

        $dataProvider = new ItemProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertTrue($dataProvider->supports(OperationResource::class, [], 'get'));
    }

    /**
     * @requires PHP 8.0
     */
    public function testNotItemOperation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);

        $resourceMetadataFactoryProphecy->create(OperationResource::class)->willReturn(new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations(['getCollection' => new GetCollection()]))]));
        $managerRegistryProphecy->getManagerForClass(OperationResource::class)->willReturn($entityManagerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryResultItemExtensionInterface::class);

        $dataProvider = new ItemProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertFalse($dataProvider->supports(OperationResource::class, [], 'getCollection'));
    }

    /**
     * @requires PHP 8.0
     */
    public function testUnsupportedClass()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);

        $dataProvider = new ItemProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertFalse($dataProvider->supports(Dummy::class));
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
        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn([
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

        $itemProvider = new ItemProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $itemProvider->provide(OperationResource::class, ['id' => 1234], null, [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true]);
    }

    /**
     * Gets mocked metadata factories.
     */
    private function getMetadataFactories(string $resourceClass, array $identifiers): array
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $nameCollection = ['foobar'];

        foreach ($identifiers as $identifier) {
            $metadata = new ApiProperty();
            $metadata = $metadata->withIdentifier(true);
            $propertyMetadataFactoryProphecy->create($resourceClass, $identifier)->willReturn($metadata);

            $nameCollection[] = $identifier;
        }

        //random property to prevent the use of non-identifiers metadata while looping
        $propertyMetadataFactoryProphecy->create($resourceClass, 'foobar')->willReturn(new ApiProperty());

        $propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection($nameCollection));
        $resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata('OperationResource'));

        return [$propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal()];
    }

    /**
     * Gets a mocked manager registry.
     *
     * @param mixed $classMetadatas
     */
    private function getManagerRegistry(string $resourceClass, array $identifierFields, QueryBuilder $queryBuilder, $classMetadatas = [])
    {
        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn(array_keys($identifierFields));

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

    /**
     * @requires PHP 8.0
     */
    public function testGetSubresourceFromProperty()
    {
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->join(Employee::class, 'm_a1', 'with', 'o.id = m_a1.company')->shouldBeCalled();
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);
        $queryBuilderProphecy->andWhere('m_a1.id = :id_p1')->shouldBeCalled();
        $queryBuilderProphecy->setParameter('id_p1', 1, Types::INTEGER)->shouldBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();

        $employeeClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $employeeClassMetadataProphecy->getAssociationMapping('company')->willReturn([
            'type' => ClassMetadataInfo::TO_ONE,
        ]);
        $employeeClassMetadataProphecy->getTypeOfField('id')->willReturn(Types::INTEGER);

        $managerRegistryProphecy = $this->getManagerRegistry(Company::class, [
            'id' => [
                'type' => Types::INTEGER,
            ],
        ], $queryBuilder, [
            Employee::class => $employeeClassMetadataProphecy->reveal(),
        ]);

        $resourceMetadataFactoryProphecy->create(Company::class)->willReturn(new ResourceMetadataCollection(Company::class, [(new ApiResource())->withOperations(new Operations(['getCompany' => (new Get())->withUriVariables([
            'employeeId' => (new Link())->withFromClass(Employee::class)
                ->withIdentifiers([
                    0 => 'id',
                ])->withFromProperty('company'),
        ])]))]));

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Company::class, ['employeeId' => 1], 'getCompany', [])->shouldBeCalled();

        $dataProvider = new ItemProvider($resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);

        $this->assertEquals([], $dataProvider->provide(Company::class, ['employeeId' => 1], 'getCompany'));
    }
}
