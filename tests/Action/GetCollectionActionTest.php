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

use ApiPlatform\Core\Action\GetCollectionAction;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class GetCollectionActionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCollection()
    {
        $result = new \stdClass();

        $dataProviderProphecy = $this->prophesize(CollectionDataProviderInterface::class);
        $dataProviderProphecy->getCollection('Foo', 'get')->willReturn($result);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);

        $action = new GetCollectionAction($dataProviderProphecy->reveal());
        $this->assertSame($result, $action($request));
    }
}
