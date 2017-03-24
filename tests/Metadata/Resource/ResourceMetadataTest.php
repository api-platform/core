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

namespace ApiPlatform\Core\tests\Metadata\Resource;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testValueObject()
    {
        $metadata = new ResourceMetadata('shortName', 'desc', 'http://example.com/foo', ['iop1' => ['foo' => 'a'], 'iop2' => ['bar' => 'b']], ['cop1' => ['foo' => 'c'], 'cop2' => ['bar' => 'd']], ['baz' => 'bar']);
        $this->assertEquals('shortName', $metadata->getShortName());
        $this->assertEquals('desc', $metadata->getDescription());
        $this->assertEquals('http://example.com/foo', $metadata->getIri());
        $this->assertEquals(['iop1' => ['foo' => 'a'], 'iop2' => ['bar' => 'b']], $metadata->getItemOperations());
        $this->assertEquals('a', $metadata->getItemOperationAttribute('iop1', 'foo', 'z', false));
        $this->assertEquals('bar', $metadata->getItemOperationAttribute('iop1', 'baz', 'z', true));
        $this->assertEquals('z', $metadata->getItemOperationAttribute('iop1', 'notExist', 'z', true));
        $this->assertEquals('z', $metadata->getItemOperationAttribute('notExist', 'notExist', 'z', true));
        $this->assertEquals(['cop1' => ['foo' => 'c'], 'cop2' => ['bar' => 'd']], $metadata->getCollectionOperations());
        $this->assertEquals('c', $metadata->getCollectionOperationAttribute('cop1', 'foo', 'z', false));
        $this->assertEquals('bar', $metadata->getCollectionOperationAttribute('cop1', 'baz', 'z', true));
        $this->assertEquals('z', $metadata->getCollectionOperationAttribute('cop1', 'notExist', 'z', true));
        $this->assertEquals('z', $metadata->getCollectionOperationAttribute('notExist', 'notExist', 'z', true));
        $this->assertEquals(['baz' => 'bar'], $metadata->getAttributes());
        $this->assertEquals('bar', $metadata->getAttribute('baz'));
        $this->assertEquals('z', $metadata->getAttribute('notExist', 'z'));

        $newMetadata = $metadata->withShortName('name');
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals('name', $newMetadata->getShortName());

        $newMetadata = $metadata->withDescription('description');
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals('description', $newMetadata->getDescription());

        $newMetadata = $metadata->withIri('foo:bar');
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals('foo:bar', $newMetadata->getIri());

        $newMetadata = $metadata->withItemOperations(['a' => ['b' => 'c']]);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals(['a' => ['b' => 'c']], $newMetadata->getItemOperations());

        $newMetadata = $metadata->withCollectionOperations(['a' => ['b' => 'c']]);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals(['a' => ['b' => 'c']], $newMetadata->getCollectionOperations());

        $newMetadata = $metadata->withAttributes(['a' => ['b' => 'c']]);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals(['a' => ['b' => 'c']], $newMetadata->getAttributes());
    }
}
