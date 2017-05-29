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

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterCollectionFactory;
use ApiPlatform\Core\Api\FilterInterface;
use Psr\Container\ContainerInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class FilterCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group legacy
     * @expectedDeprecation The ApiPlatform\Core\Api\FilterCollection class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of Psr\Container\ContainerInterface instead.
     */
    public function testCreateFilterCollectionFromLocator()
    {
        $filter = $this->prophesize(FilterInterface::class)->reveal();

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('foo')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('foo')->willReturn($filter)->shouldBeCalled();
        $filterLocatorProphecy->has('bar')->willReturn(false)->shouldBeCalled();

        $filterCollection = (new FilterCollectionFactory(['foo', 'bar']))->createFilterCollectionFromLocator($filterLocatorProphecy->reveal());

        $this->assertInstanceOf(FilterCollection::class, $filterCollection);
        $this->assertArrayNotHasKey('bar', $filterCollection);
        $this->assertArrayHasKey('foo', $filterCollection);
        $this->assertInstanceOf(FilterInterface::class, $filterCollection['foo']);
        $this->assertEquals(new FilterCollection(['foo' => $filter]), $filterCollection);
    }
}
