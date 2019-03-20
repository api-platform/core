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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryResultCollectionExtensionInterface;
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
use Doctrine\ORM\Tools\Pagination\CountWalker;
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
    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacyConstruct()
    {
        $paginationExtension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            new RequestStack(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            true,
            true,
            true
        );

        self::assertInstanceOf(ContextAwareQueryResultCollectionExtensionInterface::class, $paginationExtension);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacyConstructWithBadArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "$enabled" argument is expected to be a bool.');

        new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            new RequestStack(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            'bad argument'
        );
    }

    public function testConstruct()
    {
        $paginationExtension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            new Pagination($this->prophesize(ResourceMetadataFactoryInterface::class)->reveal())
        );

        self::assertInstanceOf(ContextAwareQueryResultCollectionExtensionInterface::class, $paginationExtension);
    }

    public function testConstructWithBadResourceMetadataFactory()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The "$resourceMetadataFactory" argument is expected to be an implementation of the "%s" interface.', ResourceMetadataFactoryInterface::class));

        new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            new \stdClass(),
            new Pagination($this->prophesize(ResourceMetadataFactoryInterface::class)->reveal())
        );
    }

    public function testConstructWithBadPagination()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The "$pagination" argument is expected to be an instance of the "%s" class.', Pagination::class));

        new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            new \stdClass()
        );
    }

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

        $pagination = new Pagination($resourceMetadataFactory, [
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
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op', ['filters' => ['pagination' => true, 'itemsPerPage' => 20, '_page' => 2]]);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacyApplyToCollection()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 20, '_page' => 2]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 40,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(40)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(40)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            30,
            '_page'
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

        $pagination = new Pagination($resourceMetadataFactory, [
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
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op', ['filters' => ['pagination' => true, 'itemsPerPage' => 0, '_page' => 1]]);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacyApplyToCollectionWithItemPerPageZero()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 0, '_page' => 1]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 0,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(0)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            0,
            '_page'
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

        $pagination = new Pagination($resourceMetadataFactory, [
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
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op', ['filters' => ['pagination' => true, 'itemsPerPage' => 0, '_page' => 2]]);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacyApplyToCollectionWithItemPerPageZeroAndPage2()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page should not be greater than 1 if itemsPerPage is equal to 0');

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 0, '_page' => 2]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 0,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(0)->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            0,
            '_page'
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

        $pagination = new Pagination($resourceMetadataFactory, [
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
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op', ['filters' => ['pagination' => true, 'itemsPerPage' => -20, '_page' => 2]]);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacyApplyToCollectionWithItemPerPageLessThen0()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item per page parameter should not be less than 0');

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => -20, '_page' => 2]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => -20,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(40)->willReturn($queryBuilderProphecy)->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(40)->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            -20,
            '_page'
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

        $pagination = new Pagination($resourceMetadataFactory, [
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
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op', ['filters' => ['pagination' => true, 'itemsPerPage' => 301, '_page' => 2]]);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacyApplyToCollectionWithItemPerPageTooHigh()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 301, '_page' => 2]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => true,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(300)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(300)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            30,
            '_page',
            'pagination',
            'itemsPerPage',
            300
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

        $pagination = new Pagination($resourceMetadataFactory);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(10)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(5)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op', ['filters' => ['pagination' => true, 'first' => 5, 'after' => 'OQ=='], 'graphql' => true]);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacyApplyToCollectionWithGraphql()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true], [], [
            '_graphql' => true,
            '_graphql_collections_args' => ['Foo' => ['first' => 5, 'after' => 'OQ==']], // base64_encode('9')
        ]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => 20,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(10)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(5)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            30
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionNofilters()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $pagination = new Pagination($resourceMetadataFactory);

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

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     */
    public function testLegacyApplyToCollectionNoRequest()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(Argument::any())->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(Argument::any())->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            new RequestStack(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     */
    public function testLegacyApplyToCollectionEmptyRequest()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(30)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testApplyToCollectionPaginationDisabled()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $pagination = new Pagination($resourceMetadataFactory, [
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

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacyApplyToCollectionPaginationDisabled()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(Argument::any())->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(Argument::any())->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            false
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

        $pagination = new Pagination($resourceMetadataFactory, [
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
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op', ['filters' => ['pagination' => true, 'itemsPerPage' => 80, 'page' => 1]]);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacyApplyToCollectionWithMaximumItemsPerPage()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 80, 'page' => 1]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'maximum_items_per_page' => 80,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(80)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            true,
            true,
            30,
            'page',
            'pagination',
            'itemsPerPage',
            50
        );
        $extension->applyToCollection($queryBuilder, new QueryNameGenerator(), 'Foo', 'op');
    }

    public function testSupportsResult()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $pagination = new Pagination($resourceMetadataFactory);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     */
    public function testLegacySupportsResult()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     */
    public function testLegacySupportsResultNoRequest()
    {
        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            new RequestStack(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     */
    public function testLegacySupportsResultEmptyRequest()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultClientNotAllowedToPaginate()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $pagination = new Pagination($resourceMetadataFactory, [
            'enabled' => false,
            'client_enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op', ['filters' => ['pagination' => true]]));
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacySupportsResultClientNotAllowedToPaginate()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            false,
            false
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultPaginationDisabled()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $pagination = new Pagination($resourceMetadataFactory, [
            'enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     * @expectedDeprecation Passing "$enabled", "$clientEnabled", "$clientItemsPerPage", "$itemsPerPage", "$pageParameterName", "$enabledParameterName", "$itemsPerPageParameterName", "$maximumItemPerPage", "$partial", "$clientPartial", "$partialParameterName" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator" as third argument instead.
     */
    public function testLegacySupportsResultPaginationDisabled()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            false
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testGetResult()
    {
        $result = $this->getPaginationExtensionResult();

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    public function testGetResultWithoutDistinct()
    {
        $configuration = new Configuration();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn($configuration)->shouldBeCalled();

        $query = new Query($entityManagerProphecy->reveal());
        $query->setFirstResult(0);
        $query->setMaxResults(42);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootEntities()->willReturn([])->shouldBeCalled();
        $queryBuilderProphecy->getAllAliases()->willReturn(['o'])->shouldBeCalled();
        $queryBuilderProphecy->getQuery()->willReturn($query)->shouldBeCalled();
        $queryBuilderProphecy->getDQLPart(Argument::that(function ($arg) {
            return \in_array($arg, ['having', 'orderBy', 'join'], true);
        }))->willReturn('')->shouldBeCalled();
        $queryBuilderProphecy->getMaxResults()->willReturn(42)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $result = $this->getPaginationExtensionResult(false, false, true, $queryBuilder);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);

        $this->assertFalse($query->getHint(CountWalker::HINT_DISTINCT));
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     */
    public function testLegacyGetResult()
    {
        $result = $this->getLegacyPaginationExtensionResult();

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    public function testGetResultWithoutFetchJoinCollection()
    {
        $result = $this->getPaginationExtensionResult(false, false, false);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     */
    public function testLegacyGetResultWithoutFetchJoinCollection()
    {
        $result = $this->getLegacyPaginationExtensionResult(false, false, false);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    public function testGetResultWithPartial()
    {
        $result = $this->getPaginationExtensionResult(true);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertNotInstanceOf(PaginatorInterface::class, $result);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     */
    public function testLegacyGetResultWithPartial()
    {
        $result = $this->getLegacyPaginationExtensionResult(true);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertNotInstanceOf(PaginatorInterface::class, $result);
    }

    public function testSimpleGetResult()
    {
        $result = $this->getPaginationExtensionResult(false, true);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" as second argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead.
     * @expectedDeprecation Passing an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" as third argument of "ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "ApiPlatform\Core\DataProvider\Pagination" instead.
     */
    public function testLegacySimpleGetResult()
    {
        $result = $this->getLegacyPaginationExtensionResult(false, true);

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    private function getPaginationExtensionResult(bool $partial = false, bool $legacy = false, bool $fetchJoinCollection = true, QueryBuilder $queryBuilder = null)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        if (!$legacy) {
            $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], ['pagination_partial' => false, 'pagination_client_partial' => true, 'pagination_fetch_join_collection' => $fetchJoinCollection]));
        }

        $pagination = new Pagination($resourceMetadataFactory);

        $configuration = new Configuration();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn($configuration);

        if (null === $queryBuilder) {
            $query = new Query($entityManagerProphecy->reveal());
            $query->setFirstResult(0);
            $query->setMaxResults(42);

            $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
            $queryBuilderProphecy->getRootEntities()->willReturn([])->shouldBeCalled();
            $queryBuilderProphecy->getAllAliases()->willReturn([])->shouldBeCalled();
            $queryBuilderProphecy->getQuery()->willReturn($query)->shouldBeCalled();
            $queryBuilderProphecy->getDQLPart(Argument::that(function ($arg) {
                return \in_array($arg, ['having', 'orderBy', 'join'], true);
            }))->willReturn('')->shouldBeCalled();
            $queryBuilderProphecy->getMaxResults()->willReturn(42)->shouldBeCalled();
            $queryBuilder = $queryBuilderProphecy->reveal();
        }

        $paginationExtension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $resourceMetadataFactory,
            $pagination
        );

        $args = [$queryBuilder, null, null, ['filters' => ['partial' => $partial]]];

        if (!$legacy) {
            $args[1] = 'Foo';
            $args[2] = null;
        }

        return $paginationExtension->getResult(...$args);
    }

    private function getLegacyPaginationExtensionResult(bool $partial = false, bool $legacy = false, bool $fetchJoinCollection = true)
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['partial' => $partial]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        if (!$legacy) {
            $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], ['pagination_partial' => false, 'pagination_client_partial' => true, 'pagination_fetch_join_collection' => $fetchJoinCollection]))->shouldBeCalled();
        }

        $configuration = new Configuration();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn($configuration)->shouldBeCalled();

        $query = new Query($entityManagerProphecy->reveal());
        $query->setFirstResult(0);
        $query->setMaxResults(42);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootEntities()->willReturn([])->shouldBeCalled();
        $queryBuilderProphecy->getAllAliases()->willReturn([])->shouldBeCalled();
        $queryBuilderProphecy->getQuery()->willReturn($query)->shouldBeCalled();
        $queryBuilderProphecy->getDQLPart(Argument::that(function ($arg) {
            return \in_array($arg, ['having', 'orderBy', 'join'], true);
        }))->willReturn('')->shouldBeCalled();
        $queryBuilderProphecy->getMaxResults()->willReturn(42)->shouldBeCalled();

        $paginationExtension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $resourceMetadataFactoryProphecy->reveal()
        );

        $args = [$queryBuilderProphecy->reveal()];

        if (!$legacy) {
            $args[] = 'Foo';
            $args[] = null;
        }

        return $paginationExtension->getResult(...$args);
    }
}
