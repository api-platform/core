<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Api;

use Dunglas\ApiBundle\Api\ResourceCollection;
use Dunglas\ApiBundle\Tests\Fixtures\DummyEntity;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $resourceProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $resourceProphecy->getEntityClass()->willReturn('Foo\Bar')->shouldBeCalled();
        $resourceProphecy->getShortName()->willReturn('Bar')->shouldBeCalled();
        $resource = $resourceProphecy->reveal();

        $resources = [$resource];

        $resourceCollection = new ResourceCollection();
        $resourceCollection->init($resources);

        $this->assertInstanceOf('Dunglas\ApiBundle\Api\ResourceCollectionInterface', $resourceCollection);
        $this->assertEquals($resource, $resourceCollection->getResourceForShortName('Bar'));
        $this->assertNull($resourceCollection->getResourceForShortName('Baz'));
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage A Resource class already exists for "Foo\Bar".
     */
    public function testInitResourceExists()
    {
        $resourceProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $resourceProphecy->getEntityClass()->willReturn('Foo\Bar')->shouldBeCalled();
        $resourceProphecy->getShortName()->willReturn('Bar')->shouldBeCalled();
        $resource = $resourceProphecy->reveal();

        $resources = [$resource, $resource];

        $resourceCollection = new ResourceCollection();
        $resourceCollection->init($resources);
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage A Resource class with the short name "Bar" already exists.
     */
    public function testInitShortNameExists()
    {
        $resource1Prophecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $resource1Prophecy->getEntityClass()->willReturn('Foo\Bar')->shouldBeCalled();
        $resource1Prophecy->getShortName()->willReturn('Bar')->shouldBeCalled();
        $resource1 = $resource1Prophecy->reveal();

        $resource2Prophecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $resource2Prophecy->getEntityClass()->willReturn('Foo\Baz')->shouldBeCalled();
        $resource2Prophecy->getShortName()->willReturn('Bar')->shouldBeCalled();
        $resource2 = $resource2Prophecy->reveal();

        $resources = [$resource1, $resource2];

        $resourceCollection = new ResourceCollection();
        $resourceCollection->init($resources);
    }

    public function testGetResourceForEntity()
    {
        $resourceProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $resourceProphecy->getEntityClass()->willReturn('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity')->shouldBeCalled();
        $resourceProphecy->getShortName()->willReturn('DummyEntity')->shouldBeCalled();
        $resource = $resourceProphecy->reveal();

        $resources = [$resource];

        $resourceCollection = new ResourceCollection();
        $resourceCollection->init($resources);

        $this->assertEquals($resource, $resourceCollection->getResourceForEntity('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity'));
        $this->assertEquals($resource, $resourceCollection->getResourceForEntity(new DummyEntity()));
        $this->assertNull($resourceCollection->getResourceForEntity('Foo\Bar'));
    }
}
