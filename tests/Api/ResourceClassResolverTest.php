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
    public function testGetResourceClassWithIntendedClassName(): void
    {
        $dummy = new Dummy();
        $dummy->setName('Smail');
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->getResourceClass($dummy, Dummy::class);
        $this->assertEquals($resourceClass, Dummy::class);
    }

    public function testGetResourceClassWithOtherClassName(): void
    {
        $dummy = new Dummy();
        $dummy->setName('Smail');
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->getResourceClass($dummy, DummyCar::class, true);
        $this->assertEquals($resourceClass, Dummy::class);
    }

    public function testGetResourceClassWithNoClassName(): void
    {
        $dummy = new Dummy();
        $dummy->setName('Smail');
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->getResourceClass($dummy, null);
        $this->assertEquals($resourceClass, Dummy::class);
    }

    public function testGetResourceClassWithTraversableAsValue(): void
    {
        $dummy = new Dummy();
        $dummy->setName('JLM');

        $dummies = new \ArrayIterator([$dummy]);

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->getResourceClass($dummies, Dummy::class);

        $this->assertEquals($resourceClass, Dummy::class);
    }

    public function testGetResourceClassWithPaginatorInterfaceAsValue(): void
    {
        $paginatorProphecy = $this->prophesize(PaginatorInterface::class);

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->getResourceClass($paginatorProphecy->reveal(), Dummy::class);

        $this->assertEquals($resourceClass, Dummy::class);
    }

    public function testGetResourceClassWithWrongClassName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No resource class found for object of type "stdClass".');

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClassResolver->getResourceClass(new \stdClass(), null);
    }

    public function testGetResourceClassWithNoResourceClassName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No resource class found.');

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClassResolver->getResourceClass(new \ArrayIterator([]), null);
    }

    public function testIsResourceClassWithIntendedClassName(): void
    {
        $dummy = new Dummy();
        $dummy->setName('Smail');
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->isResourceClass(Dummy::class);
        $this->assertTrue($resourceClass);
    }

    public function testIsResourceClassWithWrongClassName(): void
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([\ArrayIterator::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->isResourceClass('');
        $this->assertFalse($resourceClass);
    }

    public function testGetResourceClassWithNoResourceClassNameAndNoObject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No resource class found.');

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClassResolver->getResourceClass(false, null);
    }

    public function testGetResourceClassWithResourceClassNameAndNoObject(): void
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $this->assertEquals($resourceClassResolver->getResourceClass(false, Dummy::class), Dummy::class);
    }

    public function testGetResourceClassWithChildResource(): void
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyTableInheritance::class]))->shouldBeCalled();

        $t = new DummyTableInheritanceChild();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());

        $this->assertEquals(DummyTableInheritanceChild::class, $resourceClassResolver->getResourceClass($t, DummyTableInheritance::class));
    }

    public function testGetResourceClassWithInterfaceResource(): void
    {
        $dummy = new DummyResourceImplementation();
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyResourceInterface::class]))->shouldBeCalled();

        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $resourceClass = $resourceClassResolver->getResourceClass($dummy, DummyResourceInterface::class, true);
        $this->assertEquals(DummyResourceImplementation::class, $resourceClass);
    }
}
