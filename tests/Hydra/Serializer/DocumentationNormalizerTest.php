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

namespace ApiPlatform\Tests\Hydra\Serializer;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Hydra\Serializer\DocumentationNormalizer;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @group legacy
 */
class DocumentationNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testNormalizeLegacyResourceMetadata(): void
    {
        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET', 'hydra_context' => ['hydra:foo' => 'bar', 'hydra:title' => 'foobar']], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn($dummyMetadata);
        $resourceMetadataFactoryProphecy->create('relatedDummy')->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummy'));

        $this->doTestNormalize(null, $resourceMetadataFactoryProphecy->reveal());
    }

    public function testNormalize(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('dummy', [
            (new ApiResource())->withShortName('dummy')->withDescription('dummy')->withTypes(['#dummy'])->withOperations(new Operations([
                'get' => (new Get())->withHydraContext(['hydra:foo' => 'bar', 'hydra:title' => 'foobar'])->withShortName('dummy')->withTypes(['#dummy']),
                'put' => (new Put())->withShortName('dummy'),
                'get_collection' => (new GetCollection())->withShortName('dummy'),
                'post' => (new Post())->withShortName('dummy'),
            ])),
            (new ApiResource())->withShortName('relatedDummy')->withOperations(new Operations(['get' => (new Get())->withShortName('relatedDummy')->withTypes(['#relatedDummy'])])),
        ]));
        $resourceMetadataFactoryProphecy->create('relatedDummy')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('relatedDummy', [
            (new ApiResource())->withShortName('relatedDummy')->withOperations(new Operations(['get' => (new Get())->withShortName('relatedDummy')])),
        ]));

        $this->doTestNormalize(null, $resourceMetadataFactoryProphecy->reveal());
    }

    public function testLegacyNormalize(): void
    {
        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod('dummy', 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod('dummy', 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod('dummy', 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod('dummy', 'post')->shouldBeCalled()->willReturn('POST');
        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET', 'hydra_context' => ['hydra:foo' => 'bar', 'hydra:title' => 'foobar']], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn($dummyMetadata);
        $resourceMetadataFactoryProphecy->create('relatedDummy')->shouldBeCalled()->willReturn(new ResourceMetadata('relatedDummy'));

        $this->doTestNormalize($operationMethodResolverProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
    }

    private function doTestNormalize(OperationMethodResolverInterface $operationMethodResolver = null, $resourceMetadataFactory = null): void
    {
        $title = 'Test Api';
        $desc = 'test ApiGerard';
        $version = '0.0.0';
        $documentation = new Documentation(new ResourceNameCollection(['dummy' => 'dummy']), $title, $desc, $version);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create('dummy', [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name', 'description', 'nameConverted', 'relatedDummy']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create('dummy', 'name', Argument::type('array'))->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('name')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)
        );
        $propertyMetadataFactoryProphecy->create('dummy', 'description', Argument::type('array'))->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('description')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withJsonldContext(['@type' => '@id'])
        );
        $propertyMetadataFactoryProphecy->create('dummy', 'nameConverted', Argument::type('array'))->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('name converted')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)
        );
        $propertyMetadataFactoryProphecy->create('dummy', 'relatedDummy', Argument::type('array'))->shouldBeCalled()->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummy', true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, 'relatedDummy'))])->withDescription('This is a name.')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));

        $subresourceOperationFactoryProphecy = null;
        if ($resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $subresourceMetadata = new SubresourceMetadata('relatedDummy', false);
            $propertyMetadataFactoryProphecy->create('dummy', 'relatedDummy')->shouldBeCalled()->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummy', true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, 'relatedDummy'))])->withDescription('This is a name.')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withSubresource($subresourceMetadata));
            $subresourceOperationFactoryProphecy = $this->prophesize(SubresourceOperationFactoryInterface::class);
            $subresourceOperationFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn([
                'api_dummies_subresource_get_related_dummy' => [
                    'property' => 'relatedDummy',
                    'collection' => false,
                    'resource_class' => 'relatedDummy',
                    'shortNames' => ['relatedDummy'],
                    'identifiers' => [
                        'id' => ['dummy', 'id', true],
                    ],
                    'route_name' => 'api_dummies_subresource_get_related_dummy',
                    'path' => '/dummies/{id}/related_dummy.{_format}',
                ],
            ]);
        }

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true);

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('api_entrypoint')->willReturn('/')->shouldBeCalledTimes(1);
        $urlGenerator->generate('api_doc', ['_format' => 'jsonld'])->willReturn('/doc')->shouldBeCalledTimes(1);

        $urlGenerator->generate('api_doc', ['_format' => 'jsonld'], 0)->willReturn('/doc')->shouldBeCalledTimes(1);

        $documentationNormalizer = new DocumentationNormalizer(
            $resourceMetadataFactory,
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolver,
            $urlGenerator->reveal(),
            $subresourceOperationFactoryProphecy ? $subresourceOperationFactoryProphecy->reveal() : null,
            new CustomConverter()
        );

        $expected = [
            '@context' => [
                '@vocab' => '/doc#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                'xmls' => 'http://www.w3.org/2001/XMLSchema#',
                'owl' => 'http://www.w3.org/2002/07/owl#',
                'schema' => 'http://schema.org/',
                'domain' => [
                    '@id' => 'rdfs:domain',
                    '@type' => '@id',
                ],
                'range' => [
                    '@id' => 'rdfs:range',
                    '@type' => '@id',
                ],
                'subClassOf' => [
                    '@id' => 'rdfs:subClassOf',
                    '@type' => '@id',
                ],
                'expects' => [
                    '@id' => 'hydra:expects',
                    '@type' => '@id',
                ],
                'returns' => [
                    '@id' => 'hydra:returns',
                    '@type' => '@id',
                ],
            ],
            '@id' => '/doc',
            '@type' => 'hydra:ApiDocumentation',
            'hydra:title' => 'Test Api',
            'hydra:description' => 'test ApiGerard',
            'hydra:supportedClass' => [
                [
                    '@id' => '#dummy',
                    '@type' => 'hydra:Class',
                    'rdfs:label' => 'dummy',
                    'hydra:title' => 'dummy',
                    'hydra:description' => 'dummy',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#dummy/name',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'name',
                                'domain' => '#dummy',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'name',
                            'hydra:required' => false,
                            'hydra:readable' => true,
                            'hydra:writeable' => true,
                            'hydra:description' => 'name',
                        ],
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#dummy/description',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'description',
                                'domain' => '#dummy',
                                'range' => '@id',
                            ],
                            'hydra:title' => 'description',
                            'hydra:required' => false,
                            'hydra:readable' => true,
                            'hydra:writeable' => true,
                            'hydra:description' => 'description',
                        ],
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#dummy/name_converted',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'name_converted',
                                'domain' => '#dummy',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'name_converted',
                            'hydra:required' => false,
                            'hydra:readable' => true,
                            'hydra:writeable' => true,
                            'hydra:description' => 'name converted',
                        ],
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#dummy/relatedDummy',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'relatedDummy',
                                'domain' => '#dummy',
                                'range' => '#relatedDummy',
                            ],
                            'hydra:title' => 'relatedDummy',
                            'hydra:required' => false,
                            'hydra:readable' => true,
                            'hydra:writeable' => true,
                            'hydra:description' => 'This is a name.',
                        ],
                    ],
                    'hydra:supportedOperation' => [
                        [
                            '@type' => ['hydra:Operation', 'schema:FindAction'],
                            'hydra:method' => 'GET',
                            'hydra:title' => 'foobar',
                            'rdfs:label' => 'foobar',
                            'returns' => '#dummy',
                            'hydra:foo' => 'bar',
                        ],
                        [
                            '@type' => ['hydra:Operation', 'schema:ReplaceAction'],
                            'expects' => '#dummy',
                            'hydra:method' => 'PUT',
                            'hydra:title' => 'Replaces the dummy resource.',
                            'rdfs:label' => 'Replaces the dummy resource.',
                            'returns' => '#dummy',
                        ],
                        [
                            '@type' => ['hydra:Operation', 'schema:FindAction'],
                            'hydra:method' => 'GET',
                            'hydra:title' => 'Retrieves a relatedDummy resource.',
                            'rdfs:label' => 'Retrieves a relatedDummy resource.',
                            'returns' => '#relatedDummy',
                        ],
                    ],
                ],
                [
                    '@id' => '#Entrypoint',
                    '@type' => 'hydra:Class',
                    'hydra:title' => 'The API entrypoint',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#Entrypoint/dummy',
                                '@type' => 'hydra:Link',
                                'rdfs:label' => 'The collection of dummy resources',
                                'domain' => '#Entrypoint',
                                'rdfs:range' => [
                                    ['@id' => 'hydra:Collection'],
                                    [
                                        'owl:equivalentClass' => [
                                            'owl:onProperty' => ['@id' => 'hydra:member'],
                                            'owl:allValuesFrom' => ['@id' => '#dummy'],
                                        ],
                                    ],
                                ],
                                'hydra:supportedOperation' => [
                                    [
                                        '@type' => ['hydra:Operation', 'schema:FindAction'],
                                        'hydra:method' => 'GET',
                                        'hydra:title' => 'Retrieves the collection of dummy resources.',
                                        'rdfs:label' => 'Retrieves the collection of dummy resources.',
                                        'returns' => 'hydra:Collection',
                                    ],
                                    [
                                        '@type' => ['hydra:Operation', 'schema:CreateAction'],
                                        'expects' => '#dummy',
                                        'hydra:method' => 'POST',
                                        'hydra:title' => 'Creates a dummy resource.',
                                        'rdfs:label' => 'Creates a dummy resource.',
                                        'returns' => '#dummy',
                                    ],
                                ],
                            ],
                            'hydra:title' => 'The collection of dummy resources',
                            'hydra:readable' => true,
                            'hydra:writeable' => false,
                        ],
                    ],
                    'hydra:supportedOperation' => [
                        '@type' => 'hydra:Operation',
                        'hydra:method' => 'GET',
                        'rdfs:label' => 'The API entrypoint.',
                        'returns' => '#EntryPoint',
                    ],
                ],
                [
                    '@id' => '#ConstraintViolation',
                    '@type' => 'hydra:Class',
                    'hydra:title' => 'A constraint violation',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#ConstraintViolation/propertyPath',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'propertyPath',
                                'domain' => '#ConstraintViolation',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'propertyPath',
                            'hydra:description' => 'The property path of the violation',
                            'hydra:readable' => true,
                            'hydra:writeable' => false,
                        ],
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#ConstraintViolation/message',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'message',
                                'domain' => '#ConstraintViolation',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'message',
                            'hydra:description' => 'The message associated with the violation',
                            'hydra:readable' => true,
                            'hydra:writeable' => false,
                        ],
                    ],
                ],
                [
                    '@id' => '#ConstraintViolationList',
                    '@type' => 'hydra:Class',
                    'subClassOf' => 'hydra:Error',
                    'hydra:title' => 'A constraint violation list',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#ConstraintViolationList/violations',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'violations',
                                'domain' => '#ConstraintViolationList',
                                'range' => '#ConstraintViolation',
                            ],
                            'hydra:title' => 'violations',
                            'hydra:description' => 'The violations',
                            'hydra:readable' => true,
                            'hydra:writeable' => false,
                        ],
                    ],
                ],
            ],
            'hydra:entrypoint' => '/',
        ];

        if ($resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $expected['hydra:supportedClass'][1]['hydra:supportedProperty'][0]['hydra:property']['hydra:supportedOperation'][] = [
                '@type' => ['hydra:Operation', 'schema:FindAction'],
                'hydra:method' => 'GET',
                'hydra:title' => 'Retrieves a relatedDummy resource.',
                'rdfs:label' => 'Retrieves a relatedDummy resource.',
                'returns' => '#relatedDummy',
            ];
        }
        $this->assertEquals($expected, $documentationNormalizer->normalize($documentation));
        $this->assertTrue($documentationNormalizer->supportsNormalization($documentation, 'jsonld'));
        $this->assertFalse($documentationNormalizer->supportsNormalization($documentation, 'hal'));
        $this->assertTrue($documentationNormalizer->hasCacheableSupportsMethod());
    }

    public function testNormalizeInputOutputClass()
    {
        $title = 'Test Api';
        $desc = 'test ApiGerard';
        $version = '0.0.0';
        $documentation = new Documentation(new ResourceNameCollection(['dummy' => 'dummy']), $title, $desc, $version);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create('inputClass', [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['a', 'b']));
        $propertyNameCollectionFactoryProphecy->create('outputClass', [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['c', 'd']));

        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => ['method' => 'PUT', 'input' => ['class' => null]],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST', 'output' => ['class' => null]],
            ],
            [
                'input' => ['class' => 'inputClass'],
                'output' => ['class' => 'outputClass'],
            ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create('inputClass', 'a', Argument::type('array'))->shouldBeCalled()->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('a')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $propertyMetadataFactoryProphecy->create('inputClass', 'b', Argument::type('array'))->shouldBeCalled()->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('b')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $propertyMetadataFactoryProphecy->create('outputClass', 'c', Argument::type('array'))->shouldBeCalled()->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('c')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $propertyMetadataFactoryProphecy->create('outputClass', 'd', Argument::type('array'))->shouldBeCalled()->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('d')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true);

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('api_entrypoint')->willReturn('/')->shouldBeCalledTimes(1);
        $urlGenerator->generate('api_doc', ['_format' => 'jsonld'])->willReturn('/doc')->shouldBeCalledTimes(1);
        $urlGenerator->generate('api_doc', ['_format' => 'jsonld'], 0)->willReturn('/doc')->shouldBeCalledTimes(1);

        $documentationNormalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            $urlGenerator->reveal()
        );

        $expected = [
            '@context' => [
                '@vocab' => '/doc#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                'xmls' => 'http://www.w3.org/2001/XMLSchema#',
                'owl' => 'http://www.w3.org/2002/07/owl#',
                'schema' => 'http://schema.org/',
                'domain' => [
                    '@id' => 'rdfs:domain',
                    '@type' => '@id',
                ],
                'range' => [
                    '@id' => 'rdfs:range',
                    '@type' => '@id',
                ],
                'subClassOf' => [
                    '@id' => 'rdfs:subClassOf',
                    '@type' => '@id',
                ],
                'expects' => [
                    '@id' => 'hydra:expects',
                    '@type' => '@id',
                ],
                'returns' => [
                    '@id' => 'hydra:returns',
                    '@type' => '@id',
                ],
            ],
            '@id' => '/doc',
            '@type' => 'hydra:ApiDocumentation',
            'hydra:title' => 'Test Api',
            'hydra:description' => 'test ApiGerard',
            'hydra:entrypoint' => '/',
            'hydra:supportedClass' => [
                [
                    '@id' => '#dummy',
                    '@type' => 'hydra:Class',
                    'rdfs:label' => 'dummy',
                    'hydra:title' => 'dummy',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#dummy/a',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'a',
                                'domain' => '#dummy',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'a',
                            'hydra:required' => false,
                            'hydra:readable' => true,
                            'hydra:writeable' => true,
                            'hydra:description' => 'a',
                        ],
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#dummy/b',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'b',
                                'domain' => '#dummy',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'b',
                            'hydra:required' => false,
                            'hydra:readable' => true,
                            'hydra:writeable' => true,
                            'hydra:description' => 'b',
                        ],
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#dummy/c',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'c',
                                'domain' => '#dummy',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'c',
                            'hydra:required' => false,
                            'hydra:readable' => true,
                            'hydra:writeable' => true,
                            'hydra:description' => 'c',
                        ],
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#dummy/d',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'd',
                                'domain' => '#dummy',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'd',
                            'hydra:required' => false,
                            'hydra:readable' => true,
                            'hydra:writeable' => true,
                            'hydra:description' => 'd',
                        ],
                    ],
                    'hydra:supportedOperation' => [
                        [
                            '@type' => [
                                'hydra:Operation',
                                'schema:FindAction',
                            ],
                            'hydra:method' => 'GET',
                            'hydra:title' => 'Retrieves a dummy resource.',
                            'rdfs:label' => 'Retrieves a dummy resource.',
                            'returns' => '#dummy',
                        ],
                        [
                            '@type' => [
                                'hydra:Operation',
                                'schema:ReplaceAction',
                            ],
                            'expects' => 'owl:Nothing',
                            'hydra:method' => 'PUT',
                            'hydra:title' => 'Replaces the dummy resource.',
                            'rdfs:label' => 'Replaces the dummy resource.',
                            'returns' => '#dummy',
                        ],
                    ],
                    'hydra:description' => 'dummy',
                ],
                [
                    '@id' => '#Entrypoint',
                    '@type' => 'hydra:Class',
                    'hydra:title' => 'The API entrypoint',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#Entrypoint/dummy',
                                '@type' => 'hydra:Link',
                                'domain' => '#Entrypoint',
                                'rdfs:label' => 'The collection of dummy resources',
                                'rdfs:range' => [
                                    [
                                        '@id' => 'hydra:Collection',
                                    ],
                                    [
                                        'owl:equivalentClass' => [
                                            'owl:onProperty' => [
                                                '@id' => 'hydra:member',
                                            ],
                                            'owl:allValuesFrom' => [
                                                '@id' => '#dummy',
                                            ],
                                        ],
                                    ],
                                ],
                                'hydra:supportedOperation' => [
                                    [
                                        '@type' => [
                                            'hydra:Operation',
                                            'schema:FindAction',
                                        ],
                                        'hydra:method' => 'GET',
                                        'hydra:title' => 'Retrieves the collection of dummy resources.',
                                        'rdfs:label' => 'Retrieves the collection of dummy resources.',
                                        'returns' => 'hydra:Collection',
                                    ],
                                    [
                                        '@type' => [
                                            'hydra:Operation',
                                            'schema:CreateAction',
                                        ],
                                        'expects' => '#dummy',
                                        'hydra:method' => 'POST',
                                        'hydra:title' => 'Creates a dummy resource.',
                                        'rdfs:label' => 'Creates a dummy resource.',
                                        'returns' => 'owl:Nothing',
                                    ],
                                ],
                            ],
                            'hydra:title' => 'The collection of dummy resources',
                            'hydra:readable' => true,
                            'hydra:writeable' => false,
                        ],
                    ],
                    'hydra:supportedOperation' => [
                        '@type' => 'hydra:Operation',
                        'hydra:method' => 'GET',
                        'rdfs:label' => 'The API entrypoint.',
                        'returns' => '#EntryPoint',
                    ],
                ],
                2 => [
                    '@id' => '#ConstraintViolation',
                    '@type' => 'hydra:Class',
                    'hydra:title' => 'A constraint violation',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#ConstraintViolation/propertyPath',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'propertyPath',
                                'domain' => '#ConstraintViolation',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'propertyPath',
                            'hydra:description' => 'The property path of the violation',
                            'hydra:readable' => true,
                            'hydra:writeable' => false,
                        ],
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#ConstraintViolation/message',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'message',
                                'domain' => '#ConstraintViolation',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'message',
                            'hydra:description' => 'The message associated with the violation',
                            'hydra:readable' => true,
                            'hydra:writeable' => false,
                        ],
                    ],
                ],
                [
                    '@id' => '#ConstraintViolationList',
                    '@type' => 'hydra:Class',
                    'subClassOf' => 'hydra:Error',
                    'hydra:title' => 'A constraint violation list',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#ConstraintViolationList/violations',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'violations',
                                'domain' => '#ConstraintViolationList',
                                'range' => '#ConstraintViolation',
                            ],
                            'hydra:title' => 'violations',
                            'hydra:description' => 'The violations',
                            'hydra:readable' => true,
                            'hydra:writeable' => false,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $documentationNormalizer->normalize($documentation));
    }
}
