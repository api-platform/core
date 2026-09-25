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

namespace ApiPlatform\Serializer\Tests;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Serializer\SerializerContextBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializerContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    private SerializerContextBuilder $builder;
    private HttpOperation $operation;
    private HttpOperation $getCollectionOperation;
    private HttpOperation $postOperation;
    private HttpOperation $putOperation;
    private HttpOperation $patchOperation;
    private HttpOperation $patchCollectionOperation;

    protected function setUp(): void
    {
        $this->operation = new Get(normalizationContext: ['foo' => 'bar'], denormalizationContext: ['bar' => 'baz'], name: 'get');
        $this->getCollectionOperation = $this->operation->withName('get_collection');
        $this->postOperation = $this->operation->withName('post');
        $this->putOperation = (new Put(name: 'put'))->withOperation($this->operation);
        $resourceMetadata = new ResourceMetadataCollection('Foo', [
            new ApiResource(operations: [
                'get' => $this->operation,
                'post' => $this->postOperation,
                'put' => $this->putOperation,
                'get_collection' => $this->getCollectionOperation,
            ]),
        ]);

        $this->patchOperation = new Patch(inputFormats: ['json' => ['application/merge-patch+json'], 'csv' => ['text/csv']], name: 'patch');
        $this->patchCollectionOperation = $this->patchOperation
            ->withDenormalizationContext([CsvEncoder::AS_COLLECTION_KEY => true])
            ->withName('patch_collection');
        $resourceMetadataWithPatch = new ResourceMetadataCollection('Foo', [
            new ApiResource(operations: [
                'patch' => $this->patchOperation,
                'patch_collection' => $this->patchCollectionOperation,
            ]),
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata);
        $resourceMetadataFactoryProphecy->create('FooWithPatch')->willReturn($resourceMetadataWithPatch);

        $this->builder = new SerializerContextBuilder($resourceMetadataFactoryProphecy->reveal());
    }

    public function testCreateFromRequest(): void
    {
        $request = Request::create('/foos/1');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['foo' => 'bar', 'operation_name' => 'get', 'operation' => $this->operation, 'resource_class' => 'Foo', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/foos/1', 'uri' => 'http://localhost/foos/1', 'input' => null, 'output' => null, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata', 'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest($request, true));

        $request = Request::create('/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'get_collection', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['foo' => 'bar', 'operation_name' => 'get_collection', 'operation' => $this->getCollectionOperation, 'resource_class' => 'Foo', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/foos', 'uri' => 'http://localhost/foos', 'input' => null, 'output' => null, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata',  'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest($request, true));

        $request = Request::create('/foos/1');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'operation_name' => 'get', 'operation' => $this->operation, 'resource_class' => 'Foo', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/foos/1', 'uri' => 'http://localhost/foos/1', 'input' => null, 'output' => null, 'api_allow_update' => false, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata', 'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foos', 'POST');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'post', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'operation_name' => 'post', 'operation' => $this->postOperation, 'resource_class' => 'Foo', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/foos', 'uri' => 'http://localhost/foos', 'input' => null, 'output' => null, 'api_allow_update' => false, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata', 'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foos', 'PUT');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'put', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'operation_name' => 'put', 'operation' => $this->putOperation, 'resource_class' => 'Foo', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/foos', 'uri' => 'http://localhost/foos', 'input' => null, 'output' => null, 'api_allow_update' => true, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata', 'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/bars/1/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'operation_name' => 'get', 'operation' => $this->operation, 'resource_class' => 'Foo', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/bars/1/foos', 'uri' => 'http://localhost/bars/1/foos', 'input' => null, 'output' => null, 'api_allow_update' => false, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata', 'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foowithpatch/1', 'PATCH');
        $request->attributes->replace(['_api_resource_class' => 'FooWithPatch', '_api_operation_name' => 'patch', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $expected = ['operation_name' => 'patch', 'operation' => $this->patchOperation, 'resource_class' => 'FooWithPatch', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/foowithpatch/1', 'uri' => 'http://localhost/foowithpatch/1', 'input' => null, 'output' => null, 'api_allow_update' => true, 'deep_object_to_populate' => true, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata', 'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/bars/1/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml', 'id' => '1']);
        $expected = ['bar' => 'baz', 'operation_name' => 'get', 'operation' => $this->operation, 'resource_class' => 'Foo', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/bars/1/foos', 'uri' => 'http://localhost/bars/1/foos', 'input' => null, 'output' => null, 'api_allow_update' => false, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata', 'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foowithpatch/1', 'PATCH', server: ['CONTENT_TYPE' => 'text/csv']);
        $request->setFormat('csv', ['text/csv']);
        $request->attributes->replace(['_api_resource_class' => 'FooWithPatch', '_api_operation_name' => 'patch', '_api_format' => 'csv', '_api_mime_type' => 'text/csv']);
        $expected = ['operation_name' => 'patch', 'operation' => $this->patchOperation, 'resource_class' => 'FooWithPatch', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/foowithpatch/1', 'uri' => 'http://localhost/foowithpatch/1', 'input' => null, 'output' => null, 'api_allow_update' => true, 'deep_object_to_populate' => true, CsvEncoder::AS_COLLECTION_KEY => false, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata', 'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foowithpatch/1', 'PATCH', server: ['CONTENT_TYPE' => 'text/csv']);
        $request->setFormat('csv', ['text/csv']);
        $request->attributes->replace(['_api_resource_class' => 'FooWithPatch', '_api_operation_name' => 'patch_collection', '_api_format' => 'csv', '_api_mime_type' => 'text/csv']);
        $expected = [CsvEncoder::AS_COLLECTION_KEY => true, 'operation_name' => 'patch_collection', 'operation' => $this->patchCollectionOperation, 'resource_class' => 'FooWithPatch', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/foowithpatch/1', 'uri' => 'http://localhost/foowithpatch/1', 'input' => null, 'output' => null, 'api_allow_update' => true, 'deep_object_to_populate' => true, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata', 'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest($request, false));
    }

    public function testThrowExceptionOnInvalidRequest(): void
    {
        $this->expectException(RuntimeException::class);

        $this->builder->createFromRequest(new Request(), false);
    }

    public function testReuseExistingAttributes(): void
    {
        $expected = ['bar' => 'baz', 'operation_name' => 'get', 'operation' => $this->operation, 'resource_class' => 'Foo', 'skip_null_values' => true, 'skip_null_to_one_relations' => true, 'iri_only' => false, 'request_uri' => '/foos/1', 'uri' => 'http://localhost/foos/1', 'input' => null, 'output' => null, 'api_allow_update' => false, 'exclude_from_cache_key' => ['root_operation', 'operation', 'object', 'data', 'property_metadata', 'circular_reference_limit_counters', 'debug_trace_id']];
        $this->assertSame($expected, $this->builder->createFromRequest(Request::create('/foos/1'), false, ['resource_class' => 'Foo', 'operation_name' => 'get']));
    }

    public function testCreateFromRequestKeyCollectDenormalizationErrorsIsInContext(): void
    {
        $operationWithCollectDenormalizationErrors = $this->operation->withCollectDenormalizationErrors(true);
        $request = Request::create('/foos', 'POST');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_operation_name' => 'post', '_api_format' => 'xml', '_api_mime_type' => 'text/xml', '_api_operation' => $operationWithCollectDenormalizationErrors]);
        $serializerContext = $this->builder->createFromRequest($request, false);
        $this->assertArrayHasKey('collect_denormalization_errors', $serializerContext);
        $this->assertTrue($serializerContext['collect_denormalization_errors']);
    }
}
