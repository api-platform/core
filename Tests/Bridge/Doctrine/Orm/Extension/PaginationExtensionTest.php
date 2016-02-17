<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Doctrine\Orm\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Bridge\Doctrine\Orm\Extension\PaginationExtension;
use Dunglas\ApiBundle\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class PaginationExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testApplyToCollection()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $managerRegistryMock = $this->prophesize(ManagerRegistry::class);
        $itemMetadataFactoryMock = $this->prophesize(ItemMetadataFactoryInterface::class);

        $extension = new PaginationExtension($managerRegistryMock, $requestStack, $itemMetadataFactoryMock);
        $extension->applyToCollection($this->resourceMock->reveal(), $this->queryBuilderMock->reveal());
    }

    public function testApplyToCollectionNoRequest()
    {
        /* @see PaginationExtension::applyToCollection */
        $this->requestStackMock->getCurrentRequest()->shouldBeCalledTimes(1);

        /* @see PaginationExtension::isPaginationEnabled */
        $this->resourceMock->getEnablePaginationParameter()->shouldNotBeCalled();
        $this->resourceMock->isClientAllowedToEnablePagination()->shouldNotBeCalled();

        /* @see PaginationExtension::getItemsPerPage */
        $this->resourceMock->isClientAllowedToChangeItemsPerPage()->shouldNotBeCalled();
        $this->resourceMock->getItemsPerPageParameter()->shouldNotBeCalled();

        /* @see PaginationExtension::getPage */
        $this->resourceMock->getPageParameter()->shouldNotBeCalled();

        /* @see PaginationExtension::applyToCollection */
        $this->queryBuilderMock->setFirstResult(Argument::any())->shouldNotBeCalled();
        $this->queryBuilderMock->setMaxResults(Argument::any())->shouldNotBeCalled();

        $extension = new PaginationExtension($this->managerRegistryMock->reveal(), $this->requestStackMock->reveal());
        $extension->applyToCollection($this->resourceMock->reveal(), $this->queryBuilderMock->reveal());
    }

    public function testApplyToCollectionEmptyRequest()
    {
        /** @var ObjectProphecy|Request $requestMock */
        $requestMock = $this->prophesize('Symfony\Component\HttpFoundation\Request');

        /* @see PaginationExtension::applyToCollection */
        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);

        /* @see PaginationExtension::isPaginationEnabled */
        $this->resourceMock->getEnablePaginationParameter()->willReturn('enablePagination')->shouldBeCalledTimes(1);
        $requestMock->get('enablePagination')->shouldBeCalledTimes(1);
        $this->resourceMock->isClientAllowedToEnablePagination()->willReturn(true)->shouldBeCalledTimes(1);
        $this->resourceMock->isPaginationEnabledByDefault()->willReturn(true)->shouldBeCalledTimes(1);

        /* @see PaginationExtension::getItemsPerPage */
        $this->resourceMock->isClientAllowedToChangeItemsPerPage()->willReturn(true)->shouldBeCalledTimes(1);
        $this->resourceMock->getItemsPerPageParameter()->willReturn('itemsPerPage')->shouldBeCalledTimes(1);
        $requestMock->get('itemsPerPage')->shouldBeCalledTimes(1);
        $this->resourceMock->getItemsPerPageByDefault()->willReturn(15)->shouldBeCalledTimes(1);

        /* @see PaginationExtension::getPage */
        $this->resourceMock->getPageParameter()->willReturn('page')->shouldBeCalledTimes(1);
        $requestMock->get('page', 1)->willReturn(1)->shouldBeCalledTimes(1);

        /* @see PaginationExtension::applyToCollection */
        $this->queryBuilderMock->setFirstResult(0)->willReturn($this->queryBuilderMock->reveal())->shouldBeCalledTimes(1);
        $this->queryBuilderMock->setMaxResults(15)->willReturn($this->queryBuilderMock->reveal())->shouldBeCalledTimes(1);

        $extension = new PaginationExtension($this->managerRegistryMock->reveal(), $this->requestStackMock->reveal());
        $extension->applyToCollection($this->resourceMock->reveal(), $this->queryBuilderMock->reveal());
    }

    public function testApplyToCollectionPaginationDisabled()
    {
        /** @var ObjectProphecy|Request $requestMock */
        $requestMock = $this->prophesize('Symfony\Component\HttpFoundation\Request');

        /* @see PaginationExtension::applyToCollection */
        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);

        /* @see PaginationExtension::isPaginationEnabled */
        $this->resourceMock->getEnablePaginationParameter()->willReturn('enablePagination')->shouldBeCalledTimes(1);
        $requestMock->get('enablePagination')->willReturn('true')->shouldBeCalledTimes(1);
        $this->resourceMock->isClientAllowedToEnablePagination()->willReturn(false)->shouldBeCalledTimes(1);
        $this->resourceMock->isPaginationEnabledByDefault()->willReturn(false)->shouldBeCalledTimes(1);

        /* @see PaginationExtension::getItemsPerPage */
        $this->resourceMock->isClientAllowedToChangeItemsPerPage()->shouldNotBeCalled();
        $this->resourceMock->getItemsPerPageParameter()->shouldNotBeCalled();
        $requestMock->get('itemsPerPage')->shouldNotBeCalled();

        /* @see PaginationExtension::getPage */
        $this->resourceMock->getPageParameter()->shouldNotBeCalled();
        $requestMock->get('page', 1)->shouldNotBeCalled();

        /* @see PaginationExtension::applyToCollection */
        $this->queryBuilderMock->setFirstResult(Argument::any())->shouldNotBeCalled();
        $this->queryBuilderMock->setMaxResults(Argument::any())->shouldNotBeCalled();

        $extension = new PaginationExtension($this->managerRegistryMock->reveal(), $this->requestStackMock->reveal());
        $extension->applyToCollection($this->resourceMock->reveal(), $this->queryBuilderMock->reveal());
    }

    public function testSupportsResult()
    {
        /** @var ObjectProphecy|Request $requestMock */
        $requestMock = $this->prophesize('Symfony\Component\HttpFoundation\Request');

        /* @see PaginationExtension::supportsResult */
        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);

        /* @see PaginationExtension::isPaginationEnabled */
        $this->resourceMock->getEnablePaginationParameter()->willReturn('enablePagination')->shouldBeCalledTimes(1);
        $requestMock->get('enablePagination')->willReturn('true')->shouldBeCalledTimes(1);
        $this->resourceMock->isClientAllowedToEnablePagination()->willReturn(true)->shouldBeCalledTimes(1);

        $extension = new PaginationExtension($this->managerRegistryMock->reveal(), $this->requestStackMock->reveal());
        $this->assertTrue($extension->supportsResult($this->resourceMock->reveal()));
    }

    public function testSupportsResultNoRequest()
    {
        /* @see PaginationExtension::supportsResult */
        $this->requestStackMock->getCurrentRequest()->shouldBeCalledTimes(1);

        /* @see PaginationExtension::isPaginationEnabled */
        $this->resourceMock->getEnablePaginationParameter()->shouldNotBeCalled();
        $this->resourceMock->isClientAllowedToEnablePagination()->shouldNotBeCalled();

        $extension = new PaginationExtension($this->managerRegistryMock->reveal(), $this->requestStackMock->reveal());
        $this->assertFalse($extension->supportsResult($this->resourceMock->reveal()));
    }

    public function testSupportsResultEmptyRequest()
    {
        /** @var ObjectProphecy|Request $requestMock */
        $requestMock = $this->prophesize('Symfony\Component\HttpFoundation\Request');

        /* @see PaginationExtension::supportsResult */
        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);

        /* @see PaginationExtension::isPaginationEnabled */
        $this->resourceMock->getEnablePaginationParameter()->willReturn('enablePagination')->shouldBeCalledTimes(1);
        $requestMock->get('enablePagination')->shouldBeCalledTimes(1);
        $this->resourceMock->isClientAllowedToEnablePagination()->willReturn(true)->shouldBeCalledTimes(1);
        $this->resourceMock->isPaginationEnabledByDefault()->willReturn(true)->shouldBeCalledTimes(1);

        $extension = new PaginationExtension($this->managerRegistryMock->reveal(), $this->requestStackMock->reveal());
        $this->assertTrue($extension->supportsResult($this->resourceMock->reveal()));
    }

    public function testSupportsResultClientNotAllowedToPaginate()
    {
        /** @var ObjectProphecy|Request $requestMock */
        $requestMock = $this->prophesize('Symfony\Component\HttpFoundation\Request');

        /* @see PaginationExtension::supportsResult */
        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);

        /* @see PaginationExtension::isPaginationEnabled */
        $this->resourceMock->getEnablePaginationParameter()->willReturn('enablePagination')->shouldBeCalledTimes(1);
        $requestMock->get('enablePagination')->willReturn('true')->shouldBeCalledTimes(1);
        $this->resourceMock->isClientAllowedToEnablePagination()->willReturn(false)->shouldBeCalledTimes(1);
        $this->resourceMock->isPaginationEnabledByDefault()->willReturn(true)->shouldBeCalledTimes(1);

        $extension = new PaginationExtension($this->managerRegistryMock->reveal(), $this->requestStackMock->reveal());
        $this->assertTrue($extension->supportsResult($this->resourceMock->reveal()));
    }

    public function testSupportsResultPaginationDisabled()
    {
        /** @var ObjectProphecy|Request $requestMock */
        $requestMock = $this->prophesize('Symfony\Component\HttpFoundation\Request');

        /* @see PaginationExtension::supportsResult */
        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);

        /* @see PaginationExtension::isPaginationEnabled */
        $this->resourceMock->getEnablePaginationParameter()->willReturn('enablePagination')->shouldBeCalledTimes(1);
        $requestMock->get('enablePagination')->willReturn('true')->shouldBeCalledTimes(1);
        $this->resourceMock->isClientAllowedToEnablePagination()->willReturn(false)->shouldBeCalledTimes(1);
        $this->resourceMock->isPaginationEnabledByDefault()->willReturn(false)->shouldBeCalledTimes(1);

        $extension = new PaginationExtension($this->managerRegistryMock->reveal(), $this->requestStackMock->reveal());
        $this->assertFalse($extension->supportsResult($this->resourceMock->reveal()));
    }

    public function testGetResult()
    {
    }
}
