<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Metadata\Property;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PropertyMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testValueObject()
    {
        $type = new Type(Type::BUILTIN_TYPE_STRING);
        $metadata = new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'desc', true, true, false, false, true, false, 'http://example.com/foo', ['foo' => 'bar']);
        $this->assertEquals($type, $metadata->getType());
        $this->assertEquals('desc', $metadata->getDescription());
        $this->assertTrue($metadata->isReadable());
        $this->assertTrue($metadata->isWritable());
        $this->assertFalse($metadata->isReadableLink());
        $this->assertFalse($metadata->isWritableLink());
        $this->assertTrue($metadata->isRequired());
        $this->assertFalse($metadata->isIdentifier());
        $this->assertEquals('http://example.com/foo', $metadata->getIri());
        $this->assertEquals(['foo' => 'bar'], $metadata->getAttributes());

        $newType = new Type(Type::BUILTIN_TYPE_BOOL);
        $newMetadata = $metadata->withType($newType);
        $this->assertFalse($newMetadata === $metadata);
        $this->assertEquals($newType, $newMetadata->getType());

        $newMetadata = $metadata->withDescription('description');
        $this->assertFalse($newMetadata === $metadata);
        $this->assertEquals('description', $newMetadata->getDescription());

        $newMetadata = $metadata->withReadable(false);
        $this->assertFalse($newMetadata === $metadata);
        $this->assertFalse($newMetadata->isReadable());

        $newMetadata = $metadata->withWritable(false);
        $this->assertFalse($newMetadata === $metadata);
        $this->assertFalse($newMetadata->isWritable());

        $newMetadata = $metadata->withReadableLink(true);
        $this->assertFalse($newMetadata === $metadata);
        $this->assertTrue($newMetadata->isReadableLink());

        $newMetadata = $metadata->withWritableLink(true);
        $this->assertFalse($newMetadata === $metadata);
        $this->assertTrue($newMetadata->isWritableLink());

        $newMetadata = $metadata->withRequired(false);
        $this->assertFalse($newMetadata === $metadata);
        $this->assertFalse($newMetadata->isRequired());

        $newMetadata = $metadata->withIdentifier(true);
        $this->assertFalse($newMetadata === $metadata);
        $this->assertTrue($newMetadata->isIdentifier());

        $newMetadata = $metadata->withIri('foo:bar');
        $this->assertFalse($newMetadata === $metadata);
        $this->assertEquals('foo:bar', $newMetadata->getIri());

        $newMetadata = $metadata->withAttributes(['a' => 'b']);
        $this->assertFalse($newMetadata === $metadata);
        $this->assertEquals(['a' => 'b'], $newMetadata->getAttributes());
    }
}
