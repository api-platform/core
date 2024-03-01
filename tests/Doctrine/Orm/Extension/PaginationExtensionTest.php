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

namespace ApiPlatform\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Doctrine\Orm\AbstractPaginator;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PaginationExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        $paginationExtension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            new Pagination()
        );

        self::assertInstanceOf(QueryResultCollectionExtensionInterface::class, $paginationExtension);
    }

    public function testApplyToCollection(): void
    {
        $pagination = new Pagination([
            'page_parameter_name' => '_page',
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(40)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(40)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', new GetCollection(paginationEnabled: true, paginationClientEnabled: true, paginationItemsPerPage: 40), ['filters' => ['pagination' => true, 'itemsPerPage' => 20, '_page' => 2]]);
    }

    public function testApplyToCollectionWithItemPerPageZero(): void
    {
        $pagination = new Pagination([
            'items_per_page' => 0,
            'page_parameter_name' => '_page',
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(0)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', new GetCollection(paginationEnabled: true, paginationClientEnabled: true, paginationItemsPerPage: 0), ['filters' => ['pagination' => true, 'itemsPerPage' => 0, '_page' => 1]]);
    }

    public function testApplyToCollectionWithItemPerPageZeroAndPage2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page should not be greater than 1 if limit is equal to 0');

        $pagination = new Pagination([
            'items_per_page' => 0,
            'page_parameter_name' => '_page',
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(0)->shouldNotBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', new GetCollection(paginationEnabled: true, paginationClientEnabled: true, paginationItemsPerPage: 0), ['filters' => ['pagination' => true, 'itemsPerPage' => 0, '_page' => 2]]);
    }

    public function testApplyToCollectionWithItemPerPageLessThan0(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit should not be less than 0');

        $pagination = new Pagination([
            'items_per_page' => -20,
            'page_parameter_name' => '_page',
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(40)->willReturn($queryBuilderProphecy)->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(40)->shouldNotBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', new GetCollection(paginationEnabled: true, paginationClientEnabled: true, paginationItemsPerPage: -20), ['filters' => ['pagination' => true, 'itemsPerPage' => -20, '_page' => 2]]);
    }

    public function testApplyToCollectionWithItemPerPageTooHigh(): void
    {
        $pagination = new Pagination([
            'page_parameter_name' => '_page',
            'maximum_items_per_page' => 300,
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(300)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(300)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', new GetCollection(paginationEnabled: true, paginationClientEnabled: true, paginationClientItemsPerPage: true), ['filters' => ['pagination' => true, 'itemsPerPage' => 301, '_page' => 2]]);
    }

    public function testApplyToCollectionWithGraphql(): void
    {
        $pagination = new Pagination();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(10)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(5)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', new GetCollection(paginationEnabled: true, paginationClientEnabled: true, paginationItemsPerPage: 20), ['filters' => ['pagination' => true, 'first' => 5, 'after' => 'OQ=='], 'graphql_operation_name' => 'query']);
    }

    public function testApplyToCollectionNofilters(): void
    {
        $pagination = new Pagination();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(30)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', new GetCollection());
    }

    public function testApplyToCollectionPaginationDisabled(): void
    {
        $pagination = new Pagination([
            'enabled' => false,
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(Argument::any())->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(Argument::any())->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', new GetCollection());
    }

    public function testApplyToCollectionGraphQlPaginationDisabled(): void
    {
        $pagination = new Pagination([], [
            'enabled' => false,
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(Argument::any())->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(Argument::any())->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', new GetCollection(), ['graphql_operation_name' => 'op']);
    }

    public function testApplyToCollectionWithMaximumItemsPerPage(): void
    {
        $pagination = new Pagination([
            'client_enabled' => true,
            'client_items_per_page' => true,
            'maximum_items_per_page' => 50,
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(80)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', new GetCollection(paginationEnabled: true, paginationClientEnabled: true, paginationMaximumItemsPerPage: 80), ['filters' => ['pagination' => true, 'itemsPerPage' => 80, 'page' => 1]]);
    }

    public function testSupportsResult(): void
    {
        $pagination = new Pagination();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $this->assertTrue($extension->supportsResult('Foo', new GetCollection()));
    }

    public function testSupportsResultClientNotAllowedToPaginate(): void
    {
        $pagination = new Pagination([
            'enabled' => false,
            'client_enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', new GetCollection(), ['filters' => ['pagination' => true]]));
    }

    public function testSupportsResultPaginationDisabled(): void
    {
        $pagination = new Pagination([
            'enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', new GetCollection()));
    }

    public function testSupportsResultGraphQlPaginationDisabled(): void
    {
        $pagination = new Pagination([], [
            'enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', new GetCollection(), ['graphql_operation_name' => 'op']));
    }

    public function testGetResult(): void
    {
        $dummyMetadata = new ClassMetadata(Dummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn(new Configuration());
        $entityManagerProphecy->getClassMetadata(Dummy::class)->willReturn($dummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('o');
        $queryBuilder->from(Dummy::class, 'o');
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults(42);

        $query = new Query($entityManagerProphecy->reveal());
        $entityManagerProphecy->createQuery($queryBuilder->getDQL())->willReturn($query);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);

        $paginationExtension = new PaginationExtension(
            $managerRegistryProphecy->reveal(),
            new Pagination()
        );

        $result = $paginationExtension->getResult($queryBuilder, Dummy::class, new GetCollection());

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    public function testGetResultWithoutDistinct(): void
    {
        $dummyMetadata = new ClassMetadata(Dummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn(new Configuration());
        $entityManagerProphecy->getClassMetadata(Dummy::class)->willReturn($dummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('o');
        $queryBuilder->from(Dummy::class, 'o');
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults(42);

        $query = new Query($entityManagerProphecy->reveal());
        $entityManagerProphecy->createQuery($queryBuilder->getDQL())->willReturn($query);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);

        $paginationExtension = new PaginationExtension(
            $managerRegistryProphecy->reveal(),
            new Pagination()
        );

        $result = $paginationExtension->getResult($queryBuilder, Dummy::class, new GetCollection());

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);

        $this->assertFalse($query->getHint(CountWalker::HINT_DISTINCT));
    }

    /**
     * @dataProvider fetchJoinCollectionProvider
     */
    public function testGetResultWithFetchJoinCollection(bool $paginationFetchJoinCollection, array $context, bool $expected): void
    {
        $dummyMetadata = new ClassMetadata(Dummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn(new Configuration());
        $entityManagerProphecy->getClassMetadata(Dummy::class)->willReturn($dummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('o');
        $queryBuilder->from(Dummy::class, 'o');
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults(42);

        $query = new Query($entityManagerProphecy->reveal());
        $entityManagerProphecy->createQuery($queryBuilder->getDQL())->willReturn($query);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);

        $paginationExtension = new PaginationExtension(
            $managerRegistryProphecy->reveal(),
            new Pagination()
        );

        $result = $paginationExtension->getResult($queryBuilder, Dummy::class, new GetCollection(paginationFetchJoinCollection: $paginationFetchJoinCollection), $context);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);

        $doctrinePaginatorReflectionProperty = new \ReflectionProperty(AbstractPaginator::class, 'paginator');
        $doctrinePaginatorReflectionProperty->setAccessible(true);

        $doctrinePaginator = $doctrinePaginatorReflectionProperty->getValue($result);
        $this->assertSame($expected, $doctrinePaginator->getFetchJoinCollection());
    }

    public static function fetchJoinCollectionProvider(): array
    {
        return [
            'collection disabled' => [false, ['operation_name' => 'get'], false],
            'collection enabled' => [true, ['operation_name' => 'get'], true],
            'graphql disabled' => [false, ['graphql_operation_name' => 'query'], false],
            'graphql enabled' => [true, ['graphql_operation_name' => 'query'], true],
        ];
    }

    /**
     * @dataProvider fetchUseOutputWalkersProvider
     */
    public function testGetResultWithUseOutputWalkers(bool $paginationUseOutputWalkers, array $context, bool $expected): void
    {
        $dummyMetadata = new ClassMetadata(Dummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn(new Configuration());
        $entityManagerProphecy->getClassMetadata(Dummy::class)->willReturn($dummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('o');
        $queryBuilder->from(Dummy::class, 'o');
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults(42);

        $query = new Query($entityManagerProphecy->reveal());
        $entityManagerProphecy->createQuery($queryBuilder->getDQL())->willReturn($query);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);

        $paginationExtension = new PaginationExtension(
            $managerRegistryProphecy->reveal(),
            new Pagination()
        );

        $result = $paginationExtension->getResult($queryBuilder, Dummy::class, new GetCollection(paginationUseOutputWalkers: $paginationUseOutputWalkers), $context);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);

        $doctrinePaginatorReflectionProperty = new \ReflectionProperty(AbstractPaginator::class, 'paginator');
        $doctrinePaginatorReflectionProperty->setAccessible(true);

        $doctrinePaginator = $doctrinePaginatorReflectionProperty->getValue($result);
        $this->assertSame($expected, $doctrinePaginator->getUseOutputWalkers());
    }

    public static function fetchUseOutputWalkersProvider(): array
    {
        return [
            'collection disabled' => [false, ['operation_name' => 'get'], false],
            'collection enabled' => [true, ['operation_name' => 'get'], true],
            'graphql disabled' => [false, ['graphql_operation_name' => 'query'], false],
            'graphql enabled' => [true, ['graphql_operation_name' => 'query'], true],
        ];
    }

    public function testGetResultWithPartial(): void
    {
        $dummyMetadata = new ClassMetadata(Dummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn(new Configuration());
        $entityManagerProphecy->getClassMetadata(Dummy::class)->willReturn($dummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('o');
        $queryBuilder->from(Dummy::class, 'o');
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults(42);

        $query = new Query($entityManagerProphecy->reveal());
        $entityManagerProphecy->createQuery($queryBuilder->getDQL())->willReturn($query);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);

        $paginationExtension = new PaginationExtension(
            $managerRegistryProphecy->reveal(),
            new Pagination()
        );

        $result = $paginationExtension->getResult($queryBuilder, Dummy::class, new GetCollection(paginationPartial: true));

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertNotInstanceOf(PaginatorInterface::class, $result);
    }

    public function testSimpleGetResult(): void
    {
        $dummyMetadata = new ClassMetadata(Dummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn(new Configuration());
        $entityManagerProphecy->getClassMetadata(Dummy::class)->willReturn($dummyMetadata);

        $queryBuilder = new QueryBuilder($entityManagerProphecy->reveal());
        $queryBuilder->select('o');
        $queryBuilder->from(Dummy::class, 'o');
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults(42);

        $query = new Query($entityManagerProphecy->reveal());
        $entityManagerProphecy->createQuery($queryBuilder->getDQL())->willReturn($query);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($entityManagerProphecy);

        $paginationExtension = new PaginationExtension(
            $managerRegistryProphecy->reveal(),
            new Pagination()
        );

        $result = $paginationExtension->getResult($queryBuilder);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }
}
