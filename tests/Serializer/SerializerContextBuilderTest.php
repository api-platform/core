<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Serializer;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializerContextBuilderTest extends \PHPUnit_Framework_TestCase
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
            ['normalization_context' => ['foo' => 'bar'], 'denormalization_context' => ['bar' => 'baz']]
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata);

        $this->builder = new SerializerContextBuilder($resourceMetadataFactoryProphecy->reveal());
    }

    public function testCreateFromRequest()
    {
        $request = new Request([], [], ['_resource_class' => 'Foo', '_item_operation_name' => 'get', '_api_format' => 'xml']);
        $expected = ['foo' => 'bar', 'item_operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => ''];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, true));

        $request = new Request([], [], ['_resource_class' => 'Foo', '_collection_operation_name' => 'pot', '_api_format' => 'xml']);
        $expected = ['foo' => 'bar', 'collection_operation_name' => 'pot',  'resource_class' => 'Foo', 'request_uri' => ''];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, true));

        $request = new Request([], [], ['_resource_class' => 'Foo', '_item_operation_name' => 'get', '_api_format' => 'xml']);
        $expected = ['bar' => 'baz', 'item_operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => ''];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = new Request([], [], ['_resource_class' => 'Foo', '_collection_operation_name' => 'post', '_api_format' => 'xml']);
        $expected = ['bar' => 'baz', 'collection_operation_name' => 'post',  'resource_class' => 'Foo', 'request_uri' => ''];
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
        $expected = ['bar' => 'baz', 'item_operation_name' => 'get', 'resource_class' => 'Foo', 'request_uri' => ''];
        $this->assertEquals($expected, $this->builder->createFromRequest(new Request(), false, ['Foo', null, 'get']));
    }
}
