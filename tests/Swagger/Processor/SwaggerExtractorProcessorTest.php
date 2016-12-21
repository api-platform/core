<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Swagger\Processor;

use ApiPlatform\Core\Swagger\Extractor\SwaggerOperationExtractorInterface;
use ApiPlatform\Core\Swagger\Processor\SwaggerExtractorProcessor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

class SwaggerExtractorProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $operationData = ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']];
        $extractedPath = new \ArrayObject([
            '/dummies/{id}' => [
                'get' => new \ArrayObject([
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'type' => 'integer',
                            'required' => true,
                        ],
                    ],
                ]),
            ],
        ]);

        $firstExtractorProphecy = $this->prophesize(SwaggerOperationExtractorInterface::class);
        $firstExtractorProphecy->supportsExtraction($operationData)->shouldBeCalled()->willReturn(true);
        $firstExtractorProphecy->extract($operationData)->shouldBeCalled()->willReturn($extractedPath);

        $secondExtractorProphecy = $this->prophesize(SwaggerOperationExtractorInterface::class);
        $secondExtractorProphecy->supportsExtraction($operationData)->shouldBeCalled()->willReturn(false);
        $secondExtractorProphecy->extract($operationData)->shouldNotBeCalled();

        $processor = new SwaggerExtractorProcessor([
            $firstExtractorProphecy->reveal(),
            $secondExtractorProphecy->reveal(),
        ]);

        $result = $processor->process([$operationData]);
        $this->assertEquals($extractedPath, $result);
    }

    public function testExtractorsResultMerge()
    {
        $operationData = ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']];
        $firstExtractedPath = new \ArrayObject([
            '/dummies/{id}' => [
                'get' => new \ArrayObject([
                    'parameters' => [
                        [
                            'name' => 'firstId',
                            'in' => 'firstPath',
                            'type' => 'firstInteger',
                            'required' => true,
                        ],
                    ],
                ]),
            ],
        ]);

        $firstExtractorProphecy = $this->prophesize(SwaggerOperationExtractorInterface::class);
        $firstExtractorProphecy->supportsExtraction($operationData)->shouldBeCalled()->willReturn(true);
        $firstExtractorProphecy->extract($operationData)->shouldBeCalled()->willReturn($firstExtractedPath);

        $secondExtractedPath = new \ArrayObject([
            '/dummies/{id}' => [
                'get' => new \ArrayObject([
                    'produces' => ['secondProduce'],
                    'parameters' => [
                        [
                            'name' => 'secondId',
                            'in' => 'secondPath',
                            'type' => 'secondInteger',
                            'required' => true,
                        ],
                        [
                            'name' => 'secondName',
                            'in' => 'secondPath',
                            'type' => 'secondString',
                            'required' => true,
                        ],
                    ],
                ]),
            ],
        ]);
        $secondExtractorProphecy = $this->prophesize(SwaggerOperationExtractorInterface::class);
        $secondExtractorProphecy->supportsExtraction($operationData)->shouldBeCalled()->willReturn(true);
        $secondExtractorProphecy->extract($operationData)->shouldBeCalled()->willReturn($secondExtractedPath);

        $processor = new SwaggerExtractorProcessor([
            $firstExtractorProphecy->reveal(),
            $secondExtractorProphecy->reveal(),
        ]);

        $expectedResult = new \ArrayObject([
            '/dummies/{id}' => [
                'get' => new \ArrayObject([
                    'produces' => ['secondProduce'],
                    'parameters' => [
                        [
                            'name' => 'firstId',
                            'in' => 'firstPath',
                            'type' => 'firstInteger',
                            'required' => true,
                        ],
                    ],
                ]),
            ],
        ]);

        $result = $processor->process([$operationData]);
        $this->assertEquals($expectedResult, $result);
    }
}
