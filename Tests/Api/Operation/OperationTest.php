<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Api\Operation;

use Dunglas\ApiBundle\Api\Operation\Operation;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class OperationTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $prophecy = $this->prophesize('Symfony\Component\Routing\Route');
        $route = $prophecy->reveal();

        $operation = new Operation($route, 'foo', ['bar' => 'baz']);

        $this->assertInstanceOf('Dunglas\ApiBundle\Api\Operation\OperationInterface', $operation);
        $this->assertEquals($route, $operation->getRoute());
        $this->assertEquals('foo', $operation->getRouteName());
        $this->assertEquals(['bar' => 'baz'], $operation->getContext());
    }
}
