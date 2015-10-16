<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Doctrine\Mapping\Loader;

use Dunglas\ApiBundle\Mapping\Loader\ReflectionLoader;

class MyTestClass
{
    private $SIRET;
    public function getSIRET()
    {
        return $this->SIRET;
    }
    public function setSIRET($SIRET)
    {
        $this->SIRET = $SIRET;
    }
}

class ReflectionLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testUppercaseMethods()
    {
        $testClass = new MyTestClass();
        $reflectionClass = new \ReflectionClass($testClass);

        $attributeMetadataProphecy2 = $this->prophesize('Dunglas\ApiBundle\Mapping\AttributeMetadataInterface');
        $attributeMetadata2 = $attributeMetadataProphecy2->reveal();

        $attributeMetadataProphecy = $this->prophesize('Dunglas\ApiBundle\Mapping\AttributeMetadataInterface');
        $attributeMetadataProphecy->withWritable(true)->willReturn($attributeMetadata2)->shouldBeCalled();
        $attributeMetadataProphecy->withReadable(true)->willReturn($attributeMetadata2)->shouldBeCalled();
        $attributeMetadata = $attributeMetadataProphecy->reveal();

        $classMetadataProphecy = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataInterface');
        $classMetadataProphecy->withAttributeMetadata('SIRET', $attributeMetadata2)->shouldBeCalled();
        $classMetadataProphecy->getReflectionClass()->willReturn($reflectionClass)->shouldBeCalled();
        $classMetadata = $classMetadataProphecy->reveal();

        $factoryProphecy = $this->prophesize('Dunglas\ApiBundle\Mapping\Factory\AttributeMetadataFactory');
        $factoryProphecy->getAttributeMetadataFor($classMetadata, 'SIRET', null, null)->willReturn($attributeMetadata)->shouldBeCalled();
        $factory = $factoryProphecy->reveal();

        $loader = new ReflectionLoader($factory);
        $this->assertInstanceOf('Dunglas\ApiBundle\Mapping\Loader\LoaderInterface', $loader);

        $this->assertEquals($loader->loadClassMetadata($classMetadata)->getReflectionClass(), $reflectionClass);
    }
}
