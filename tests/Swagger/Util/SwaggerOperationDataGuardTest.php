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

use ApiPlatform\Core\Swagger\Util\SwaggerOperationDataGuard;
use Symfony\Component\Serializer\Tests\Fixtures\Dummy;

class SwaggerOperationDataGuardTest extends \PHPUnit_Framework_TestCase
{
    public function testAllNeededKeyExist()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => false,
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $this->assertTrue(SwaggerOperationDataGuard::check($operationData));
    }

    public function testMissingResourceClass()
    {
        $operationData = [
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => false,
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $this->assertFalse(SwaggerOperationDataGuard::check($operationData));
    }

    public function testMissingOperationName()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operation' => ['method' => 'GET'],
            'isCollection' => false,
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $this->assertFalse(SwaggerOperationDataGuard::check($operationData));
    }

    public function testMissingOperation()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'isCollection' => false,
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $this->assertFalse(SwaggerOperationDataGuard::check($operationData));
    }

    public function testMissingIsCollection()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $this->assertFalse(SwaggerOperationDataGuard::check($operationData));
    }

    public function testMissingPath()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => false,
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $this->assertFalse(SwaggerOperationDataGuard::check($operationData));
    }

    public function testMissingMethod()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => false,
            'path' => '/dummies',
            'mimeTypes' => ['application/ld+json'],
        ];

        $this->assertFalse(SwaggerOperationDataGuard::check($operationData));
    }

    public function testMissingMimeTypes()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => false,
            'path' => '/dummies',
            'method' => 'GET',
        ];

        $this->assertFalse(SwaggerOperationDataGuard::check($operationData));
    }
}
