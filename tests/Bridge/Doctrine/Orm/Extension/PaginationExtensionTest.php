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

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PaginationExtensionTest extends \PHPUnit_Framework_TestCase
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
}
