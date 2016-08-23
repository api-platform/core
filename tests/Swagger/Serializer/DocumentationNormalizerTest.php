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

use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\PathResolver\CustomOperationPathResolver;
use ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class DocumentationNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalize()
    {
        $title = 'Test API';
        $description = 'This is a test API.';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '1.2.3';
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), $title, $description, $version, $formats);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', 'http://schema.example.com/Dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'custom')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'custom2')->shouldBeCalled()->willReturn('POST');

        $operationPathResolver = new CustomOperationPathResolver(new UnderscoreOperationPathResolver());

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver
        );

        $expected = [
            'swagger' => '2.0',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'definitions' => [
                'Dummy' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'externalDocs' => [
                        'url' => 'http://schema.example.com/Dummy',
                    ],
                    'properties' => [
                        'name' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a name.',
                        ]),
                    ],
                ]),
            ],
            'paths' => [
                '/dummies' => [
                    'get' => [
                        'tags' => ['Dummy'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'responses' => [
                            200 => [
                                'description' => 'Successful operation',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/definitions/Dummy'],
                                ],
                            ],
                        ],
                    ],
                    'post' => [
                        'tags' => ['Dummy'],
                        'produces' => ['application/ld+json'],
                        'consumes' => ['application/ld+json'],
                        'summary' => 'Creates a Dummy resource.',
                        'parameters' => [
                            [
                                'in' => 'body',
                                'name' => 'body',
                                'description' => 'The new Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                        ],
                        'responses' => [
                            201 => [
                                'description' => 'Successful operation',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ],
                ],
                '/dummies/{id}' => [
                    'get' => [
                        'tags' => ['Dummy'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves a Dummy resource.',
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
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ],
                    'put' => [
                        'tags' => ['Dummy'],
                        'produces' => ['application/ld+json'],
                        'consumes' => ['application/ld+json'],
                        'summary' => 'Replaces the Dummy resource.',
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
                                'description' => 'The updated Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Successful operation',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ],
                ],
                '/foo' => [
                    'get' => [
                        'tags' => ['Dummy'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'responses' => [
                            200 => [
                                'description' => 'Successful operation',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/definitions/Dummy'],
                                ],
                            ],
                        ],
                    ],
                    'post' => [
                        'tags' => ['Dummy'],
                        'produces' => ['application/ld+json'],
                        'consumes' => ['application/ld+json'],
                        'summary' => 'Creates a Dummy resource.',
                        'parameters' => [
                            [
                                'in' => 'body',
                                'name' => 'body',
                                'description' => 'The new Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                        ],
                        'responses' => [
                            201 => [
                                'description' => 'Successful operation',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }
}
