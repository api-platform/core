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

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Swagger\Extractor\CollectionGetOperationExtractor;
use ApiPlatform\Core\Swagger\Extractor\CollectionPostOperationExtractor;
use ApiPlatform\Core\Swagger\Extractor\ItemDeleteOperationExtractor;
use ApiPlatform\Core\Swagger\Extractor\ItemGetOperationExtractor;
use ApiPlatform\Core\Swagger\Extractor\ItemPutOperationExtractor;
use ApiPlatform\Core\Swagger\Extractor\SwaggerContextOperationExtractor;
use ApiPlatform\Core\Swagger\Processor\SwaggerExtractorProcessor;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Swagger\Util\SwaggerDefinitions;
use ApiPlatform\Core\Swagger\Util\SwaggerFilterDefinitions;
use ApiPlatform\Core\Swagger\Util\SwaggerOperationGenerator;
use ApiPlatform\Core\Swagger\Util\SwaggerTypeResolver;
use ApiPlatform\Core\Tests\Fixtures\DummyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DocumentationNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalize()
    {
        $documentation = new Documentation(
            new ResourceNameCollection([Dummy::class]),
            'Test API',
            'This is a test API.',
            '1.2.3',
            ['jsonld' => ['application/ld+json']]
        );

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => ['method' => 'PUT'],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST'],
                'custom' => ['method' => 'GET', 'path' => '/foo'],
                'custom2' => ['method' => 'POST', 'path' => '/foo'],
            ],
            []
        );

        $operationData = [
           ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
           ['resourceClass' => Dummy::class, 'operationName' => 'put', 'operation' => ['method' => 'PUT'], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'PUT', 'mimeTypes' => ['application/ld+json']],
           ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => true, 'path' => '/dummies', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
           ['resourceClass' => Dummy::class, 'operationName' => 'post', 'operation' => ['method' => 'POST'], 'isCollection' => true, 'path' => '/dummies', 'method' => 'POST', 'mimeTypes' => ['application/ld+json']],
           ['resourceClass' => Dummy::class, 'operationName' => 'custom', 'operation' => ['method' => 'GET', 'path' => '/foo'], 'isCollection' => true, 'path' => '/foo', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
           ['resourceClass' => Dummy::class, 'operationName' => 'custom2', 'operation' => ['method' => 'POST', 'path' => '/foo'], 'isCollection' => true, 'path' => '/foo', 'method' => 'POST', 'mimeTypes' => ['application/ld+json']],
        ];
        $swaggerOperationGeneratorProphecy = $this->prophesize(SwaggerOperationGenerator::class);
        $swaggerOperationGeneratorProphecy->generate(Argument::any())->willReturn($operationData);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(
            new PropertyNameCollection(['id', 'name'])
        );

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $typeResolver = new SwaggerTypeResolver(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $swaggerDefinitions = new SwaggerDefinitions(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $typeResolver
        );

        $swaggerFilterDefinitionsProphecy = $this->prophesize(SwaggerFilterDefinitions::class);
        $swaggerFilterDefinitionsProphecy->get(Argument::any())->willReturn([]);

        $swaggerProcessor = $this->getSwaggerProcessor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitions,
            $swaggerFilterDefinitionsProphecy->reveal()
        );

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/app_dev.php/')->shouldBeCalled();

        $normalizer = new DocumentationNormalizer(
            $urlGeneratorProphecy->reveal(),
            $swaggerProcessor,
            $swaggerDefinitions,
            $swaggerOperationGeneratorProphecy->reveal()
        );

        $expected = [
            'swagger' => '2.0',
            'basePath' => '/app_dev.php/',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyCollection',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'responses' => [
                            200 => [
                                'description' => 'Dummy collection response',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/definitions/Dummy'],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'postDummyCollection',
                        'consumes' => ['application/ld+json'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Creates a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'description' => 'The new Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                        ],
                        'responses' => [
                            201 => [
                                'description' => 'Dummy resource created',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'type' => 'integer',
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource response',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                    'put' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'putDummyItem',
                        'consumes' => ['application/ld+json'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Replaces the Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'type' => 'integer',
                                'required' => true,
                            ],
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'description' => 'The updated Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource updated',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/foo' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'customDummyCollection',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'responses' => [
                            200 => [
                                'description' => 'Dummy collection response',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/definitions/Dummy'],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'custom2DummyCollection',
                        'produces' => ['application/ld+json'],
                        'consumes' => ['application/ld+json'],
                        'summary' => 'Creates a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'description' => 'The new Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                        ],
                        'responses' => [
                            201 => [
                                'description' => 'Dummy resource created',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'definitions' => new \ArrayObject([
                'Dummy' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                    'properties' => [
                        'id' => new \ArrayObject([
                            'type' => 'integer',
                            'description' => 'This is an id.',
                            'readOnly' => true,
                        ]),
                        'name' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a name.',
                        ]),
                    ],
                ]),
            ]),
        ];

        $result = $normalizer->normalize($documentation);
        $this->assertEquals($expected, $result);
    }

    public function testNormalizeWithNameConverter()
    {
        $documentation = new Documentation(
            new ResourceNameCollection([Dummy::class]),
            'Dummy API',
            'This is a dummy API',
            '1.2.3',
            ['jsonld' => ['application/ld+json']]
        );

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            null,
            [
                'get' => ['method' => 'GET'],
            ],
            [],
            []
        );

        $operationData = [
            ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
        ];

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('name')->willReturn('name')->shouldBeCalled();
        $nameConverterProphecy->normalize('nameConverted')->willReturn('name_converted')->shouldBeCalled();

        $swaggerOperationGeneratorProphecy = $this->prophesize(SwaggerOperationGenerator::class);
        $swaggerOperationGeneratorProphecy->generate(Argument::any())->willReturn($operationData);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['name', 'nameConverted'])
        );

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, null, null, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'nameConverted')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a converted name.', true, true, null, null, false));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $typeResolver = new SwaggerTypeResolver(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $swaggerDefinitions = new SwaggerDefinitions(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $typeResolver,
            $nameConverterProphecy->reveal(),
            true,
            'oauth2',
            'application',
            '/oauth/v2/token',
            '/oauth/v2/auth',
            ['scope param']
        );

        $swaggerFilterDefinitionsProphecy = $this->prophesize(SwaggerFilterDefinitions::class);
        $swaggerFilterDefinitionsProphecy->get(Argument::any())->willReturn([]);

        $swaggerProcessor = $this->getSwaggerProcessor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitions,
            $swaggerFilterDefinitionsProphecy->reveal()
        );

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/app_dev.php/')->shouldBeCalled();

        $normalizer = new DocumentationNormalizer(
            $urlGeneratorProphecy->reveal(),
            $swaggerProcessor,
            $swaggerDefinitions,
            $swaggerOperationGeneratorProphecy->reveal()
        );

        $expected = [
            'swagger' => '2.0',
            'basePath' => '/app_dev.php/',
            'info' => [
                'title' => 'Dummy API',
                'description' => 'This is a dummy API',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'type' => 'integer',
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource response',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'definitions' => new \ArrayObject([
                'Dummy' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'properties' => [
                        'name' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a name.',
                        ]),
                        'name_converted' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a converted name.',
                        ]),
                    ],
                ]),
            ]),
            'securityDefinitions' => [
                'oauth' => [
                    'type' => 'oauth2',
                    'description' => 'OAuth client_credentials Grant',
                    'flow' => 'application',
                    'tokenUrl' => '/oauth/v2/token',
                    'authorizationUrl' => '/oauth/v2/auth',
                    'scopes' => ['scope param'],
                ],
            ],
            'security' => [['oauth' => []]],
        ];

        $result = $normalizer->normalize($documentation);
        file_put_contents('test.log', print_r($result, true));

        $this->assertEquals($expected, $result);
    }

    public function testNormalizeWithOnlyNormalizationGroups()
    {
        $documentation = new Documentation(
            new ResourceNameCollection([Dummy::class]),
            'Test API',
            'This is a test API.',
            '1.2.3',
            ['jsonld' => ['application/ld+json']]
        );

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => ['method' => 'PUT', 'normalization_context' => ['groups' => 'dummy']],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST'],
            ],
            []
        );

        $operationData = [
            ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'put', 'operation' => ['method' => 'PUT', 'normalization_context' => ['groups' => 'dummy']], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'PUT', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => true, 'path' => '/dummies', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'post', 'operation' => ['method' => 'POST'], 'isCollection' => true, 'path' => '/dummies', 'method' => 'POST', 'mimeTypes' => ['application/ld+json']],
       ];

        $swaggerOperationGeneratorProphecy = $this->prophesize(SwaggerOperationGenerator::class);
        $swaggerOperationGeneratorProphecy->generate(Argument::any())->willReturn($operationData);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => 'dummy'])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['gerard']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'gerard')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a gerard.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $typeResolver = new SwaggerTypeResolver(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $swaggerDefinitions = new SwaggerDefinitions(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $typeResolver
        );

        $swaggerFilterDefinitionsProphecy = $this->prophesize(SwaggerFilterDefinitions::class);
        $swaggerFilterDefinitionsProphecy->get(Argument::any())->willReturn([]);

        $swaggerProcessor = $this->getSwaggerProcessor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitions,
            $swaggerFilterDefinitionsProphecy->reveal()
        );

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/app_dev.php/')->shouldBeCalled();

        $normalizer = new DocumentationNormalizer(
            $urlGeneratorProphecy->reveal(),
            $swaggerProcessor,
            $swaggerDefinitions,
            $swaggerOperationGeneratorProphecy->reveal()
        );

        $expected = [
            'swagger' => '2.0',
            'basePath' => '/app_dev.php/',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => [
                            'Dummy',
                        ],
                        'operationId' => 'getDummyCollection',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'responses' => [
                            200 => [
                                'description' => 'Dummy collection response',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/definitions/Dummy'],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'postDummyCollection',
                        'consumes' => ['application/ld+json'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Creates a Dummy resource.',
                        'parameters' => [[
                            'name' => 'dummy',
                            'in' => 'body',
                            'description' => 'The new Dummy resource',
                            'schema' => ['$ref' => '#/definitions/Dummy'],
                        ]],
                        'responses' => [
                            201 => [
                                'description' => 'Dummy resource created',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [[
                            'name' => 'id',
                            'in' => 'path',
                            'type' => 'integer',
                            'required' => true,
                        ]],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource response',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                    'put' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'putDummyItem',
                        'consumes' => ['application/ld+json'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Replaces the Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'type' => 'integer',
                                'required' => true,
                            ],
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'description' => 'The updated Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource updated',
                                'schema' => ['$ref' => '#/definitions/Dummy_be35824b9d92d1dfc6f78fe086649b8f'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'definitions' => new \ArrayObject([
                'Dummy' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                    'properties' => [
                        'name' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a name.',
                        ]),
                    ],
                ]),
                'Dummy_be35824b9d92d1dfc6f78fe086649b8f' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                    'properties' => [
                        'gerard' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a gerard.',
                        ]),
                    ],
                ]),
            ]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithOnlyDenormalizationGroups()
    {
        $documentation = new Documentation(
            new ResourceNameCollection([Dummy::class]),
            'Test API',
            'This is a test API.',
            '1.2.3',
            ['jsonld' => ['application/ld+json']]
        );

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => ['method' => 'PUT', 'denormalization_context' => ['groups' => 'dummy']],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST'],
            ],
            []
        );
        $operationData = [
            ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'put', 'operation' => ['method' => 'PUT', 'denormalization_context' => ['groups' => 'dummy']], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'PUT', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => true, 'path' => '/dummies', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'post', 'operation' => ['method' => 'POST'], 'isCollection' => true, 'path' => '/dummies', 'method' => 'POST', 'mimeTypes' => ['application/ld+json']],
        ];

        $swaggerOperationGeneratorProphecy = $this->prophesize(SwaggerOperationGenerator::class);
        $swaggerOperationGeneratorProphecy->generate(Argument::any())->willReturn($operationData);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => 'dummy'])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['gerard']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'gerard')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a gerard.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $typeResolver = new SwaggerTypeResolver(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $swaggerDefinitions = new SwaggerDefinitions(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $typeResolver
        );

        $swaggerFilterDefinitionsProphecy = $this->prophesize(SwaggerFilterDefinitions::class);
        $swaggerFilterDefinitionsProphecy->get(Argument::any())->willReturn([]);

        $swaggerProcessor = $this->getSwaggerProcessor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitions,
            $swaggerFilterDefinitionsProphecy->reveal()
        );

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/app_dev.php/')->shouldBeCalled();

        $normalizer = new DocumentationNormalizer(
            $urlGeneratorProphecy->reveal(),
            $swaggerProcessor,
            $swaggerDefinitions,
            $swaggerOperationGeneratorProphecy->reveal()
        );

        $expected = [
            'swagger' => '2.0',
            'basePath' => '/app_dev.php/',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => [
                            'Dummy',
                        ],
                        'operationId' => 'getDummyCollection',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'responses' => [
                            200 => [
                                'description' => 'Dummy collection response',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/definitions/Dummy'],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'postDummyCollection',
                        'consumes' => ['application/ld+json'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Creates a Dummy resource.',
                        'parameters' => [[
                            'name' => 'dummy',
                            'in' => 'body',
                            'description' => 'The new Dummy resource',
                            'schema' => ['$ref' => '#/definitions/Dummy'],
                        ]],
                        'responses' => [
                            201 => [
                                'description' => 'Dummy resource created',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [[
                            'name' => 'id',
                            'in' => 'path',
                            'type' => 'integer',
                            'required' => true,
                        ]],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource response',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                    'put' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'putDummyItem',
                        'consumes' => ['application/ld+json'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Replaces the Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'type' => 'integer',
                                'required' => true,
                            ],
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'description' => 'The updated Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy_be35824b9d92d1dfc6f78fe086649b8f'],
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource updated',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'definitions' => new \ArrayObject([
                'Dummy' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                    'properties' => [
                        'name' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a name.',
                        ]),
                    ],
                ]),
                'Dummy_be35824b9d92d1dfc6f78fe086649b8f' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                    'properties' => [
                        'gerard' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a gerard.',
                        ]),
                    ],
                ]),
            ]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithNormalizationAndDenormalizationGroups()
    {
        $documentation = new Documentation(
            new ResourceNameCollection([Dummy::class]),
            'Test API',
            'This is a test API.',
            '1.2.3',
            ['jsonld' => ['application/ld+json']]
        );

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => [
                    'method' => 'PUT',
                    'normalization_context' => ['groups' => 'dummy'], 'denormalization_context' => ['groups' => 'dummy'],
                ],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST'],
            ],
            []
        );

        $operationData = [
            ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'put', 'operation' => ['method' => 'PUT', 'normalization_context' => ['groups' => 'dummy'], 'denormalization_context' => ['groups' => 'dummy']], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'PUT', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => true, 'path' => '/dummies', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'post', 'operation' => ['method' => 'POST'], 'isCollection' => true, 'path' => '/dummies', 'method' => 'POST', 'mimeTypes' => ['application/ld+json']],
        ];

        $swaggerOperationGeneratorProphecy = $this->prophesize(SwaggerOperationGenerator::class);
        $swaggerOperationGeneratorProphecy->generate(Argument::any())->willReturn($operationData);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => 'dummy'])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['gerard']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'gerard')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a gerard.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $typeResolver = new SwaggerTypeResolver(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $swaggerDefinitions = new SwaggerDefinitions(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $typeResolver
        );

        $swaggerFilterDefinitionsProphecy = $this->prophesize(SwaggerFilterDefinitions::class);
        $swaggerFilterDefinitionsProphecy->get(Argument::any())->willReturn([]);

        $swaggerProcessor = $this->getSwaggerProcessor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitions,
            $swaggerFilterDefinitionsProphecy->reveal()
        );

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/app_dev.php/')->shouldBeCalled();

        $normalizer = new DocumentationNormalizer(
            $urlGeneratorProphecy->reveal(),
            $swaggerProcessor,
            $swaggerDefinitions,
            $swaggerOperationGeneratorProphecy->reveal()
        );

        $expected = [
            'swagger' => '2.0',
            'basePath' => '/app_dev.php/',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => [
                            'Dummy',
                        ],
                        'operationId' => 'getDummyCollection',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'responses' => [
                            200 => [
                                'description' => 'Dummy collection response',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/definitions/Dummy'],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'postDummyCollection',
                        'consumes' => ['application/ld+json'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Creates a Dummy resource.',
                        'parameters' => [[
                            'name' => 'dummy',
                            'in' => 'body',
                            'description' => 'The new Dummy resource',
                            'schema' => ['$ref' => '#/definitions/Dummy'],
                        ]],
                        'responses' => [
                            201 => [
                                'description' => 'Dummy resource created',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [[
                            'name' => 'id',
                            'in' => 'path',
                            'type' => 'integer',
                            'required' => true,
                        ]],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource response',
                                'schema' => ['$ref' => '#/definitions/Dummy'],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                    'put' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'putDummyItem',
                        'consumes' => ['application/ld+json'],
                        'produces' => ['application/ld+json'],
                        'summary' => 'Replaces the Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'type' => 'integer',
                                'required' => true,
                            ],
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'description' => 'The updated Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy_be35824b9d92d1dfc6f78fe086649b8f'],
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource updated',
                                'schema' => ['$ref' => '#/definitions/Dummy_be35824b9d92d1dfc6f78fe086649b8f'],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'definitions' => new \ArrayObject([
                'Dummy' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                    'properties' => [
                        'name' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a name.',
                        ]),
                    ],
                ]),
                'Dummy_be35824b9d92d1dfc6f78fe086649b8f' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                    'properties' => [
                        'gerard' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a gerard.',
                        ]),
                    ],
                ]),
            ]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testFilters()
    {
        $documentation = new Documentation(
            new ResourceNameCollection([Dummy::class]),
            '',
            '',
            '0.0.0',
            ['jsonld' => ['application/ld+json']]
        );

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            null,
            [],
            ['get' => ['method' => 'GET', 'filters' => ['f1', 'f2']]],
            []
        );

        $operationData = [
            ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET', 'filters' => ['f1', 'f2']], 'isCollection' => true, 'path' => '/dummies', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
        ];

        $swaggerOperationGeneratorProphecy = $this->prophesize(SwaggerOperationGenerator::class);
        $swaggerOperationGeneratorProphecy->generate(Argument::any())->willReturn($operationData);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $typeResolver = new SwaggerTypeResolver(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $swaggerDefinitions = new SwaggerDefinitions(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $typeResolver
        );

        $filters = new FilterCollection([
            'f1' => new DummyFilter(['name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => true,
                'strategy' => 'exact',
                'swagger' => ['x-foo' => 'bar'],
            ]]),
            'f2' => new DummyFilter(['ha' => [
                'property' => 'foo',
                'type' => 'int',
                'required' => false,
                'strategy' => 'partial',
            ]]),
        ]);

        $swaggerFilterDefinitions = new SwaggerFilterDefinitions(
            $resourceMetadataFactoryProphecy->reveal(),
            $typeResolver,
            $filters
        );

        $swaggerProcessor = $this->getSwaggerProcessor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitions,
            $swaggerFilterDefinitions
        );

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/')->shouldBeCalled();

        $normalizer = new DocumentationNormalizer(
            $urlGeneratorProphecy->reveal(),
            $swaggerProcessor,
            $swaggerDefinitions,
            $swaggerOperationGeneratorProphecy->reveal()
        );

        $expected = [
            'swagger' => '2.0',
            'basePath' => '/',
            'info' => [
                'title' => '',
                'version' => '0.0.0',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyCollection',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'responses' => [
                            200 => [
                                'description' => 'Dummy collection response',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/definitions/Dummy'],
                                ],
                            ],
                        ],
                        'parameters' => [
                            [
                                'x-foo' => 'bar',
                                'name' => 'name',
                                'in' => 'query',
                                'required' => true,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'ha',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'integer',
                            ],
                        ],
                    ]),
                ],
            ]),
            'definitions' => new \ArrayObject([
                'Dummy' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'properties' => [
                        'name' => new \ArrayObject([
                            'description' => 'This is a name.',
                            'type' => 'string',
                        ]),
                    ],
                ]),
            ]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    /**
     * @param $resourceMetadataFactory
     * @param $swaggerDefinitions
     * @param $swaggerFilterDefinitions
     *
     * @return SwaggerExtractorProcessor
     */
    private function getSwaggerProcessor($resourceMetadataFactory, $swaggerDefinitions, $swaggerFilterDefinitions): SwaggerExtractorProcessor
    {
        $collectionGetOperationExtractor = new CollectionGetOperationExtractor(
            $resourceMetadataFactory,
            $swaggerDefinitions,
            $swaggerFilterDefinitions
        );

        $collectionPostOperationExtractor = new CollectionPostOperationExtractor(
            $resourceMetadataFactory,
            $swaggerDefinitions
        );
        $itemDeleteOperationExtractor = new ItemDeleteOperationExtractor(
            $resourceMetadataFactory
        );
        $itemGetOperationExtractor = new ItemGetOperationExtractor(
            $resourceMetadataFactory,
            $swaggerDefinitions
        );
        $itemPutOperationExtractor = new ItemPutOperationExtractor(
            $resourceMetadataFactory,
            $swaggerDefinitions
        );

        $swaggerContextOperationExtractor = new SwaggerContextOperationExtractor();

        $swaggerProcessor = new SwaggerExtractorProcessor([
            $swaggerContextOperationExtractor,
            $collectionGetOperationExtractor,
            $collectionPostOperationExtractor,
            $itemDeleteOperationExtractor,
            $itemGetOperationExtractor,
            $itemPutOperationExtractor,
        ]);

        return $swaggerProcessor;
    }
}
