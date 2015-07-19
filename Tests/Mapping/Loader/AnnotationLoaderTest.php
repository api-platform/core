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

use Dunglas\ApiBundle\Mapping\Loader\AnnotationLoader;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AnnotationLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadClassMetadata()
    {
        $classAnnotation = new \stdClass();
        $classAnnotation->value = 'http://example.com';

        $attributeAnnotation = new \stdClass();
        $attributeAnnotation->value = 'http://example.org';

        $reflectionProperty = $this->prophesize('ReflectionProperty')->reveal();

        $reflectionClassProphecy = $this->prophesize('ReflectionClass');
        $reflectionClassProphecy->hasProperty('attr')->willReturn(true)->shouldBeCalled();
        $reflectionClassProphecy->getProperty('attr')->willReturn($reflectionProperty)->shouldBeCalled();
        $reflectionClass = $reflectionClassProphecy->reveal();

        $readerProphecy = $this->prophesize('Doctrine\Common\Annotations\Reader');
        $readerProphecy->getClassAnnotation($reflectionClass, AnnotationLoader::IRI_ANNOTATION_NAME)->willReturn($classAnnotation)->shouldBeCalled();
        $readerProphecy->getPropertyAnnotation($reflectionProperty, AnnotationLoader::IRI_ANNOTATION_NAME)->willReturn($attributeAnnotation)->shouldBeCalled();
        $reader = $readerProphecy->reveal();

        $attributeMetadata2 = $this->prophesize('Dunglas\ApiBundle\Mapping\AttributeMetadataInterface')->reveal();

        $attributeMetadataProphecy1 = $this->prophesize('Dunglas\ApiBundle\Mapping\AttributeMetadataInterface');
        $attributeMetadataProphecy1->withIri($attributeAnnotation->value)->willReturn($attributeMetadata2)->shouldBeCalled();
        $attributeMetadata1 = $attributeMetadataProphecy1->reveal();

        $classMetadata3 = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataInterface')->reveal();

        $classMetadataProphecy2 = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataInterface');
        $classMetadataProphecy2->getAttributesMetadata()->willReturn(['attr' => $attributeMetadata1])->shouldBeCalled();
        $classMetadataProphecy2->withAttributeMetadata('attr', $attributeMetadata2)->willReturn($classMetadata3)->shouldBeCalled();
        $classMetadata2 = $classMetadataProphecy2->reveal();

        $classMetadataProphecy1 = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataInterface');
        $classMetadataProphecy1->getReflectionClass()->willReturn($reflectionClass)->shouldBeCalled();
        $classMetadataProphecy1->withIri($classAnnotation->value)->willReturn($classMetadata2)->shouldBeCalled();
        $classMetadata1 = $classMetadataProphecy1->reveal();

        $loader = new AnnotationLoader($reader);

        $this->assertInstanceOf('Dunglas\ApiBundle\Mapping\Loader\LoaderInterface', $loader);
        $this->assertEquals($classMetadata3, $loader->loadClassMetadata($classMetadata1, ['a'], ['b'], ['c']));
    }
}
