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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PaginationExtensionTest extends TestCase
{
    public function testApplyToCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 40,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 20, '_page' => 2]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'page_parameter_name' => '_page',
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(40)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(40)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageZero()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 0,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 0, '_page' => 1]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'items_per_page' => 0,
            'page_parameter_name' => '_page',
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(0)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageZeroAndPage2()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page should not be greater than 1 if limit is equal to 0');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 0,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 0, '_page' => 2]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'items_per_page' => 0,
            'page_parameter_name' => '_page',
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(0)->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageLessThan0()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit should not be less than 0');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => -20,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => -20, '_page' => 2]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'items_per_page' => -20,
            'page_parameter_name' => '_page',
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(40)->willReturn($queryBuilderProphecy)->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(40)->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageTooHigh()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => true,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 301, '_page' => 2]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'page_parameter_name' => '_page',
            'maximum_items_per_page' => 300,
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(300)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(300)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionWithGraphql()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => 20,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true], [], [
            '_graphql' => true,
            '_graphql_collections_args' => ['Foo' => ['first' => 5, 'after' => 'OQ==']], // base64_encode('9')
        ]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(10)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(5)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionNoRequest()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $pagination = new Pagination(new RequestStack(), $resourceMetadataFactory);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(Argument::any())->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(Argument::any())->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionEmptyRequest()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(30)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionPaginationDisabled()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'enabled' => false,
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(Argument::any())->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(Argument::any())->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionWithMaximumItemsPerPage()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'maximum_items_per_page' => 80,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 80, 'page' => 1]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'client_enabled' => true,
            'client_items_per_page' => true,
            'maximum_items_per_page' => 50,
        ]);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(80)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testSupportsResult()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultNoRequest()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $pagination = new Pagination(new RequestStack(), $resourceMetadataFactory);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultEmptyRequest()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultClientNotAllowedToPaginate()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'enabled' => false,
            'client_enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultPaginationDisabled()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testGetResult()
    {
        $result = $this->getPaginationExtensionResult(false);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    public function testGetResultWithoutFetchJoinCollection()
    {
        $result = $this->getPaginationExtensionResult(false, false, false);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    public function testGetResultWithPartial()
    {
        $result = $this->getPaginationExtensionResult(true);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertNotInstanceOf(PaginatorInterface::class, $result);
    }

    public function testSimpleGetResult()
    {
        $result = $this->getPaginationExtensionResult(false, true);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    private function getPaginationExtensionResult(bool $partial = false, bool $legacy = false, bool $fetchJoinCollection = true)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        if (!$legacy) {
            $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], ['pagination_partial' => false, 'pagination_client_partial' => true, 'pagination_fetch_join_collection' => $fetchJoinCollection]));
        }

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['partial' => $partial]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory);

        $configuration = new Configuration();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn($configuration);

        $query = new Query($entityManagerProphecy->reveal());
        $query->setFirstResult(0);
        $query->setMaxResults(42);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootEntities()->willReturn([]);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilderProphecy->getDQLPart(Argument::that(function ($arg) {
            return \in_array($arg, ['having', 'orderBy', 'join'], true);
        }))->willReturn('');
        $queryBuilderProphecy->getMaxResults()->willReturn(42);

        $paginationExtension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );

        $args = [$queryBuilderProphecy->reveal()];

        if (!$legacy) {
            $args[] = 'Foo';
            $args[] = null;
        }

        return $paginationExtension->getResult(...$args);
    }
}
