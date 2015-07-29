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

use Dunglas\ApiBundle\Mapping\AttributeMetadata;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributeMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeMetadata
     */
    private $attributeMetadata;

    public function setUp()
    {
        $this->attributeMetadata = new AttributeMetadata('test');
    }

    public function testInitialize()
    {
        $this->assertInstanceOf('Dunglas\ApiBundle\Mapping\AttributeMetadataInterface', $this->attributeMetadata);
    }

    public function testType()
    {
        $this->assertNull($this->attributeMetadata->getType());

        $type = $this->prophesize('PropertyInfo\Type')->reveal();
        $newAttributeMetadata = $this->attributeMetadata->withType($type);

        $this->assertEquals($type, $newAttributeMetadata->getType());
        $this->assertNotEquals($this->attributeMetadata, $newAttributeMetadata);
    }

    public function testDescription()
    {
        $this->assertEquals('', $this->attributeMetadata->getDescription());

        $newAttributeMetadata = $this->attributeMetadata->withDescription('desc');

        $this->assertEquals('desc', $newAttributeMetadata->getDescription());
        $this->assertNotEquals($this->attributeMetadata, $newAttributeMetadata);
    }

    public function testReadable()
    {
        $this->assertFalse($this->attributeMetadata->isReadable());

        $newAttributeMetadata = $this->attributeMetadata->withReadable(true);

        $this->assertTrue($newAttributeMetadata->isReadable());
        $this->assertNotEquals($this->attributeMetadata, $newAttributeMetadata);
    }

    public function testWritable()
    {
        $this->assertFalse($this->attributeMetadata->isWritable());

        $newAttributeMetadata = $this->attributeMetadata->withWritable(true);

        $this->assertTrue($newAttributeMetadata->isWritable());
        $this->assertNotEquals($this->attributeMetadata, $newAttributeMetadata);
    }

    public function testRequired()
    {
        $this->assertFalse($this->attributeMetadata->isRequired());

        $newAttributeMetadata = $this->attributeMetadata->withRequired(true);

        $this->assertTrue($newAttributeMetadata->isRequired());
        $this->assertNotEquals($this->attributeMetadata, $newAttributeMetadata);
    }

    public function testLink()
    {
        $this->assertFalse($this->attributeMetadata->isLink());

        $newAttributeMetadata = $this->attributeMetadata->withLink(true);

        $this->assertTrue($newAttributeMetadata->isLink());
        $this->assertNotEquals($this->attributeMetadata, $newAttributeMetadata);
    }

    public function testLinkClass()
    {
        $this->assertEquals('', $this->attributeMetadata->getLinkClass());

        $newAttributeMetadata = $this->attributeMetadata->withLinkClass('class');

        $this->assertEquals('class', $newAttributeMetadata->getLinkClass());
        $this->assertNotEquals($this->attributeMetadata, $newAttributeMetadata);
    }

    public function testNormalizationLink()
    {
        $this->assertFalse($this->attributeMetadata->isNormalizationLink());

        $newAttributeMetadata = $this->attributeMetadata->withNormalizationLink(true);

        $this->assertTrue($newAttributeMetadata->isNormalizationLink());
        $this->assertNotEquals($this->attributeMetadata, $newAttributeMetadata);
    }

    public function testDenormalizationLink()
    {
        $this->assertFalse($this->attributeMetadata->isDenormalizationLink());

        $newAttributeMetadata = $this->attributeMetadata->withDenormalizationLink(true);

        $this->assertTrue($newAttributeMetadata->isDenormalizationLink());
        $this->assertNotEquals($this->attributeMetadata, $newAttributeMetadata);
    }

    public function testIri()
    {
        $this->assertNull($this->attributeMetadata->getIri());

        $newAttributeMetadata = $this->attributeMetadata->withIri('https://les-tilleuls.coop');

        $this->assertEquals('https://les-tilleuls.coop', $newAttributeMetadata->getIri());
        $this->assertNotEquals($this->attributeMetadata, $newAttributeMetadata);
    }

    public function testSerialize()
    {
        $serialized = serialize($this->attributeMetadata);

        $this->assertEquals($this->attributeMetadata, unserialize($serialized));
    }
}
