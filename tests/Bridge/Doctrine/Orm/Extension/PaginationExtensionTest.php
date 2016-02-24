<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Core\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ItemMetadata;
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

        $itemMetadataFactoryProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 40,
        ];
        $itemMetadataFactoryProphecy->create('Foo')->willReturn(new ItemMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $itemMetadataFactory = $itemMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(40)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(40)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $itemMetadataFactory,
            true,
            false,
            false,
            30,
            '_page'
        );
        $extension->applyToCollection($queryBuilder, 'Foo', 'op');
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
            $this->prophesize(ItemMetadataFactoryInterface::class)->reveal()
        );
        $extension->applyToCollection($queryBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionEmptyRequest()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $itemMetadataFactoryProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $itemMetadataFactoryProphecy->create('Foo')->willReturn(new ItemMetadata(null, null, null, [], []))->shouldBeCalled();
        $itemMetadataFactory = $itemMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(0)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->setMaxResults(30)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $itemMetadataFactory
        );
        $extension->applyToCollection($queryBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionPaginationDisabled()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $itemMetadataFactoryProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $itemMetadataFactoryProphecy->create('Foo')->willReturn(new ItemMetadata(null, null, null, [], []))->shouldBeCalled();
        $itemMetadataFactory = $itemMetadataFactoryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->setFirstResult(Argument::any())->shouldNotBeCalled();
        $queryBuilderProphecy->setMaxResults(Argument::any())->shouldNotBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $itemMetadataFactory,
            false
        );
        $extension->applyToCollection($queryBuilder, 'Foo', 'op');
    }

    public function testSupportsResult()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $itemMetadataFactoryProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $itemMetadataFactoryProphecy->create('Foo')->willReturn(new ItemMetadata(null, null, null, [], []))->shouldBeCalled();
        $itemMetadataFactory = $itemMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $itemMetadataFactory
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultNoRequest()
    {
        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            new RequestStack(),
            $this->prophesize(ItemMetadataFactoryInterface::class)->reveal()
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultEmptyRequest()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $itemMetadataFactoryProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $itemMetadataFactoryProphecy->create('Foo')->willReturn(new ItemMetadata(null, null, null, [], []))->shouldBeCalled();
        $itemMetadataFactory = $itemMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $itemMetadataFactory
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultClientNotAllowedToPaginate()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true]));

        $itemMetadataFactoryProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $itemMetadataFactoryProphecy->create('Foo')->willReturn(new ItemMetadata(null, null, null, [], []))->shouldBeCalled();
        $itemMetadataFactory = $itemMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $itemMetadataFactory,
            false,
            false
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultPaginationDisabled()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $itemMetadataFactoryProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $itemMetadataFactoryProphecy->create('Foo')->willReturn(new ItemMetadata(null, null, null, [], []))->shouldBeCalled();
        $itemMetadataFactory = $itemMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $requestStack,
            $itemMetadataFactory,
            false
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }
}
