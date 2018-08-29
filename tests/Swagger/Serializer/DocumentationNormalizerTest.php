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

namespace ApiPlatform\Core\Tests\Swagger\Serializer;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouterOperationPathResolver;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactory;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\PathResolver\CustomOperationPathResolver;
use ApiPlatform\Core\PathResolver\OperationPathResolver;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Tests\Fixtures\DummyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Answer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Question;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DocumentationNormalizerTest extends TestCase
{
    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of ApiPlatform\Core\Api\UrlGeneratorInterface to ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer::__construct() is deprecated since version 2.1 and will be removed in 3.0.
     */
    public function testLegacyConstruct()
    {
        $normalizer = new DocumentationNormalizer(
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(OperationMethodResolverInterface::class)->reveal(),
            $this->prophesize(OperationPathResolverInterface::class)->reveal(),
            $this->prophesize(UrlGeneratorInterface::class)->reveal()
        );

        $this->assertInstanceOf(DocumentationNormalizer::class, $normalizer);
    }

    public function testNormalize()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name', 'description']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', 'http://schema.example.com/Dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']], ['pagination_client_items_per_page' => true]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'description')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is an initializable but not writable property.', true, false, true, true, false, false, null, null, [], null, true));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'custom')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'custom2')->shouldBeCalled()->willReturn('POST');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

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
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'integer',
                                'description' => 'The collection page number',
                            ],
                            [
                                'name' => 'itemsPerPage',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'integer',
                                'description' => 'The number of items per page',
                            ],
                        ],
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
                                'type' => 'string',
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
                                'type' => 'string',
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
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'integer',
                                'description' => 'The collection page number',
                            ],
                            [
                                'name' => 'itemsPerPage',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'integer',
                                'description' => 'The number of items per page',
                            ],
                        ],
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
                        'description' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is an initializable but not writable property.',
                        ]),
                    ],
                ]),
            ]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['base_url' => '/app_dev.php/']));
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

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('name')->willReturn('name')->shouldBeCalled();
        $nameConverterProphecy->normalize('nameConverted')->willReturn('name_converted')->shouldBeCalled();

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            $nameConverterProphecy->reveal(),
            true,
            'oauth2',
            'application',
            '/oauth/v2/token',
            '/oauth/v2/auth',
            ['scope param']
        );

        $expected = [
            'swagger' => '2.0',
            'basePath' => '/',
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
                                'type' => 'string',
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

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithApiKeysEnabled()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', null, ['get' => ['method' => 'GET']], [], []);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, null, null, false));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $apiKeysConfiguration = [
            'header' => [
                'type' => 'header',
                'name' => 'Authorization',
            ],
            'query' => [
                'type' => 'query',
                'name' => 'key',
            ],
        ];

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            null,
            null,
            null,
            null,
            [],
            $apiKeysConfiguration
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
                                'type' => 'string',
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
                    ],
                ]),
            ]),
            'securityDefinitions' => [
                'header' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'description' => 'Value for the Authorization header',
                    'name' => 'Authorization',
                ],
                'query' => [
                    'type' => 'apiKey',
                    'in' => 'query',
                    'description' => 'Value for the key query parameter',
                    'name' => 'key',
                ],
            ],
            'security' => [
                ['header' => []],
                ['query' => []],
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['base_url' => '/app_dev.php/']));
    }

    public function testNormalizeWithOnlyNormalizationGroups()
    {
        $title = 'Test API';
        $description = 'This is a test API.';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '1.2.3';
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), $title, $description, $version, $formats);
        $groups = ['dummy', 'foo', 'bar'];

        $ref = 'Dummy-'.implode('_', $groups);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => $groups])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['gerard']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => ['method' => 'PUT', 'normalization_context' => [AbstractNormalizer::GROUPS => $groups]],
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

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

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
            'basePath' => '/',
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
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'integer',
                                'description' => 'The collection page number',
                            ],
                        ],
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
                            'type' => 'string',
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
                                'type' => 'string',
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
                                'schema' => ['$ref' => '#/definitions/'.$ref],
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
                $ref => new \ArrayObject([
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

    public function testNormalizeWithSwaggerDefinitionName()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['id']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => [
                    'method' => 'GET',
                    'normalization_context' => [
                        DocumentationNormalizer::SWAGGER_DEFINITION_NAME => 'Read',
                    ],
                ],
            ]
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

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
            'basePath' => '/app_dev.php/',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
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
                                'type' => 'string',
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource response',
                                'schema' => ['$ref' => '#/definitions/Dummy-Read'],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'definitions' => new \ArrayObject([
                'Dummy-Read' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                    'properties' => [
                        'id' => new \ArrayObject([
                            'type' => 'integer',
                            'description' => 'This is an id.',
                            'readOnly' => true,
                        ]),
                    ],
                ]),
            ]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['base_url' => '/app_dev.php/']));
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
                'put' => ['method' => 'PUT', 'denormalization_context' => [AbstractNormalizer::GROUPS => 'dummy']],
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

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

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
            'basePath' => '/',
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
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'integer',
                                'description' => 'The collection page number',
                            ],
                        ],
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
                            'type' => 'string',
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
                                'type' => 'string',
                                'required' => true,
                            ],
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'description' => 'The updated Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy-dummy'],
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
                'Dummy-dummy' => new \ArrayObject([
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
                    'normalization_context' => [AbstractNormalizer::GROUPS => 'dummy'], 'denormalization_context' => [AbstractNormalizer::GROUPS => 'dummy'],
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

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

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
            'basePath' => '/',
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
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'integer',
                                'description' => 'The collection page number',
                            ],
                        ],
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
                            'type' => 'string',
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
                                'type' => 'string',
                                'required' => true,
                            ],
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'description' => 'The updated Dummy resource',
                                'schema' => ['$ref' => '#/definitions/Dummy-dummy'],
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource updated',
                                'schema' => ['$ref' => '#/definitions/Dummy-dummy'],
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
                'Dummy-dummy' => new \ArrayObject([
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
        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filters = [
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
        ];

        foreach ($filters as $filterId => $filter) {
            $filterLocatorProphecy->has($filterId)->willReturn(true)->shouldBeCalled();
            $filterLocatorProphecy->get($filterId)->willReturn($filter)->shouldBeCalled();
        }

        $filterLocatorProphecy->has('f3')->willReturn(false)->shouldBeCalled();

        $this->normalizeWithFilters($filterLocatorProphecy->reveal());
    }

    /**
     * @group legacy
     * @expectedDeprecation The ApiPlatform\Core\Api\FilterCollection class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of Psr\Container\ContainerInterface instead.
     */
    public function testFiltersWithDeprecatedFilterCollection()
    {
        $this->normalizeWithFilters(new FilterCollection([
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
        ]));
    }

    public function testConstructWithInvalidFilterLocator()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "$filterLocator" argument is expected to be an implementation of the "Psr\\Container\\ContainerInterface" interface or null.');

        new DocumentationNormalizer(
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(OperationMethodResolverInterface::class)->reveal(),
            $this->prophesize(OperationPathResolverInterface::class)->reveal(),
            null,
            new \ArrayObject()
        );
    }

    public function testSupports()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver
        );

        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $this->assertTrue($normalizer->supportsNormalization($documentation, 'json'));
        $this->assertFalse($normalizer->supportsNormalization($documentation));
        $this->assertFalse($normalizer->supportsNormalization(new Dummy(), 'json'));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNoOperations()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), '', '', '0.0.0', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldNotBeCalled();

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            null,
            [],
            [],
            []
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldNotBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldNotBeCalled();

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

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
            'basePath' => '/',
            'info' => [
                'title' => '',
                'version' => '0.0.0',
            ],
            'paths' => new \ArrayObject([]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testWithCustomMethod()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), '', '', '0.0.0', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            null,
            [],
            ['get' => ['method' => 'FOO']],
            []
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('FOO');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

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
            'basePath' => '/',
            'info' => [
                'title' => '',
                'version' => '0.0.0',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'foo' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyCollection',
                    ]),
                ],
            ]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithNestedNormalizationGroups()
    {
        $title = 'Test API';
        $description = 'This is a test API.';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '1.2.3';
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), $title, $description, $version, $formats);
        $groups = ['dummy', 'foo', 'bar'];
        $ref = 'Dummy-'.implode('_', $groups);
        $relatedDummyRef = 'RelatedDummy-'.implode('_', $groups);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => $groups])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['name', 'relatedDummy']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class, ['serializer_groups' => $groups])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => ['method' => 'PUT', 'normalization_context' => [AbstractNormalizer::GROUPS => $groups]],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST'],
            ],
            []
        );

        $relatedDummyMetadata = new ResourceMetadata(
            'RelatedDummy',
            'This is a related dummy.',
            'http://schema.example.com/RelatedDummy',
            [
                'get' => ['method' => 'GET'],
            ],
            [],
            []
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedDummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, true, RelatedDummy::class), 'This is a related dummy \o/.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

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
            'basePath' => '/',
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
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'integer',
                                'description' => 'The collection page number',
                            ],
                        ],
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
                            'type' => 'string',
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
                                'type' => 'string',
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
                                'schema' => ['$ref' => '#/definitions/'.$ref],
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
                $ref => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a dummy.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                    'properties' => [
                        'name' => new \ArrayObject([
                            'type' => 'string',
                            'description' => 'This is a name.',
                        ]),
                        'relatedDummy' => new \ArrayObject([
                            'description' => 'This is a related dummy \o/.',
                            '$ref' => '#/definitions/'.$relatedDummyRef,
                        ]),
                    ],
                ]),
                $relatedDummyRef => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a related dummy.',
                    'externalDocs' => ['url' => 'http://schema.example.com/RelatedDummy'],
                    'properties' => [
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

    private function normalizeWithFilters($filterLocator)
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), '', '', '0.0.0', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            null,
            [],
            ['get' => ['method' => 'GET', 'filters' => ['f1', 'f2', 'f3']]],
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

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            $filterLocator
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
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'integer',
                                'description' => 'The collection page number',
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

    public function testNormalizeWithSubResource()
    {
        $documentation = new Documentation(new ResourceNameCollection([Question::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Question::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['answer']));
        $propertyNameCollectionFactoryProphecy->create(Answer::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['content']));

        $questionMetadata = new ResourceMetadata('Question', 'This is a question.', 'http://schema.example.com/Question', ['get' => ['method' => 'GET']]);
        $answerMetadata = new ResourceMetadata('Answer', 'This is an answer.', 'http://schema.example.com/Answer', [], ['get' => ['method' => 'GET']]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Question::class)->shouldBeCalled()->willReturn($questionMetadata);
        $resourceMetadataFactoryProphecy->create(Answer::class)->shouldBeCalled()->willReturn($answerMetadata);

        $subresourceMetadata = new SubresourceMetadata(Answer::class, false);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Question::class, 'answer')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, Question::class, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, Answer::class)), 'This is a name.', true, true, true, true, false, false, null, null, [], $subresourceMetadata));

        $propertyMetadataFactoryProphecy->create(Answer::class, 'content')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, Question::class, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, Answer::class)), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Question::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Answer::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Question::class, 'get')->shouldBeCalled()->willReturn('GET');

        $routeCollection = new RouteCollection();
        $routeCollection->add('api_questions_answer_get_subresource', new Route('/api/questions/{id}/answer.{_format}'));
        $routeCollection->add('api_questions_get_item', new Route('/api/questions/{id}.{_format}'));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->shouldBeCalled()->willReturn($routeCollection);

        $operationPathResolver = new RouterOperationPathResolver($routerProphecy->reveal(), new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator())));

        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactory,
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null, null, null, false, '', '', '', '', [], [],
            $subresourceOperationFactory
        );

        $expected = [
            'swagger' => '2.0',
            'basePath' => '/',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/api/questions/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Question'],
                        'operationId' => 'getQuestionItem',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves a Question resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Question resource response',
                                'schema' => ['$ref' => '#/definitions/Question'],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/api/questions/{id}/answer' => new \ArrayObject([
                    'get' => new \ArrayObject([
                        'tags' => ['Answer', 'Question'],
                        'operationId' => 'api_questions_answer_get_subresource',
                        'produces' => ['application/ld+json'],
                        'summary' => 'Retrieves a Answer resource.',
                        'responses' => [
                            200 => [
                                'description' => 'Answer resource response',
                                'schema' => ['$ref' => '#/definitions/Answer'],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ],
                        ],
                    ]),
                ]),
            ]),
            'definitions' => new \ArrayObject([
                'Question' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is a question.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Question'],
                    'properties' => [
                        'answer' => new \ArrayObject([
                            'type' => 'array',
                            'description' => 'This is a name.',
                            'items' => ['$ref' => '#/definitions/Answer'],
                        ]),
                    ],
                ]),
                'Answer' => new \ArrayObject([
                    'type' => 'object',
                    'description' => 'This is an answer.',
                    'externalDocs' => ['url' => 'http://schema.example.com/Answer'],
                    'properties' => [
                        'content' => new \ArrayObject([
                            'type' => 'array',
                            'description' => 'This is a name.',
                            'items' => ['$ref' => '#/definitions/Answer'],
                        ]),
                    ],
                ]),
            ]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithPropertySwaggerContext()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', 'http://schema.example.com/Dummy', ['get' => ['method' => 'GET']]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, ['swagger_context' => ['type' => 'string', 'enum' => ['one', 'two'], 'example' => 'one']]));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

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
            'basePath' => '/app_dev.php/',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
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
                                'type' => 'string',
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
                            'enum' => ['one', 'two'],
                            'example' => 'one',
                        ]),
                    ],
                ]),
            ]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['base_url' => '/app_dev.php/']));
    }
}
