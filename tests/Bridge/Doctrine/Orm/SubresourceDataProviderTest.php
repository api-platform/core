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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\SubresourceDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SubresourceDataProviderTest extends TestCase
{
    private function assertIdentifierManagerMethodCalls($managerProphecy)
    {
        $platformProphecy = $this->prophesize(AbstractPlatform::class);

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->getDatabasePlatform()->willReturn($platformProphecy);

        $managerProphecy->getConnection()->willReturn($connectionProphecy);
    }

    private function getMetadataProphecies(array $resourceClassesIdentifiers)
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        foreach ($resourceClassesIdentifiers as $resourceClass => $identifiers) {
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
        }

        return [$propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal()];
    }

    private function getManagerRegistryProphecy(QueryBuilder $queryBuilder, array $identifiers, string $resourceClass)
    {
        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn($identifiers);

        foreach ($identifiers as $id) {
            $classMetadataProphecy->getTypeOfField($id)->willReturn('interger');
        }

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getClassMetadata($resourceClass)->willReturn($classMetadataProphecy->reveal());
        $managerProphecy->getRepository($resourceClass)->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass($resourceClass)->willReturn($managerProphecy->reveal());

        return $managerRegistryProphecy->reveal();
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotSupportedException
     * @expectedExceptionMessage The given resource class is not a subresource.
     */
    public function testNotASubresource()
    {
        $identifiers = ['id'];
        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataProphecies([Dummy::class => $identifiers]);
        $queryBuilder = $this->prophesize(QueryBuilder::class)->reveal();
        $managerRegistry = $this->getManagerRegistryProphecy($queryBuilder, $identifiers, Dummy::class);

        $dataProvider = new SubresourceDataProvider($managerRegistry, $propertyNameCollectionFactory, $propertyMetadataFactory, []);

        $dataProvider->getSubresource(Dummy::class, ['id' => 1], []);
    }

    public function testGetSubresource()
    {
        $dql = 'SELECT relatedDummies_a2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a1 INNER JOIN id_a1.relatedDummies relatedDummies_a2 WHERE id_a1.id = :id_p1';

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->shouldBeCalled()->willReturn([]);

        $identifiers = ['id'];
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->setParameter('id_p1', 1)->shouldBeCalled()->willReturn($queryBuilder);
        $funcProphecy = $this->prophesize(Func::class);
        $func = $funcProphecy->reveal();

        $queryBuilder->andWhere($func)->shouldBeCalled()->willReturn($queryBuilder);

        $queryBuilder->getQuery()->shouldBeCalled()->willReturn($queryProphecy->reveal());

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->shouldBeCalled()->willReturn($queryBuilder->reveal());

        $managerProphecy = $this->prophesize(EntityManager::class);
        $managerProphecy->getRepository(RelatedDummy::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($managerProphecy);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn($identifiers);
        $classMetadataProphecy->getTypeOfField('id')->shouldBeCalled()->willReturn('integer');
        $classMetadataProphecy->getAssociationMapping('relatedDummies')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_MANY]);

        $managerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select('relatedDummies_a2')->shouldBeCalled()->willReturn($qb);
        $qb->from(Dummy::class, 'id_a1')->shouldBeCalled()->willReturn($qb);
        $qb->innerJoin('id_a1.relatedDummies', 'relatedDummies_a2')->shouldBeCalled()->willReturn($qb);
        $qb->andWhere('id_a1.id = :id_p1')->shouldBeCalled()->willReturn($qb);
        $qb->getDQL()->shouldBeCalled()->willReturn($dql);

        $exprProphecy = $this->prophesize(Expr::class);
        $exprProphecy->in('o', $dql)->willReturn($func)->shouldBeCalled();

        $qb->expr()->shouldBeCalled()->willReturn($exprProphecy->reveal());

        $managerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($qb->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);

        $context = ['property' => 'relatedDummies', 'identifiers' => [['id', Dummy::class]], 'collection' => true];

        $this->assertEquals([], $dataProvider->getSubresource(RelatedDummy::class, ['id' => 1], $context));
    }

    public function testGetSubSubresourceItem()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $identifiers = ['id'];
        $funcProphecy = $this->prophesize(Func::class);
        $func = $funcProphecy->reveal();

        // First manager (Dummy)
        $dummyDQL = 'SELECT relatedDummies_a3 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a2 INNER JOIN id_a2.relatedDummies relatedDummies_a3 WHERE id_a2.id = :id_p2';

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select('relatedDummies_a3')->shouldBeCalled()->willReturn($qb);
        $qb->from(Dummy::class, 'id_a2')->shouldBeCalled()->willReturn($qb);
        $qb->innerJoin('id_a2.relatedDummies', 'relatedDummies_a3')->shouldBeCalled()->willReturn($qb);
        $qb->andWhere('id_a2.id = :id_p2')->shouldBeCalled()->willReturn($qb);

        $dummyFunc = new Func('in', ['any']);

        $dummyExpProphecy = $this->prophesize(Expr::class);
        $dummyExpProphecy->in('relatedDummies_a1', $dummyDQL)->willReturn($dummyFunc)->shouldBeCalled();

        $qb->expr()->shouldBeCalled()->willReturn($dummyExpProphecy->reveal());

        $qb->getDQL()->shouldBeCalled()->willReturn($dummyDQL);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn($identifiers);
        $classMetadataProphecy->getTypeOfField('id')->shouldBeCalled()->willReturn('integer');
        $classMetadataProphecy->getAssociationMapping('relatedDummies')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_MANY]);

        $dummyManagerProphecy = $this->prophesize(EntityManager::class);
        $dummyManagerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($qb->reveal());
        $dummyManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($dummyManagerProphecy);

        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($dummyManagerProphecy->reveal());

        // Second manager (RelatedDummy)
        $relatedDQL = 'SELECT IDENTITY(relatedDummies_a1.thirdLevel) FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy relatedDummies_a1 WHERE relatedDummies_a1.id = :id_p1 AND relatedDummies_a1 IN(SELECT relatedDummies_a3 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a2 INNER JOIN id_a2.relatedDummies relatedDummies_a3 WHERE id_a2.id = :id_p2)';

        $rqb = $this->prophesize(QueryBuilder::class);
        $rqb->select('IDENTITY(relatedDummies_a1.thirdLevel)')->shouldBeCalled()->willReturn($rqb);
        $rqb->from(RelatedDummy::class, 'relatedDummies_a1')->shouldBeCalled()->willReturn($rqb);
        $rqb->andWhere('relatedDummies_a1.id = :id_p1')->shouldBeCalled()->willReturn($rqb);
        $rqb->andWhere($dummyFunc)->shouldBeCalled()->willReturn($rqb);
        $rqb->getDQL()->shouldBeCalled()->willReturn($relatedDQL);

        $relatedExpProphecy = $this->prophesize(Expr::class);
        $relatedExpProphecy->in('o', $relatedDQL)->willReturn($func)->shouldBeCalled();

        $rqb->expr()->shouldBeCalled()->willReturn($relatedExpProphecy->reveal());

        $rClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $rClassMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn($identifiers);
        $rClassMetadataProphecy->getTypeOfField('id')->shouldBeCalled()->willReturn('integer');
        $rClassMetadataProphecy->getAssociationMapping('thirdLevel')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_ONE]);

        $rDummyManagerProphecy = $this->prophesize(EntityManager::class);
        $rDummyManagerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($rqb->reveal());
        $rDummyManagerProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($rClassMetadataProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($rDummyManagerProphecy);

        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($rDummyManagerProphecy->reveal());

        $result = new \StdClass();
        // Origin manager (ThirdLevel)
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->shouldBeCalled()->willReturn($result);

        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $queryBuilder->andWhere($func)->shouldBeCalled()->willReturn($queryBuilder);

        $queryBuilder->getQuery()->shouldBeCalled()->willReturn($queryProphecy->reveal());
        $queryBuilder->setParameter('id_p1', 1)->shouldBeCalled()->willReturn($queryBuilder);
        $queryBuilder->setParameter('id_p2', 1)->shouldBeCalled()->willReturn($queryBuilder);

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->shouldBeCalled()->willReturn($queryBuilder->reveal());

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(ThirdLevel::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy->getManagerForClass(ThirdLevel::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataProphecies([Dummy::class => $identifiers, RelatedDummy::class => $identifiers]);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);

        $context = ['property' => 'thirdLevel', 'identifiers' => [['id', Dummy::class], ['relatedDummies', RelatedDummy::class]], 'collection' => false];

        $this->assertEquals($result, $dataProvider->getSubresource(ThirdLevel::class, ['id' => 1, 'relatedDummies' => 1], $context));
    }

    public function testQueryResultExtension()
    {
        $dql = 'SELECT relatedDummies_a2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a1 INNER JOIN id_a1.relatedDummies relatedDummies_a2 WHERE id_a1.id = :id_p1';

        $identifiers = ['id'];
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->setParameter('id_p1', 1)->shouldBeCalled()->willReturn($queryBuilder);
        $funcProphecy = $this->prophesize(Func::class);
        $func = $funcProphecy->reveal();

        $queryBuilder->andWhere($func)->shouldBeCalled()->willReturn($queryBuilder);

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->shouldBeCalled()->willReturn($queryBuilder->reveal());

        $managerProphecy = $this->prophesize(EntityManager::class);
        $managerProphecy->getRepository(RelatedDummy::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($managerProphecy);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn($identifiers);
        $classMetadataProphecy->getTypeOfField('id')->shouldBeCalled()->willReturn('integer');
        $classMetadataProphecy->getAssociationMapping('relatedDummies')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_MANY]);

        $managerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($managerProphecy);

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select('relatedDummies_a2')->shouldBeCalled()->willReturn($qb);
        $qb->from(Dummy::class, 'id_a1')->shouldBeCalled()->willReturn($qb);
        $qb->innerJoin('id_a1.relatedDummies', 'relatedDummies_a2')->shouldBeCalled()->willReturn($qb);
        $qb->andWhere('id_a1.id = :id_p1')->shouldBeCalled()->willReturn($qb);
        $qb->getDQL()->shouldBeCalled()->willReturn($dql);

        $exprProphecy = $this->prophesize(Expr::class);
        $exprProphecy->in('o', $dql)->willReturn($func)->shouldBeCalled();

        $qb->expr()->shouldBeCalled()->willReturn($exprProphecy->reveal());

        $managerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($qb->reveal());
        $this->assertIdentifierManagerMethodCalls($managerProphecy);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $extensionProphecy = $this->prophesize(QueryResultCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), RelatedDummy::class, null, Argument::type('array'))->shouldBeCalled();
        $extensionProphecy->supportsResult(RelatedDummy::class, null, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($queryBuilder, RelatedDummy::class, null, Argument::type('array'))->willReturn([])->shouldBeCalled();

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);

        $context = ['property' => 'relatedDummies', 'identifiers' => [['id', Dummy::class]], 'collection' => true];

        $this->assertEquals([], $dataProvider->getSubresource(RelatedDummy::class, ['id' => 1], $context));
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage The repository class must have a "createQueryBuilder" method.
     */
    public function testCannotCreateQueryBuilder()
    {
        $identifiers = ['id'];
        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);
        $dataProvider->getSubresource(Dummy::class, ['id' => 1], []);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotSupportedException
     */
    public function testThrowResourceClassNotSupportedException()
    {
        $identifiers = ['id'];
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);
        $dataProvider->getSubresource(Dummy::class, ['id' => 1], []);
    }
}
