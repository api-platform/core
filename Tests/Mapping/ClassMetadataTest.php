<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Doctrine\Mapping;

use Dunglas\ApiBundle\Mapping\ClassMetadata;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ClassMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassMetadata
     */
    private $classMetadata;

    public function setUp()
    {
        $this->classMetadata = new ClassMetadata('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');
    }

    public function testInitialize()
    {
        $this->assertInstanceOf('Dunglas\ApiBundle\Mapping\ClassMetadataInterface', $this->classMetadata);
    }

    public function testName()
    {
        $this->assertEquals('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity', $this->classMetadata->getName());
    }

    public function testDescription()
    {
        $newClassMetadata = $this->classMetadata->withDescription('desc');

        $this->assertEquals('desc', $newClassMetadata->getDescription());
        $this->assertNotEquals($this->classMetadata, $newClassMetadata);
    }

    public function testIri()
    {
        $newClassMetadata = $this->classMetadata->withIri('https://dunglas.fr');

        $this->assertEquals('https://dunglas.fr', $newClassMetadata->getIri());
        $this->assertNotEquals($this->classMetadata, $newClassMetadata);
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The class "Dunglas\ApiBundle\Tests\Fixtures\DummyEntity" has no identifier. Maybe you forgot to define the Entity Identifier, or using composite identifiers (which are not supported)?
     */
    public function testIdentifierNameNotSet()
    {
        $this->classMetadata->getIdentifierName();
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage The attribute "id" cannot be the identifier: this attribute does not exist.
     */
    public function testSetNotExistingIdentifierName()
    {
        $this->classMetadata->withIdentifierName('id');
    }

    public function testAttributes()
    {
        $this->assertFalse($this->classMetadata->hasAttributeMetadata('id'));

        $attributeMetadata = $this->prophesize('Dunglas\ApiBundle\Mapping\AttributeMetadataInterface')->reveal();
        $newClassMetadata = $this->classMetadata->withAttributeMetadata('id', $attributeMetadata);

        $this->assertNotEquals($this->classMetadata, $newClassMetadata);
        $this->assertTrue($newClassMetadata->hasAttributeMetadata('id'));
        $this->assertEquals($attributeMetadata, $newClassMetadata->getAttributeMetadata('id'));
        $this->assertContains($attributeMetadata, $newClassMetadata->getAttributesMetadata());

        $newClassMetadata2 = $newClassMetadata->withIdentifierName('id');

        $this->assertNotEquals($newClassMetadata, $newClassMetadata2);
        $this->assertEquals('id', $newClassMetadata2->getIdentifierName());
        $this->assertEquals($attributeMetadata, $newClassMetadata2->getAttributeMetadata($newClassMetadata2->getIdentifierName()));
    }

    public function testGetReflectionClass()
    {
        $this->assertInstanceOf('\ReflectionClass', $this->classMetadata->getReflectionClass());
    }

    public function testSerialize()
    {
        $serialized = serialize($this->classMetadata);

        $this->assertEquals($this->classMetadata, unserialize($serialized));
    }
}
