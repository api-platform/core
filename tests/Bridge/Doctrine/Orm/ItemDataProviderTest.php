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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\ItemDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Prophecy\Argument;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ItemDataProviderTest extends \PHPUnit_Framework_TestCase
{
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

        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataFactories(Dummy::class, [
            'id',
        ]);
        $managerRegistry = $this->getManagerRegistry(Dummy::class, [
            'id' => [
                'type' => DBALType::INTEGER,
            ],
        ], $queryBuilder);

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Dummy::class, ['id' => 1], 'foo', $context)->shouldBeCalled();

        $dataProvider = new ItemDataProvider($managerRegistry, $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);

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

        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataFactories(Dummy::class, [
            'ida',
            'idb',
        ]);
        $managerRegistry = $this->getManagerRegistry(Dummy::class, [
            'ida' => [
                'type' => DBALType::INTEGER,
            ],
            'idb' => [
                'type' => DBALType::INTEGER,
            ],
        ], $queryBuilder);

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Dummy::class, ['ida' => 1, 'idb' => 2], 'foo', [])->shouldBeCalled();

        $dataProvider = new ItemDataProvider($managerRegistry, $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);

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

        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataFactories(Dummy::class, [
            'id',
        ]);
        $managerRegistry = $this->getManagerRegistry(Dummy::class, [
            'id' => [
                'type' => DBALType::INTEGER,
            ],
        ], $queryBuilder);

        $extensionProphecy = $this->prophesize(QueryResultItemExtensionInterface::class);
        $extensionProphecy->applyToItem($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Dummy::class, ['id' => 1], 'foo', [])->shouldBeCalled();
        $extensionProphecy->supportsResult(Dummy::class, 'foo')->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($queryBuilder)->willReturn([])->shouldBeCalled();

        $dataProvider = new ItemDataProvider($managerRegistry, $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);

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

        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataFactories(Dummy::class, [
            'id',
        ]);

        $dataProvider = new ItemDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);
        $dataProvider->getItem(Dummy::class, 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage The repository class must have a "createQueryBuilder" method.
     */
    public function testCannotCreateQueryBuilder()
    {
        $repositoryProphecy = $this->prophesize(ObjectRepository::class);
        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn([
            'id',
        ]);
        $classMetadataProphecy->getTypeOfField('id')->willReturn(DBALType::INTEGER);

        $platformProphecy = $this->prophesize(AbstractPlatform::class);

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->getDatabasePlatform()->willReturn($platformProphecy);

        $managerProphecy = $this->prophesize(EntityManagerInterface::class);
        $managerProphecy->getClassMetadata(Dummy::class)->willReturn($classMetadataProphecy->reveal());
        $managerProphecy->getConnection()->willReturn($connectionProphecy);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal());

        $extensionProphecy = $this->prophesize(QueryItemExtensionInterface::class);

        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataFactories(Dummy::class, [
            'id',
        ]);

        (new ItemDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]))->getItem(Dummy::class, 'foo');
    }

    /**
     * Gets mocked metadata factories.
     *
     * @param string $resourceClass
     * @param array  $identifiers
     *
     * @return array
     */
    private function getMetadataFactories(string $resourceClass, array $identifiers): array
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $nameCollection = ['foobar'];

        foreach ($identifiers as $identifier) {
            $metadata = new PropertyMetadata();
            $metadata = $metadata->withIdentifier(true);
            $propertyMetadataFactoryProphecy->create($resourceClass, $identifier)->willReturn($metadata);

            $nameCollection[] = $identifier;
        }

        //random property to prevent the use of non-identifiers metadata while looping
        $propertyMetadataFactoryProphecy->create($resourceClass, 'foobar')->willReturn(new PropertyMetadata());

        $propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection($nameCollection));

        return [$propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal()];
    }

    /**
     * Gets a mocked manager registry.
     *
     * @param string       $resourceClass
     * @param array        $identifierFields
     * @param QueryBuilder $queryBuilder
     *
     * @return ManagerRegistry
     */
    private function getManagerRegistry(string $resourceClass, array $identifierFields, QueryBuilder $queryBuilder): ManagerRegistry
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

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal());

        return $managerRegistryProphecy->reveal();
    }
}
