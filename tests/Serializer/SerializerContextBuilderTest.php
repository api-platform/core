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

namespace ApiPlatform\Tests\Serializer;

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Serializer\SerializerContextBuilder;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializerContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    private SerializerContextBuilder $builder;
    private HttpOperation $operation;
    private HttpOperation $patchOperation;

    protected function setUp(): void
    {
        $this->operation = new Get(normalizationContext: ['foo' => 'bar'], denormalizationContext: ['bar' => 'baz'], name: 'get');
        $resourceMetadata = new ResourceMetadataCollection('Foo', [
            new ApiResource(operations: [
                'get' => $this->operation,
                'post' => $this->operation->withName('post'),
                'put' => (new Put(name: 'put'))->withOperation($this->operation),
                'get_collection' => $this->operation->withName('get_collection'),
            ]),
        ]);

        $this->patchOperation = new Patch(inputFormats: ['json' => ['application/merge-patch+json']], name: 'patch');
        $resourceMetadataWithPatch = new ResourceMetadataCollection('Foo', [
            new ApiResource(operations: [
                'patch' => $this->patchOperation,
            ]),
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata);
        $resourceMetadataFactoryProphecy->create('FooWithPatch')->willReturn($resourceMetadataWithPatch);

        $this->builder = new SerializerContextBuilder($resourceMetadataFactoryProphecy->reveal());
    }

    public function testCreateFromRequest()
    {
        $request = Request::create('/foos/1');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['foo' => 'bar', 'operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => '/foos/1', 'uri' => 'http://localhost/foos/1', 'output' => null, 'input' => null, 'iri_only' => false, 'skip_null_values' => true, 'operation' => $this->operation];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, true));

        $request = Request::create('/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'get_collection', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['foo' => 'bar', 'operation_name' => 'get_collection',  'resource_class' => 'Foo', 'request_uri' => '/foos', 'uri' => 'http://localhost/foos', 'output' => null, 'input' => null, 'iri_only' => false, 'skip_null_values' => true, 'operation' => $this->operation->withName('get_collection')];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, true));

        $request = Request::create('/foos/1');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => '/foos/1', 'api_allow_update' => false, 'uri' => 'http://localhost/foos/1', 'output' => null, 'input' => null, 'iri_only' => false, 'skip_null_values' => true, 'operation' => $this->operation];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foos', 'POST');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'post', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'operation_name' => 'post',  'resource_class' => 'Foo', 'request_uri' => '/foos', 'api_allow_update' => false, 'uri' => 'http://localhost/foos', 'output' => null, 'input' => null, 'iri_only' => false, 'skip_null_values' => true, 'operation' => $this->operation->withName('post')];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foos', 'PUT');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'put', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'operation_name' => 'put', 'resource_class' => 'Foo', 'request_uri' => '/foos', 'api_allow_update' => true, 'uri' => 'http://localhost/foos', 'output' => null, 'input' => null, 'iri_only' => false, 'skip_null_values' => true, 'operation' => (new Put(name: 'put'))->withOperation($this->operation)];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/bars/1/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'operation_name' => 'get', 'resource_class' => 'Foo', 'request_uri' => '/bars/1/foos', 'api_allow_update' => false, 'uri' => 'http://localhost/bars/1/foos', 'output' => null, 'input' => null, 'iri_only' => false, 'skip_null_values' => true, 'operation' => $this->operation];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foowithpatch/1', 'PATCH');
        $request->attributes->replace(['_api_resource_class' => 'FooWithPatch', '_api_operation_name' => 'patch', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $expected = ['operation_name' => 'patch', 'resource_class' => 'FooWithPatch', 'request_uri' => '/foowithpatch/1', 'api_allow_update' => true, 'uri' => 'http://localhost/foowithpatch/1', 'output' => null, 'input' => null, 'deep_object_to_populate' => true, 'skip_null_values' => true, 'iri_only' => false, 'operation' => $this->patchOperation];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/bars/1/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml', '_api_subresource_context' => ['identifiers' => ['id' => ['Foo', 'id']]], 'id' => '1']);
        $expected = ['bar' => 'baz', 'operation_name' => 'get', 'resource_class' => 'Foo', 'request_uri' => '/bars/1/foos', 'api_allow_update' => false, 'uri' => 'http://localhost/bars/1/foos', 'output' => null, 'input' => null, 'iri_only' => false, 'operation' => $this->operation, 'skip_null_values' => true];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));
    }

    public function testThrowExceptionOnInvalidRequest()
    {
        $this->expectException(RuntimeException::class);

        $this->builder->createFromRequest(new Request(), false);
    }

    public function testReuseExistingAttributes()
    {
        $expected = ['bar' => 'baz', 'operation_name' => 'get', 'resource_class' => 'Foo', 'request_uri' => '/foos/1', 'api_allow_update' => false, 'uri' => 'http://localhost/foos/1', 'output' => null, 'input' => null, 'iri_only' => false, 'skip_null_values' => true, 'operation' => $this->operation];
        $this->assertEquals($expected, $this->builder->createFromRequest(Request::create('/foos/1'), false, ['resource_class' => 'Foo', 'operation_name' => 'get']));
    }
}
