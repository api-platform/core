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

namespace ApiPlatform\Core\Tests\Metadata\Resource;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceMetadataTest extends TestCase
{
    public function testValueObject()
    {
        $metadata = new ResourceMetadata('shortName', 'desc', 'http://example.com/foo', ['iop1' => ['foo' => 'a'], 'iop2' => ['bar' => 'b']], ['cop1' => ['foo' => 'c'], 'cop2' => ['bar' => 'd']], ['baz' => 'bar'], ['sop1' => ['sub' => 'bus']], ['query' => ['foo' => 'graphql']]);
        $this->assertSame('shortName', $metadata->getShortName());
        $this->assertSame('desc', $metadata->getDescription());
        $this->assertSame('http://example.com/foo', $metadata->getIri());
        $this->assertSame(['iop1' => ['foo' => 'a'], 'iop2' => ['bar' => 'b']], $metadata->getItemOperations());
        $this->assertSame('a', $metadata->getItemOperationAttribute('iop1', 'foo', 'z'));
        $this->assertSame('a', $metadata->getTypedOperationAttribute(OperationType::ITEM, 'iop1', 'foo', 'z'));
        $this->assertSame('bar', $metadata->getItemOperationAttribute('iop1', 'baz', 'z', true));
        $this->assertSame('bar', $metadata->getItemOperationAttribute(null, 'baz', 'z', true));
        $this->assertSame('z', $metadata->getItemOperationAttribute('iop1', 'notExist', 'z', true));
        $this->assertSame('z', $metadata->getItemOperationAttribute('notExist', 'notExist', 'z', true));
        $this->assertSame(['cop1' => ['foo' => 'c'], 'cop2' => ['bar' => 'd']], $metadata->getCollectionOperations());
        $this->assertSame('c', $metadata->getCollectionOperationAttribute('cop1', 'foo', 'z'));
        $this->assertSame('c', $metadata->getTypedOperationAttribute(OperationType::COLLECTION, 'cop1', 'foo', 'z'));
        $this->assertSame('bar', $metadata->getCollectionOperationAttribute('cop1', 'baz', 'z', true));
        $this->assertSame('bar', $metadata->getCollectionOperationAttribute(null, 'baz', 'z', true));
        $this->assertSame('z', $metadata->getCollectionOperationAttribute('cop1', 'notExist', 'z', true));
        $this->assertSame('z', $metadata->getCollectionOperationAttribute('notExist', 'notExist', 'z', true));
        $this->assertSame(['baz' => 'bar'], $metadata->getAttributes());
        $this->assertSame('bar', $metadata->getAttribute('baz'));
        $this->assertSame('z', $metadata->getAttribute('notExist', 'z'));
        $this->assertSame(['sop1' => ['sub' => 'bus']], $metadata->getSubresourceOperations());
        $this->assertSame('bus', $metadata->getSubresourceOperationAttribute('sop1', 'sub'));
        $this->assertSame('bus', $metadata->getTypedOperationAttribute(OperationType::SUBRESOURCE, 'sop1', 'sub'));
        $this->assertSame('sub', $metadata->getSubresourceOperationAttribute('sop1', 'bus', 'sub'));
        $this->assertSame('bar', $metadata->getSubresourceOperationAttribute('sop1', 'baz', 'sub', true));
        $this->assertSame('graphql', $metadata->getGraphqlAttribute('query', 'foo'));
        $this->assertSame('bar', $metadata->getGraphqlAttribute('query', 'baz', null, true));
        $this->assertSame('hey', $metadata->getGraphqlAttribute('query', 'notExist', 'hey', true));

        $newMetadata = $metadata->withShortName('name');
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertSame('name', $newMetadata->getShortName());

        $newMetadata = $metadata->withDescription('description');
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertSame('description', $newMetadata->getDescription());

        $newMetadata = $metadata->withIri('foo:bar');
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertSame('foo:bar', $newMetadata->getIri());

        $newMetadata = $metadata->withItemOperations(['a' => ['b' => 'c']]);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertSame(['a' => ['b' => 'c']], $newMetadata->getItemOperations());
    }

    /**
     * @dataProvider getWithMethods
     */
    public function testWithMethods(string $name, $value)
    {
        $metadata = new ResourceMetadata();
        $newMetadata = $metadata->{"with$name"}($value);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertSame($value, $newMetadata->{"get$name"}());
    }

    public function testGetOperationAttributeFallback()
    {
        $metadata = new ResourceMetadata();
        $this->assertSame('okay', $metadata->getOperationAttribute([], 'doh', 'okay'));
    }

    public function testGetOperationAttributeFallbackToResourceAttribute()
    {
        $metadata = new ResourceMetadata(null, null, null, null, null, ['doh' => 'nuts']);
        $this->assertSame('nuts', $metadata->getOperationAttribute([], 'doh', 'okay', true));
    }

    public function getWithMethods(): array
    {
        return [
            ['ShortName', 'shortName'],
            ['Description', 'description'],
            ['Iri', 'iri'],
            ['ItemOperations', ['a' => ['b' => 'c']]],
            ['CollectionOperations', ['a' => ['b' => 'c']]],
            ['Attributes', ['a' => ['b' => 'c']]],
            ['Graphql', ['query' => ['b' => 'c']]],
        ];
    }
}
