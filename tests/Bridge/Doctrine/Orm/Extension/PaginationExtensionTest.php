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
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
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

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Page should not be greater than 1 if itemsPegPage is equal to 0
     */
    public function testApplyToCollectionWithItemPerPageZeroAndPage2()
    {
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

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Item per page parameter should not be less than 0
     */
    public function testApplyToCollectionWithItemPerPageLessThen0()
    {
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

    public function testApplyToCollectionNoRequest()
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

    public function testApplyToCollectionEmptyRequest()
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

    public function testSupportsResultNoRequest()
    {
        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            new RequestStack(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultEmptyRequest()
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
        $result = $this->getPaginationExtensionResult(false);

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

    private function getPaginationExtensionResult(bool $partial = false, bool $legacy = false)
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['partial' => $partial]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        if (!$legacy) {
            $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], ['pagination_partial' => false, 'pagination_client_partial' => true]))->shouldBeCalled();
        }

        $configuration = new Configuration();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConfiguration()->willReturn($configuration)->shouldBeCalled();

        $query = new Query($entityManagerProphecy->reveal());
        $query->setFirstResult(0);
        $query->setMaxResults(42);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootEntities()->willReturn([])->shouldBeCalled();
        $queryBuilderProphecy->getQuery()->willReturn($query)->shouldBeCalled();
        $queryBuilderProphecy->getDQLPart(Argument::that(function ($arg) {
            return in_array($arg, ['having', 'orderBy', 'join'], true);
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
