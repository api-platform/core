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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\Property\Factory\InheritedPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritance;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceChild;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class InheritedPropertyNameCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $resourceNameCollectionFactory = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->create()->willReturn(new ResourceNameCollection([DummyTableInheritance::class, DummyTableInheritanceChild::class]))->shouldBeCalled();

        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(DummyTableInheritance::class, [])->willReturn(new PropertyNameCollection(['name']))->shouldBeCalled();
        $propertyNameCollectionFactory->create(DummyTableInheritanceChild::class, [])->willReturn(new PropertyNameCollection(['nickname']))->shouldBeCalled();

        $factory = new InheritedPropertyNameCollectionFactory($resourceNameCollectionFactory->reveal(), $propertyNameCollectionFactory->reveal());
        $metadata = $factory->create(DummyTableInheritance::class);

        $this->assertEquals($metadata, new PropertyNameCollection(['name', 'nickname']));
    }
}
