<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RequestAttributesExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function testExtractCollectionAttributes()
    {
        $request = new Request([], [], ['_resource_class' => 'Foo', '_collection_operation_name' => 'post', '_api_format' => 'json']);
        $extactor = new RequestAttributesExtractor();

        $this->assertEquals(['Foo', 'post', null, 'json'], $extactor->extractAttributes($request));
    }

    public function testExtractItemAttributes()
    {
        $request = new Request([], [], ['_resource_class' => 'Foo', '_item_operation_name' => 'get', '_api_format' => 'json']);
        $extactor = new RequestAttributesExtractor();

        $this->assertEquals(['Foo', null, 'get', 'json'], $extactor->extractAttributes($request));
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage The request attribute "_resource_class" must be defined.
     */
    public function testResourceClassNotSet()
    {
        $request = new Request([], [], ['_item_operation_name' => 'get', '_api_format' => 'json']);
        $extactor = new RequestAttributesExtractor();
        $extactor->extractAttributes($request);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage One of the request attribute "_item_operation_name" or "_collection_operation_name" must be defined.
     */
    public function testOperationNotSet()
    {
        $request = new Request([], [], ['_resource_class' => 'Foo', '_api_format' => 'json']);
        $extactor = new RequestAttributesExtractor();
        $extactor->extractAttributes($request);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage The request attribute "_api_format" must be defined.
     */
    public function testFormatNotSet()
    {
        $request = new Request([], [], ['_resource_class' => 'Foo', '_collection_operation_name' => 'op']);
        $extactor = new RequestAttributesExtractor();
        $extactor->extractAttributes($request);
    }
}
