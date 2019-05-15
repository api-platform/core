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
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\DummyResourceImplementation;
use ApiPlatform\Core\Tests\Fixtures\DummyResourceInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritance;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceChild;
use PHPUnit\Framework\TestCase;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ResourceClassResolverTest extends TestCase
{
    public function testGetResourceClassWithIntendedClassName()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]));

        $dummy = new Dummy();
        $dummy->setName('Smail');

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertEquals(Dummy::class, $resourceClassResolver->getResourceClass($dummy, Dummy::class));
    }

    public function testGetResourceClassWithNonResourceClassName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Specified class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar" is not a resource class.');

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]));

        $dummy = new Dummy();
        $dummy->setName('Smail');

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $resourceClassResolver->getResourceClass($dummy, DummyCar::class, true);
    }

    public function testGetResourceClassWithNoClassName()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]));

        $dummy = new Dummy();
        $dummy->setName('Smail');

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertEquals(Dummy::class, $resourceClassResolver->getResourceClass($dummy));
    }

    public function testGetResourceClassWithTraversableAsValue()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]));

        $dummy = new Dummy();
        $dummy->setName('JLM');

        $dummies = new \ArrayIterator([$dummy]);

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertEquals(Dummy::class, $resourceClassResolver->getResourceClass($dummies, Dummy::class));
    }

    public function testGetResourceClassWithPaginatorInterfaceAsValue()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $paginatorProphecy = $this->prophesize(PaginatorInterface::class);

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertEquals(Dummy::class, $resourceClassResolver->getResourceClass($paginatorProphecy->reveal(), Dummy::class));
    }

    public function testGetResourceClassWithWrongClassName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No resource class found for object of type "stdClass".');

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $resourceClassResolver->getResourceClass(new \stdClass());
    }

    public function testGetResourceClassWithNoResourceClassName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource type could not be determined. Resource class must be specified.');

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $resourceClassResolver->getResourceClass(new \ArrayIterator([]));
    }

    public function testIsResourceClassWithIntendedClassName()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]));

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertTrue($resourceClassResolver->isResourceClass(Dummy::class));
    }

    public function testIsResourceClassWithWrongClassName()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([\ArrayIterator::class]));

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertFalse($resourceClassResolver->isResourceClass(''));
    }

    public function testGetResourceClassWithNoResourceClassNameAndNoObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource type could not be determined. Resource class must be specified.');

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $resourceClassResolver->getResourceClass(false);
    }

    public function testGetResourceClassWithResourceClassNameAndNoObject()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]));

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertEquals(Dummy::class, $resourceClassResolver->getResourceClass(false, Dummy::class));
    }

    public function testGetResourceClassWithChildResource()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyTableInheritance::class, DummyTableInheritanceChild::class]));

        $dummy = new DummyTableInheritanceChild();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertEquals(DummyTableInheritanceChild::class, $resourceClassResolver->getResourceClass($dummy, DummyTableInheritance::class));
    }

    public function testGetResourceClassWithInterfaceResource()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyResourceInterface::class]));

        $dummy = new DummyResourceImplementation();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertEquals(DummyResourceInterface::class, $resourceClassResolver->getResourceClass($dummy, DummyResourceInterface::class, true));
    }
}
