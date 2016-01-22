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
    private $resource2;

    public function setUp()
    {
        $this->operationFactory = new OperationFactory();

        $prophecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $prophecy->getShortName()->willReturn('Foo');
        $prophecy->getPluralizedName()->willReturn(null);
        $this->resource = $prophecy->reveal();

        $prophecy2 = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $prophecy2->getShortName()->willReturn('Bar');
        $prophecy2->getPluralizedName()->willReturn('Barz');
        $this->resource2 = $prophecy2->reveal();
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
            $this->resource,
            ['GET', 'HEAD'],
            '/bar/{baz}',
            'AppBundle:Test:cget',
            'qux',
            ['kevin' => 'dunglas'],
            ['baz' => '\d']
        );

        $this->assertEquals('/bar/{baz}', $operation->getRoute()->getPath());
        $this->assertEquals(['GET', 'HEAD'], $operation->getRoute()->getMethods());
        $this->assertArrayHasKey('baz', $operation->getRoute()->getRequirements());

        $defaults = $operation->getRoute()->getDefaults();
        $this->assertEquals('AppBundle:Test:cget', $defaults['_controller']);
        $this->assertEquals('Foo', $defaults['_resource']);

        $this->assertEquals('qux', $operation->getRouteName());
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
            $this->resource,
            ['GET', 'HEAD'],
            '/bar/{id}',
            'AppBundle:Test:cget',
            'baz',
            ['kevin' => 'dunglas'],
            ['id' => '\d']
        );

        $this->assertEquals('/bar/{id}', $operation->getRoute()->getPath());
        $this->assertEquals(['GET', 'HEAD'], $operation->getRoute()->getMethods());
        $this->assertArrayHasKey('id', $operation->getRoute()->getRequirements());

        $defaults = $operation->getRoute()->getDefaults();
        $this->assertEquals('AppBundle:Test:cget', $defaults['_controller']);
        $this->assertEquals('Foo', $defaults['_resource']);

        $this->assertEquals('baz', $operation->getRouteName());
        $this->assertEquals(['kevin' => 'dunglas'], $operation->getContext());
    }

    public function testCreateCollectionOperationWithCustomPluralizedName()
    {
        $operation = $this->operationFactory->createCollectionOperation($this->resource2, ['GET', 'DELETE']);

        $this->assertEquals('/barz', $operation->getRoute()->getPath());
        $this->assertEquals(['GET', 'DELETE'], $operation->getRoute()->getMethods());

        $defaults = $operation->getRoute()->getDefaults();
        $this->assertEquals('DunglasApiBundle:Resource:cget', $defaults['_controller']);
        $this->assertEquals('Bar', $defaults['_resource']);

        $this->assertEquals('api_barz_cget', $operation->getRouteName());
        $this->assertEquals([], $operation->getContext());
    }
}
