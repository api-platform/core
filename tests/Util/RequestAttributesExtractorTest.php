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

namespace ApiPlatform\Core\Tests\Util;

use ApiPlatform\Core\Util\RequestAttributesExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RequestAttributesExtractorTest extends TestCase
{
    public function testExtractCollectionAttributes()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'collection_operation_name' => 'post',
                'receive' => true,
                'persist' => true,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testExtractItemAttributes()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'persist' => true,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testExtractReceive()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_receive' => '0']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => false,
                'persist' => true,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_receive' => '1']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'persist' => true,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'persist' => true,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testExtractPersist()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_persist' => '0']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'persist' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_persist' => '1']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'persist' => true,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'persist' => true,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testResourceClassNotSet()
    {
        $this->assertEmpty(RequestAttributesExtractor::extractAttributes(new Request([], [], ['_api_item_operation_name' => 'get'])));
    }

    public function testOperationNotSet()
    {
        $this->assertEmpty(RequestAttributesExtractor::extractAttributes(new Request([], [], ['_api_resource_class' => 'Foo'])));
    }
}
