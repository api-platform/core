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

namespace ApiPlatform\Core\Tests\Serializer;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextBuilder;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializerContextBuilderTest extends TestCase
{
    /**
     * @var SerializerContextBuilder
     */
    private $builder;

    protected function setUp()
    {
        $resourceMetadata = new ResourceMetadata(
            null,
            null,
            null,
            [],
            [],
            [
                'normalization_context' => ['foo' => 'bar', DocumentationNormalizer::SWAGGER_DEFINITION_NAME => 'MyDefinition'],
                'denormalization_context' => ['bar' => 'baz'],
            ]
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata);

        $this->builder = new SerializerContextBuilder($resourceMetadataFactoryProphecy->reveal());
    }

    public function testCreateFromRequest()
    {
        $request = Request::create('/foos/1');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['foo' => 'bar', 'item_operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => '/foos/1', 'operation_type' => 'item', 'uri' => 'http://localhost/foos/1'];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, true));

        $request = Request::create('/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'pot', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['foo' => 'bar', 'collection_operation_name' => 'pot',  'resource_class' => 'Foo', 'request_uri' => '/foos', 'operation_type' => 'collection', 'uri' => 'http://localhost/foos'];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, true));

        $request = Request::create('/foos/1');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'item_operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => '/foos/1', 'api_allow_update' => false, 'operation_type' => 'item', 'uri' => 'http://localhost/foos/1'];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foos', 'POST');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'collection_operation_name' => 'post',  'resource_class' => 'Foo', 'request_uri' => '/foos', 'api_allow_update' => false, 'operation_type' => 'collection', 'uri' => 'http://localhost/foos'];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foos', 'PUT');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'put', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'collection_operation_name' => 'put', 'resource_class' => 'Foo', 'request_uri' => '/foos', 'api_allow_update' => true, 'operation_type' => 'collection', 'uri' => 'http://localhost/foos'];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/bars/1/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'subresource_operation_name' => 'get', 'resource_class' => 'Foo', 'request_uri' => '/bars/1/foos', 'operation_type' => 'subresource', 'api_allow_update' => false, 'uri' => 'http://localhost/bars/1/foos'];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     */
    public function testThrowExceptionOnInvalidRequest()
    {
        $this->builder->createFromRequest(new Request(), false);
    }

    public function testReuseExistingAttributes()
    {
        $expected = ['bar' => 'baz', 'item_operation_name' => 'get', 'resource_class' => 'Foo', 'request_uri' => '/foos/1', 'api_allow_update' => false, 'operation_type' => 'item', 'uri' => 'http://localhost/foos/1'];
        $this->assertEquals($expected, $this->builder->createFromRequest(Request::create('/foos/1'), false, ['resource_class' => 'Foo', 'item_operation_name' => 'get']));
    }
}
