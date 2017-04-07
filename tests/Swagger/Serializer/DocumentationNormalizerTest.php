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

namespace ApiPlatform\Core\Tests\Swagger;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
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
use ApiPlatform\Core\PathResolver\CustomOperationPathResolver;
use ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Tests\Fixtures\DummyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
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
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', 'http://schema.example.com/Dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'custom')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'custom2')->shouldBeCalled()->willReturn('POST');

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/app_dev.php/')->shouldBeCalled();

        $operationPathResolver = new CustomOperationPathResolver(new UnderscoreOperationPathResolver());

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            $urlGeneratorProphecy->reveal()
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

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithNameConverter()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Dummy API', 'This is a dummy API', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name', 'nameConverted']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', null, ['get' => ['method' => 'GET']], [], []);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, null, null, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'nameConverted')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a converted name.', true, true, null, null, false));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/app_dev.php/')->shouldBeCalled();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('name')->willReturn('name')->shouldBeCalled();
        $nameConverterProphecy->normalize('nameConverted')->willReturn('name_converted')->shouldBeCalled();

        $operationPathResolver = new CustomOperationPathResolver(new UnderscoreOperationPathResolver());

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            $urlGeneratorProphecy->reveal(),
            null,
            $nameConverterProphecy->reveal()
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
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithOnlyNormalizationGroups()
    {
        $title = 'Test API';
        $description = 'This is a test API.';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '1.2.3';
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), $title, $description, $version, $formats);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => 'dummy'])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['gerard']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'gerard')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a gerard.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/app_dev.php/')->shouldBeCalled();

        $operationPathResolver = new CustomOperationPathResolver(new UnderscoreOperationPathResolver());

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            $urlGeneratorProphecy->reveal()
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
        $title = 'Test API';
        $description = 'This is a test API.';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '1.2.3';
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), $title, $description, $version, $formats);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => 'dummy'])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['gerard']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'gerard')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a gerard.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/app_dev.php/')->shouldBeCalled();

        $operationPathResolver = new CustomOperationPathResolver(new UnderscoreOperationPathResolver());

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            $urlGeneratorProphecy->reveal()
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
        $title = 'Test API';
        $description = 'This is a test API.';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '1.2.3';
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), $title, $description, $version, $formats);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => 'dummy'])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['gerard']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'gerard')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a gerard.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/app_dev.php/')->shouldBeCalled();

        $operationPathResolver = new CustomOperationPathResolver(new UnderscoreOperationPathResolver());

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            $urlGeneratorProphecy->reveal()
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
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), '', '', '0.0.0', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            null,
            [],
            ['get' => ['method' => 'GET', 'filters' => ['f1', 'f2']]],
            []
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/')->shouldBeCalled();

        $operationPathResolver = new CustomOperationPathResolver(new UnderscoreOperationPathResolver());

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

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            $urlGeneratorProphecy->reveal(),
            $filters
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
}
