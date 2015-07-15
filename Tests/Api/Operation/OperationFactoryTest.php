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

use Dunglas\ApiBundle\Api\Operation\OperationFactory;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class OperationFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $operationFactory;
    private $resource;

    public function setUp()
    {
        $this->operationFactory = new OperationFactory();

        $prophecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $prophecy->getShortName()->willReturn('Foo');
        $this->resource = $prophecy->reveal();
    }

    public function testCreateCollectionOperationWithDefaultValues()
    {
        $operation = $this->operationFactory->createCollectionOperation($this->resource, ['GET', 'DELETE']);

        $this->assertEquals('/foos', $operation->getRoute()->getPath());
        $this->assertEquals(['GET', 'DELETE'], $operation->getRoute()->getMethods());

        $defaults = $operation->getRoute()->getDefaults();
        $this->assertEquals('DunglasApiBundle:Resource:cget', $defaults['_controller']);
        $this->assertEquals('Foo', $defaults['_resource']);

        $this->assertEquals('api_foos_cget', $operation->getRouteName());
        $this->assertEquals([], $operation->getContext());
    }

    public function testCreateCollectionOperationWithAllParameters()
    {
        $operation = $this->operationFactory->createCollectionOperation(
            $this->resource, ['GET', 'HEAD'], '/bar', 'AppBundle:Test:cget', 'baz', ['kevin' => 'dunglas']
        );

        $this->assertEquals('/bar', $operation->getRoute()->getPath());
        $this->assertEquals(['GET', 'HEAD'], $operation->getRoute()->getMethods());

        $defaults = $operation->getRoute()->getDefaults();
        $this->assertEquals('AppBundle:Test:cget', $defaults['_controller']);
        $this->assertEquals('Foo', $defaults['_resource']);

        $this->assertEquals('baz', $operation->getRouteName());
        $this->assertEquals(['kevin' => 'dunglas'], $operation->getContext());
    }

    public function testCreateItemOperationWithDefaultValues()
    {
        $operation = $this->operationFactory->createItemOperation($this->resource, ['GET', 'DELETE']);

        $this->assertEquals('/foos/{id}', $operation->getRoute()->getPath());
        $this->assertEquals(['GET', 'DELETE'], $operation->getRoute()->getMethods());

        $defaults = $operation->getRoute()->getDefaults();
        $this->assertEquals('DunglasApiBundle:Resource:get', $defaults['_controller']);
        $this->assertEquals('Foo', $defaults['_resource']);

        $this->assertEquals('api_foos_get', $operation->getRouteName());
        $this->assertEquals([], $operation->getContext());
    }

    public function testCreateItemOperationWithAllParameters()
    {
        $operation = $this->operationFactory->createItemOperation(
            $this->resource, ['GET', 'HEAD'], '/bar/{id}', 'AppBundle:Test:cget', 'baz', ['kevin' => 'dunglas']
        );

        $this->assertEquals('/bar/{id}', $operation->getRoute()->getPath());
        $this->assertEquals(['GET', 'HEAD'], $operation->getRoute()->getMethods());

        $defaults = $operation->getRoute()->getDefaults();
        $this->assertEquals('AppBundle:Test:cget', $defaults['_controller']);
        $this->assertEquals('Foo', $defaults['_resource']);

        $this->assertEquals('baz', $operation->getRouteName());
        $this->assertEquals(['kevin' => 'dunglas'], $operation->getContext());
    }
}
