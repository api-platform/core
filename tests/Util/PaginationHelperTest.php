<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\tests\Util;

use ApiPlatform\Core\Util\PaginationHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * @author Jonathan Doelfs <jd@sodatech.com>
 */
class PaginationHelperTest extends \PHPUnit_Framework_TestCase
{
    private $resourceClass = 'someResourceClass';
    private $operationName = 'someOperationName';

    private $enabled = true;
    private $clientEnabled = false;
    private $clientItemsPerPage = false;
    private $itemsPerPage = 30;
    private $parameterNamePage = 'page';
    private $parameterNameEnabled = 'pagination';
    private $parameterNameItemsPerPage = 'itemsPerPage';
    private $maximumItemsPerPage = null;

    public function getPaginationHelper(array $collectionOperationData = null, array $requestQuery = null): PaginationHelper
    {
        $requestStack = new RequestStack();
        if (!is_null($requestQuery)) {
            $requestStack->push(new Request($requestQuery));
        }

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create($this->resourceClass)->willReturn(
            new ResourceMetadata('foo', '', null, [], [
                $this->operationName => $collectionOperationData,
            ])
        );

        return new PaginationHelper($requestStack, $resourceMetadataFactoryProphecy->reveal(), $this->resourceClass, $this->operationName, $this->enabled, $this->clientEnabled, $this->clientItemsPerPage, $this->itemsPerPage, $this->parameterNamePage, $this->parameterNameEnabled, $this->parameterNameItemsPerPage, $this->maximumItemsPerPage);
    }

    public function testIsResourcePaginationEnabled()
    {
        $paginationHelper = $this->getPaginationHelper();
        $this->assertEquals($this->enabled || $this->clientEnabled, $paginationHelper->isResourcePaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_enabled' => false, 'pagination_client_enabled' => false]);
        $this->assertFalse($paginationHelper->isResourcePaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_enabled' => true, 'pagination_client_enabled' => false]);
        $this->assertTrue($paginationHelper->isResourcePaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_enabled' => false, 'pagination_client_enabled' => true]);
        $this->assertTrue($paginationHelper->isResourcePaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_enabled' => true, 'pagination_client_enabled' => true]);
        $this->assertTrue($paginationHelper->isResourcePaginationEnabled());
    }

    public function testIsPaginationEnabled()
    {
        $paginationHelper = $this->getPaginationHelper();
        $this->assertEquals($this->enabled || $this->clientEnabled, $paginationHelper->isPaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_enabled' => false, 'pagination_client_enabled' => false]);
        $this->assertFalse($paginationHelper->isPaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_enabled' => true, 'pagination_client_enabled' => false]);
        $this->assertTrue($paginationHelper->isPaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_enabled' => false, 'pagination_client_enabled' => true]);
        $this->assertFalse($paginationHelper->isPaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_enabled' => false, 'pagination_client_enabled' => true], [$this->parameterNameEnabled => '1']);
        $this->assertTrue($paginationHelper->isPaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_enabled' => false, 'pagination_client_enabled' => true], [$this->parameterNameEnabled => '0']);
        $this->assertFalse($paginationHelper->isPaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_enabled' => true, 'pagination_client_enabled' => true], [$this->parameterNameEnabled => '0']);
        $this->assertFalse($paginationHelper->isPaginationEnabled());
    }

    public function testGetItemsPerPage()
    {
        $paginationHelper = $this->getPaginationHelper();
        $this->assertEquals($this->itemsPerPage, $paginationHelper->getItemsPerPage());

        $paginationHelper = $this->getPaginationHelper(['pagination_items_per_page' => 10, 'pagination_client_items_per_page' => false, 'maximum_items_per_page' => 20]);
        $this->assertEquals(10, $paginationHelper->getItemsPerPage());

        $paginationHelper = $this->getPaginationHelper(['pagination_items_per_page' => 20, 'pagination_client_items_per_page' => false, 'maximum_items_per_page' => 10]);
        $this->assertEquals(10, $paginationHelper->getItemsPerPage());

        $paginationHelper = $this->getPaginationHelper(['pagination_items_per_page' => 10, 'pagination_client_items_per_page' => true, 'maximum_items_per_page' => 40], [$this->parameterNameItemsPerPage => 30]);
        $this->assertEquals(30, $paginationHelper->getItemsPerPage());

        $paginationHelper = $this->getPaginationHelper(['pagination_items_per_page' => 10, 'pagination_client_items_per_page' => true, 'maximum_items_per_page' => 20], [$this->parameterNameItemsPerPage => 30]);
        $this->assertEquals(20, $paginationHelper->getItemsPerPage());

        $paginationHelper = $this->getPaginationHelper(['pagination_items_per_page' => 10, 'pagination_client_items_per_page' => true, 'maximum_items_per_page' => 20], [$this->parameterNameItemsPerPage => -2]);
        $this->assertEquals(1, $paginationHelper->getItemsPerPage());
    }

    public function testGetPage()
    {
        $paginationHelper = $this->getPaginationHelper();
        $this->assertEquals(1, $paginationHelper->getPage());

        $paginationHelper = $this->getPaginationHelper(null, [$this->parameterNamePage => 4]);
        $this->assertEquals(4, $paginationHelper->getPage());

        $paginationHelper = $this->getPaginationHelper(null, [$this->parameterNamePage => -2]);
        $this->assertEquals(1, $paginationHelper->getPage());
    }

    public function testIsClientPaginationEnabled()
    {
        $paginationHelper = $this->getPaginationHelper();
        $this->assertEquals($this->clientEnabled, $paginationHelper->isClientPaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['client_pagination_enabled' => true]);
        $this->assertTrue($paginationHelper->isClientPaginationEnabled());

        $paginationHelper = $this->getPaginationHelper(['client_pagination_enabled' => false]);
        $this->assertFalse($paginationHelper->isClientPaginationEnabled());
    }

    public function testIsClientItemsPerPageEnabled()
    {
        $paginationHelper = $this->getPaginationHelper();
        $this->assertEquals($this->clientItemsPerPage, $paginationHelper->isClientItemsPerPageEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_client_items_per_page' => true]);
        $this->assertTrue($paginationHelper->isClientItemsPerPageEnabled());

        $paginationHelper = $this->getPaginationHelper(['pagination_client_items_per_page' => false]);
        $this->assertFalse($paginationHelper->isClientItemsPerPageEnabled());
    }

    public function testGetMaximumItemsPerPage()
    {
        $paginationHelper = $this->getPaginationHelper();
        $this->assertEquals($this->maximumItemsPerPage, $paginationHelper->getMaximumItemsPerPage());

        $paginationHelper = $this->getPaginationHelper(['maximum_items_per_page' => 5]);
        $this->assertEquals(5, $paginationHelper->getMaximumItemsPerPage());
    }

    public function testGetParameterNamePage()
    {
        $paginationHelper = $this->getPaginationHelper();
        $this->assertEquals($this->parameterNamePage, $paginationHelper->getParameterNamePage());
    }

    public function testGetParameterNameEnabled()
    {
        $paginationHelper = $this->getPaginationHelper();
        $this->assertEquals($this->parameterNameEnabled, $paginationHelper->getParameterNameEnabled());
    }

    public function testGetParameterNameItemsPerPage()
    {
        $paginationHelper = $this->getPaginationHelper();
        $this->assertEquals($this->parameterNameItemsPerPage, $paginationHelper->getParameterNameItemsPerPage());
    }
}
