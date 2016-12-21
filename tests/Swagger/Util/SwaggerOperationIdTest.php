<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Swagger\Util;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Swagger\Util\SwaggerOperationId;
use Symfony\Component\Serializer\Tests\Fixtures\Dummy;

class SwaggerOperationIdTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateSwaggerOperationIdForItem()
    {
        $resourceMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
            ],
            [],
            []
        );

        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => false,
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $result = SwaggerOperationId::create($operationData, $resourceMetadata);
        $this->assertEquals('getDummyItem', $result);
    }

    public function testCreateSwaggerOperationIdForCollection()
    {
        $resourceMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
            ],
            [],
            []
        );
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => true,
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $result = SwaggerOperationId::create($operationData, $resourceMetadata);
        $this->assertEquals('getDummyCollection', $result);
    }
}
