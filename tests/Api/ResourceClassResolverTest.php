<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\ResourceClassResolver;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ResourceClassResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testGetResourceClassWithIntendedClassName()
    {
        $dummy = new Dummy();
        $dummy->setName('Smail');
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->getResourceClass($dummy, Dummy::class);
        $this->assertEquals($resourceClass, Dummy::class);
    }

    public function testGetResourceClassWithNoClassName()
    {
        $dummy = new Dummy();
        $dummy->setName('Smail');
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->getResourceClass($dummy, null);
        $this->assertEquals($resourceClass, Dummy::class);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No resource class found
     */
    public function testGetResourceClassWithWrongClassName()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClassResolver->getResourceClass(new \stdClass(), null);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No resource class found for object of type "ArrayIterator"
     */
    public function testGetResourceClassWithNoResourceClassName()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClassResolver->getResourceClass(new \ArrayIterator([]), null);
    }

    public function testIsResourceClassWithIntendedClassName()
    {
        $dummy = new Dummy();
        $dummy->setName('Smail');
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->isResourceClass(Dummy::class);
        $this->assertTrue($resourceClass);
    }

    public function testIsResourceClassWithWrongClassName()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([\ArrayIterator::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->isResourceClass('');
        $this->assertFalse($resourceClass);
    }
}
