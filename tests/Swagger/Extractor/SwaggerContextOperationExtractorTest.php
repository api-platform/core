<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Swagger\Extractor;

use ApiPlatform\Core\Swagger\Extractor\SwaggerContextOperationExtractor;
use Symfony\Component\Serializer\Tests\Fixtures\Dummy;

class SwaggerContextOperationExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET', 'swagger_context' => ['dummy']],
            'isCollection' => false,
            'path' => '/dummies/{id}',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $swaggerContextOperationExtractor = new SwaggerContextOperationExtractor();

        $this->assertTrue($swaggerContextOperationExtractor->supportsExtraction($operationData));

        $operationData['operation'] = ['method' => 'GET'];
        $this->assertFalse($swaggerContextOperationExtractor->supportsExtraction($operationData));
    }

    public function testExtract()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET', 'swagger_context' => ['summary' => 'operationSummary', 'operationId' => 'operationId']],
            'isCollection' => false,
            'path' => '/dummies/{id}',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $swaggerContextOperationExtractor = new SwaggerContextOperationExtractor();

        $expectedResult = new \ArrayObject([
            '/dummies/{id}' => [
                'get' => new \ArrayObject([
                    'operationId' => 'operationId',
                    'summary' => 'operationSummary',
                ]),
            ],
        ]);

        $this->assertEquals($expectedResult, $swaggerContextOperationExtractor->extract($operationData));
    }
}
