<?php

/*
 *  This file is part of the API Platform project.
 *
 *  (c) Kévin Dunglas <dunglas@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Http;

use ApiPlatform\Core\Api\ItemDataProviderInterface;
use ApiPlatform\Core\Http\ItemDataProvider;

/**
 * @covers ApiPlatform\Core\Http\ItemDataProvider
 *
 * @author             Théo FIDRY <theo.fidry@gmail.com>
 */
class ItemDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testDecoratesProvider()
    {
        $resourceClass = 'App\Entity\Dummy';
        $id = 200;
        $operationName = 'get';
        $fetchData = true;
        $expectedItem = new \stdClass();

        $decoratedProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $decoratedProviderProphecy->getItem($resourceClass, $id, $operationName, $fetchData)->shouldBeCalledTimes(1);
        $decoratedProviderProphecy->getItem($resourceClass, $id, $operationName, $fetchData)->willReturn($expectedItem);
        /* @var ItemDataProviderInterface $decoratedProvider */
        $decoratedProvider = $decoratedProviderProphecy->reveal();

        $provider = new ItemDataProvider($decoratedProvider);
        $actualItem = $provider->getItem($resourceClass, $id, $operationName, $fetchData);

        $this->assertSame($expectedItem, $actualItem);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\NotFoundHttpException
     */
    public function testThrowExceptionWhenItemNotFound()
    {
        $resourceClass = 'App\Entity\Dummy';
        $id = 200;
        $operationName = 'get';
        $fetchData = true;

        $decoratedProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $decoratedProviderProphecy->getItem($resourceClass, $id, $operationName, $fetchData)->willReturn(null);
        /* @var ItemDataProviderInterface $decoratedProvider */
        $decoratedProvider = $decoratedProviderProphecy->reveal();

        $provider = new ItemDataProvider($decoratedProvider);
        $provider->getItem($resourceClass, $id, $operationName, $fetchData);
    }
}
