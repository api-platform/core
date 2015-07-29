<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Doctrine\Mapping\Factory;

use Dunglas\ApiBundle\Mapping\Factory\AttributeMetadataFactory;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributeMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testInitialize()
    {
        $propertyInfo = $this->prophesize('PropertyInfo\PropertyInfoInterface')->reveal();
        $resourceCollection = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface')->reveal();

        $factory = new AttributeMetadataFactory($propertyInfo, $resourceCollection);
        $this->assertInstanceOf('Dunglas\ApiBundle\Mapping\Factory\AttributeMetadataFactoryInterface', $factory);
    }

    public function testGetFromCache()
    {
        $propertyInfo = $this->prophesize('PropertyInfo\PropertyInfoInterface')->reveal();
        $resourceCollection = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollectionInterface')->reveal();
        $attributeMetadata = $this->prophesize('Dunglas\ApiBundle\Mapping\AttributeMetadataInterface')->reveal();

        $classMetadataProphecy = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataInterface');
        $classMetadataProphecy->hasAttributeMetadata('attr')->willReturn(true)->shouldBeCalled();
        $classMetadataProphecy->getAttributeMetadata('attr')->willReturn($attributeMetadata)->shouldBeCalled();
        $classMetadata = $classMetadataProphecy->reveal();

        $factory = new AttributeMetadataFactory($propertyInfo, $resourceCollection);
        $this->assertEquals($attributeMetadata, $factory->getAttributeMetadataFor($classMetadata, 'attr'));
    }
}
