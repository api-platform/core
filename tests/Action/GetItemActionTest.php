<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Action;

use ApiPlatform\Core\Action\GetItemAction;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class GetItemActionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetItem()
    {
        $result = new \stdClass();

        $dataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $dataProviderProphecy->getItem('Foo', 22, 'get', true)->willReturn($result);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);

        $action = new GetItemAction($dataProviderProphecy->reveal());
        $this->assertSame($result, $action($request, 22));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Not Found
     */
    public function testNotFound()
    {
        $dataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $dataProviderProphecy->getItem('Foo', 1312, 'get', true)->willReturn(null);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);

        $action = new GetItemAction($dataProviderProphecy->reveal());
        $action($request, 1312);
    }
}
