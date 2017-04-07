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
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RequestAttributesExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function testExtractCollectionAttributes()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post']);

        $this->assertEquals(
            ['resource_class' => 'Foo', 'collection_operation_name' => 'post'],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testExtractItemAttributes()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);

        $this->assertEquals(
            ['resource_class' => 'Foo', 'item_operation_name' => 'get'],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage The request attribute "_api_resource_class" must be defined.
     */
    public function testResourceClassNotSet()
    {
        RequestAttributesExtractor::extractAttributes(new Request([], [], ['_api_item_operation_name' => 'get']));
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage One of the request attribute "_api_collection_operation_name" or "_api_item_operation_name" must be defined.
     */
    public function testOperationNotSet()
    {
        RequestAttributesExtractor::extractAttributes(new Request([], [], ['_api_resource_class' => 'Foo']));
    }
}
