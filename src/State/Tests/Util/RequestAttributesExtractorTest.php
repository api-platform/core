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

namespace ApiPlatform\State\Tests\Util;

use ApiPlatform\State\Util\RequestAttributesExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RequestAttributesExtractorTest extends TestCase
{
    public function testExtractCollectionAttributes(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'post']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'post',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));
    }

    public function testExtractItemAttributes(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));
    }

    public function testExtractReceive(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_receive' => '0']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => false,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_receive' => '1']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));
    }

    public function testExtractRespond(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_respond' => '0']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => false,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_respond' => '1']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));
    }

    public function testExtractPersist(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_persist' => '0']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => false,
        ], RequestAttributesExtractor::extractAttributes($request));

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_persist' => '1']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));
    }

    public function testResourceClassNotSet(): void
    {
        $this->assertEmpty(RequestAttributesExtractor::extractAttributes(new Request([], [], ['_api_operation_name' => 'get'])));
    }

    public function testOperationNotSet(): void
    {
        $this->assertEmpty(RequestAttributesExtractor::extractAttributes(new Request([], [], ['_api_resource_class' => 'Foo'])));
    }

    public function testExtractPreviousDataAttributes(): void
    {
        $object = new \stdClass();
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', 'previous_data' => $object]);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'previous_data' => $object,
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));
    }

    public function testExtractIdentifiers(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_identifiers' => ['test'], '_api_has_composite_identifier' => true]);

        $this->assertSame([
            'resource_class' => 'Foo',
            'has_composite_identifier' => true,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], RequestAttributesExtractor::extractAttributes($request));
    }
}
