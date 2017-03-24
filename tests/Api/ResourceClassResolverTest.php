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

use ApiPlatform\Core\Api\ResourceClassResolver;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritance;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceChild;

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
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
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
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
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

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage No resource class found.
     */
    public function testGetResourceClassWithNoResourceClassNameAndNoObject()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClassResolver->getResourceClass(false, null);
    }

    public function testGetResourceClassWithResourceClassNameAndNoObject()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $this->assertEquals($resourceClassResolver->getResourceClass(false, Dummy::class), Dummy::class);
    }

    public function testGetResourceClassWithChildResource()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyTableInheritance::class]))->shouldBeCalled();

        $t = new DummyTableInheritanceChild();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertEquals($resourceClassResolver->getResourceClass($t, DummyTableInheritance::class), DummyTableInheritanceChild::class);
    }
}
