<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Swagger;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Swagger\DocumentationNormalizer;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class DocumentationNormalizerTest extends \PHPUnit_Framework_TestCase /**/
{
    public function testNormalize()
    {
        $title = 'Test Api';
        $desc = 'test ApiGerard';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '0.0.0';
        $documentation = new Documentation($title, $desc, $version, $formats);
        $documentation = $documentation->create(new ResourceNameCollection(['dummy' => 'dummy']));

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create('dummy', [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create('dummy', 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'name', true, true, true, true, false, false, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod('dummy', 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod('dummy', 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod('dummy', 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod('dummy', 'post')->shouldBeCalled()->willReturn('POST');

        $iriConverter = $this->prophesize(IriConverterInterface::class);
        $iriConverter->getIriFromResourceClass('dummy')->shouldBeCalled()->willReturn('/dummies');

        $apiDocumentationBuilder = new DocumentationNormalizer($resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $operationMethodResolverProphecy->reveal(), $iriConverter->reveal());

        $expected = [
            'swagger' => '2.0',
            'info' => [
                'title' => 'Test Api',
                'description' => 'test ApiGerard',
                'version' => '0.0.0',
            ],
            'definitions' => [
                'dummy' => [
                    'type' => 'object',
                    'xml' => ['name' => 'response'],
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
            ],
            'externalDocs' => [
                'description' => 'Find more about API Platform',
                'url' => 'https://api-platform.com',
            ],
            'tags' => [
                [
                    'name' => 'dummy',
                    'description' => 'dummy',
                    'externalDocs' => ['url' => '#dummy'],
                ],
            ],
            'paths' => [
                '/dummies' => [
                    'get' => [
                        'tags' => ['dummy'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves the collection of dummy resources.',
                        'responses' => [
                            200 => [
                                'description' => 'Successful operation',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/definitions/dummy'],
                                ],
                            ],
                        ],
                    ],
                    'post' => [
                        'tags' => ['dummy'],
                        'produces' => ['application/ld+json'],
                        'consumes' => ['application/ld+json'],
                        'summary' => 'Creates a dummy resource.',
                        'parameters' => [
                            [
                                'in' => 'body',
                                'name' => 'body',
                                'description' => 'dummy resource to be added',
                                'schema' => ['$ref' => '#/definitions/dummy'],
                            ],
                        ],
                        'responses' => [
                            201 => [
                                'description' => 'Successful operation',
                                'schema' => ['$ref' => '#/definitions/dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ],
                ],
                '/dummies/{id}' => [
                    'get' => [
                        'tags' => ['dummy'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'type' => 'integer',
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Successful operation',
                                'schema' => ['$ref' => '#/definitions/dummy'],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ],
                    'put' => [
                        'tags' => ['dummy'],
                        'produces' => ['application/ld+json'],
                        'consumes' => ['application/ld+json'],
                        'summary' => 'Replaces the dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'type' => 'integer',
                            ],
                            [
                                'in' => 'body',
                                'name' => 'body',
                                'description' => 'dummy resource to be added',
                                'schema' => ['$ref' => '#/definitions/dummy'],
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Successful operation',
                                'schema' => ['$ref' => '#/definitions/dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $apiDocumentationBuilder->normalize($documentation));
    }
}
