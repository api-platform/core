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
use ApiPlatform\Core\Metadata\Resource\OperationCollectionMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * todo Update this test
 * todo Add OperationCollectionMetadataTest
 */
class ResourceMetadataTest extends TestCase
{
    public function testValueObject()
    {
        $resourceMetadata = new ResourceMetadata([new OperationCollectionMetadata('/dummies', 'shortName', 'desc', 'http://example.com/foo', ['iop1' => ['foo' => 'a'], 'iop2' => ['bar' => 'b']], ['cop1' => ['foo' => 'c'], 'cop2' => ['bar' => 'd']], ['baz' => 'bar'], ['sop1' => ['sub' => 'bus']], ['query' => ['foo' => 'graphql']])]);
        $operationCollectionMetadata = $resourceMetadata['/dummies'];
        $this->assertSame('shortName', $operationCollectionMetadata->getShortName());
        $this->assertSame('desc', $operationCollectionMetadata->getDescription());
        $this->assertSame('http://example.com/foo', $operationCollectionMetadata->getIri());
        $this->assertSame(['iop1' => ['foo' => 'a'], 'iop2' => ['bar' => 'b']], $operationCollectionMetadata->getItemOperations());
        $this->assertSame('a', $operationCollectionMetadata->getItemOperationAttribute('iop1', 'foo', 'z'));
        $this->assertSame('a', $operationCollectionMetadata->getTypedOperationAttribute(OperationType::ITEM, 'iop1', 'foo', 'z'));
        $this->assertSame('bar', $operationCollectionMetadata->getItemOperationAttribute('iop1', 'baz', 'z', true));
        $this->assertSame('bar', $operationCollectionMetadata->getItemOperationAttribute(null, 'baz', 'z', true));
        $this->assertSame('z', $operationCollectionMetadata->getItemOperationAttribute('iop1', 'notExist', 'z', true));
        $this->assertSame('z', $operationCollectionMetadata->getItemOperationAttribute('notExist', 'notExist', 'z', true));
        $this->assertSame(['cop1' => ['foo' => 'c'], 'cop2' => ['bar' => 'd']], $operationCollectionMetadata->getCollectionOperations());
        $this->assertSame('c', $operationCollectionMetadata->getCollectionOperationAttribute('cop1', 'foo', 'z'));
        $this->assertSame('c', $operationCollectionMetadata->getTypedOperationAttribute(OperationType::COLLECTION, 'cop1', 'foo', 'z'));
        $this->assertSame('bar', $operationCollectionMetadata->getCollectionOperationAttribute('cop1', 'baz', 'z', true));
        $this->assertSame('bar', $operationCollectionMetadata->getCollectionOperationAttribute(null, 'baz', 'z', true));
        $this->assertSame('z', $operationCollectionMetadata->getCollectionOperationAttribute('cop1', 'notExist', 'z', true));
        $this->assertSame('z', $operationCollectionMetadata->getCollectionOperationAttribute('notExist', 'notExist', 'z', true));
        $this->assertSame(['baz' => 'bar'], $operationCollectionMetadata->getAttributes());
        $this->assertSame('bar', $operationCollectionMetadata->getAttribute('baz'));
        $this->assertSame('z', $operationCollectionMetadata->getAttribute('notExist', 'z'));
        $this->assertSame(['sop1' => ['sub' => 'bus']], $operationCollectionMetadata->getSubresourceOperations());
        $this->assertSame('bus', $operationCollectionMetadata->getSubresourceOperationAttribute('sop1', 'sub'));
        $this->assertSame('bus', $operationCollectionMetadata->getTypedOperationAttribute(OperationType::SUBRESOURCE, 'sop1', 'sub'));
        $this->assertSame('sub', $operationCollectionMetadata->getSubresourceOperationAttribute('sop1', 'bus', 'sub'));
        $this->assertSame('bar', $operationCollectionMetadata->getSubresourceOperationAttribute('sop1', 'baz', 'sub', true));
        $this->assertSame('graphql', $operationCollectionMetadata->getGraphqlAttribute('query', 'foo'));
        $this->assertSame('bar', $operationCollectionMetadata->getGraphqlAttribute('query', 'baz', null, true));
        $this->assertSame('hey', $operationCollectionMetadata->getGraphqlAttribute('query', 'notExist', 'hey', true));

        $newMetadata = $operationCollectionMetadata->withShortName('name');
        $this->assertNotSame($operationCollectionMetadata, $newMetadata);
        $this->assertSame('name', $newMetadata->getShortName());

        $newMetadata = $operationCollectionMetadata->withDescription('description');
        $this->assertNotSame($operationCollectionMetadata, $newMetadata);
        $this->assertSame('description', $newMetadata->getDescription());

        $newMetadata = $operationCollectionMetadata->withIri('foo:bar');
        $this->assertNotSame($operationCollectionMetadata, $newMetadata);
        $this->assertSame('foo:bar', $newMetadata->getIri());

        $newMetadata = $operationCollectionMetadata->withItemOperations(['a' => ['b' => 'c']]);
        $this->assertNotSame($operationCollectionMetadata, $newMetadata);
        $this->assertSame(['a' => ['b' => 'c']], $newMetadata->getItemOperations());
    }

    /**
     * @dataProvider getWithMethods
     */
    public function testWithMethods(string $name, $value)
    {
        $metadata = new ResourceMetadata([new OperationCollectionMetadata('/dummies')]);
        $newMetadata = $metadata->{"with$name"}($value);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertSame($value, $newMetadata->{"get$name"}());
    }

    public function testGetOperationAttributeFallback()
    {
        $metadata = new ResourceMetadata([new OperationCollectionMetadata('/dummies')]);
        $this->assertSame('okay', $metadata->getOperationAttribute([], 'doh', 'okay'));
    }

    public function testGetOperationAttributeFallbackToResourceAttribute()
    {
        $metadata = new ResourceMetadata([new OperationCollectionMetadata('/dummies', null, null, null, null, null, ['doh' => 'nuts'])]);
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
