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

namespace ApiPlatform\Hydra\Tests\Serializer;

use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Hydra\Serializer\DocumentationNormalizer;
use ApiPlatform\Hydra\Tests\Fixtures\CustomConverter;
use ApiPlatform\JsonLd\ContextBuilder;
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
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type;

use const ApiPlatform\JsonLd\HYDRA_CONTEXT;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class DocumentationNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testNormalize(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('dummy', [
            (new ApiResource())->withShortName('dummy')->withDescription('dummy')->withTypes(['#dummy'])->withOperations(new Operations([
                'get' => (new Get())->withHydraContext(['hydra:foo' => 'bar', 'hydra:title' => 'foobar'])->withTypes(['#dummy'])->withShortName('dummy'),
                'put' => (new Put())->withShortName('dummy'),
                'get_collection' => (new GetCollection())->withShortName('dummy'),
                'post' => (new Post())->withShortName('dummy'),
            ])),
            (new ApiResource())->withShortName('relatedDummy')->withOperations(new Operations(['get' => (new Get())->withTypes(['#relatedDummy'])->withShortName('relatedDummy')])),
        ]));
        $resourceMetadataFactoryProphecy->create('relatedDummy')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('relatedDummy', [
            (new ApiResource())->withShortName('relatedDummy')->withOperations(new Operations(['get' => (new Get())->withShortName('relatedDummy')])),
        ]));

        $this->doTestNormalize($resourceMetadataFactoryProphecy->reveal());
    }

    private function doTestNormalize($resourceMetadataFactory = null): void
    {
        $title = 'Test Api';
        $desc = 'test ApiGerard';
        $version = '0.0.0';
        $documentation = new Documentation(new ResourceNameCollection(['dummy' => 'dummy']), $title, $desc, $version);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create('dummy', [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name', 'description', 'nameConverted', 'relatedDummy', 'iri']));

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
        $propertyMetadataFactoryProphecy->create('dummy', 'iri', Argument::type('array'))->shouldBeCalled()->willReturn((new ApiProperty())->withIris(['https://schema.org/Dummy']));

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
            $urlGenerator->reveal(),
            new CustomConverter()
        );

        $expected = [
            '@context' => [
                HYDRA_CONTEXT,
                [
                    '@vocab' => '/doc#',
                    'hydra' => 'http://www.w3.org/ns/hydra/core#',
                    'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                    'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                    'xmls' => 'http://www.w3.org/2001/XMLSchema#',
                    'owl' => 'http://www.w3.org/2002/07/owl#',
                    'schema' => 'https://schema.org/',
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
                    'hydra:title' => 'dummy',
                    'hydra:description' => 'dummy',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#dummy/name',
                                '@type' => 'rdf:Property',
                                'label' => 'name',
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
                                'label' => 'description',
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
                                'label' => 'name_converted',
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
                                'label' => 'relatedDummy',
                                'domain' => '#dummy',
                                'range' => '#relatedDummy',
                            ],
                            'hydra:title' => 'relatedDummy',
                            'hydra:required' => false,
                            'hydra:readable' => true,
                            'hydra:writeable' => true,
                            'hydra:description' => 'This is a name.',
                        ],
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => 'https://schema.org/Dummy',
                                '@type' => 'rdf:Property',
                                'label' => 'iri',
                                'domain' => '#dummy',
                            ],
                            'hydra:title' => 'iri',
                            'hydra:required' => null,
                            'hydra:readable' => null,
                            'hydra:writeable' => false,
                        ],
                    ],
                    'hydra:supportedOperation' => [
                        [
                            '@type' => ['hydra:Operation', 'schema:FindAction'],
                            'hydra:method' => 'GET',
                            'hydra:title' => 'foobar',
                            'returns' => 'dummy',
                            'hydra:foo' => 'bar',
                            'hydra:description' => 'Retrieves a dummy resource.',
                        ],
                        [
                            '@type' => ['hydra:Operation', 'schema:ReplaceAction'],
                            'expects' => 'dummy',
                            'hydra:method' => 'PUT',
                            'hydra:title' => 'putdummy',
                            'hydra:description' => 'Replaces the dummy resource.',
                            'returns' => 'dummy',
                        ],
                        [
                            '@type' => ['hydra:Operation', 'schema:FindAction'],
                            'hydra:method' => 'GET',
                            'hydra:title' => 'getrelatedDummy',
                            'hydra:description' => 'Retrieves a relatedDummy resource.',
                            'returns' => 'relatedDummy',
                        ],
                    ],
                ],
                [
                    '@id' => '#Entrypoint',
                    '@type' => 'hydra:Class',
                    'hydra:title' => 'Entrypoint',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#Entrypoint/dummy',
                                '@type' => 'hydra:Link',
                                'domain' => '#Entrypoint',
                                'range' => [
                                    ['@id' => 'hydra:Collection'],
                                    [
                                        'owl:equivalentClass' => [
                                            'owl:onProperty' => ['@id' => 'hydra:member'],
                                            'owl:allValuesFrom' => ['@id' => '#dummy'],
                                        ],
                                    ],
                                ],
                                'owl:maxCardinality' => 1,
                                'hydra:supportedOperation' => [
                                    [
                                        '@type' => ['hydra:Operation', 'schema:FindAction'],
                                        'hydra:method' => 'GET',
                                        'hydra:title' => 'getdummyCollection',
                                        'hydra:description' => 'Retrieves the collection of dummy resources.',
                                        'returns' => 'hydra:Collection',
                                    ],
                                    [
                                        '@type' => ['hydra:Operation', 'schema:CreateAction'],
                                        'expects' => 'dummy',
                                        'hydra:method' => 'POST',
                                        'hydra:title' => 'postdummy',
                                        'hydra:description' => 'Creates a dummy resource.',
                                        'returns' => 'dummy',
                                    ],
                                ],
                            ],
                            'hydra:title' => 'getdummyCollection',
                            'hydra:description' => 'The collection of dummy resources',
                            'hydra:readable' => true,
                            'hydra:writeable' => false,
                        ],
                    ],
                    'hydra:supportedOperation' => [
                        '@type' => 'hydra:Operation',
                        'hydra:method' => 'GET',
                        'hydra:title' => 'index',
                        'hydra:description' => 'The API Entrypoint.',
                        'hydra:returns' => 'Entrypoint',
                    ],
                ],
                [
                    '@id' => '#ConstraintViolationList',
                    '@type' => 'hydra:Class',
                    'hydra:title' => 'ConstraintViolationList',
                    'hydra:description' => 'A constraint violation List.',
                    'hydra:supportedProperty' => [
                        [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#ConstraintViolationList/propertyPath',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'propertyPath',
                                'domain' => '#ConstraintViolationList',
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
                                '@id' => '#ConstraintViolationList/message',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'message',
                                'domain' => '#ConstraintViolationList',
                                'range' => 'xmls:string',
                            ],
                            'hydra:title' => 'message',
                            'hydra:description' => 'The message associated with the violation',
                            'hydra:readable' => true,
                            'hydra:writeable' => false,
                        ],
                    ],
                ],
            ],
            'hydra:entrypoint' => '/',
        ];

        $this->assertEquals($expected, $documentationNormalizer->normalize($documentation));
        $this->assertTrue($documentationNormalizer->supportsNormalization($documentation, 'jsonld'));
        $this->assertFalse($documentationNormalizer->supportsNormalization($documentation, 'hal'));
        $this->assertEmpty($documentationNormalizer->getSupportedTypes('json'));
        $this->assertSame([Documentation::class => true], $documentationNormalizer->getSupportedTypes($documentationNormalizer::FORMAT));
    }

    public function testNormalizeInputOutputClass(): void
    {
        $title = 'Test Api';
        $desc = 'test ApiGerard';
        $version = '0.0.0';
        $documentation = new Documentation(new ResourceNameCollection(['dummy' => 'dummy']), $title, $desc, $version);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create('inputClass', Argument::type('array'))->shouldBeCalled()->willReturn(new PropertyNameCollection(['a', 'b']));
        $propertyNameCollectionFactoryProphecy->create('outputClass', Argument::type('array'))->shouldBeCalled()->willReturn(new PropertyNameCollection(['c', 'd']));
        $propertyNameCollectionFactoryProphecy->create('dummy', Argument::type('array'))->shouldBeCalled()->willReturn(new PropertyNameCollection([]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('dummy', [
            (new ApiResource())->withShortName('dummy')->withDescription('dummy')->withTypes(['#dummy'])->withOperations(new Operations([
                'get' => (new Get())->withTypes(['#dummy'])->withShortName('dummy')->withInput(['class' => 'inputClass'])->withOutput(['class' => 'outputClass']),
                'put' => (new Put())->withShortName('dummy')->withInput(['class' => null])->withOutput(['class' => 'outputClass']),
                'get_collection' => (new GetCollection())->withShortName('dummy')->withInput(['class' => 'inputClass'])->withOutput(['class' => 'outputClass']),
                'post' => (new Post())->withShortName('dummy')->withOutput(['class' => null])->withInput(['class' => 'inputClass']),
            ])),
        ]));

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
            $urlGenerator->reveal()
        );

        $expected = [
            '@id' => '#dummy',
            '@type' => 'hydra:Class',
            'hydra:title' => 'dummy',
            'hydra:supportedProperty' => [
                [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => '#dummy/a',
                        '@type' => 'rdf:Property',
                        'label' => 'a',
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
                        'label' => 'b',
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
                        'label' => 'c',
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
                        'label' => 'd',
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
                    'hydra:title' => 'getdummy',
                    'hydra:description' => 'Retrieves a dummy resource.',
                    'returns' => 'dummy',
                ],
                [
                    '@type' => [
                        'hydra:Operation',
                        'schema:ReplaceAction',
                    ],
                    'expects' => 'owl:Nothing',
                    'hydra:method' => 'PUT',
                    'hydra:title' => 'putdummy',
                    'hydra:description' => 'Replaces the dummy resource.',
                    'returns' => 'dummy',
                ],
            ],
            'hydra:description' => 'dummy',
        ];

        $doc = $documentationNormalizer->normalize($documentation);
        $this->assertEquals($expected, $doc['hydra:supportedClass'][0]);
    }

    public function testHasHydraContext(): void
    {
        $title = 'Test Api';
        $desc = 'test ApiGerard';
        $version = '0.0.0';
        $documentation = new Documentation(new ResourceNameCollection(['dummy' => 'dummy']), $title, $desc, $version);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create('dummy', Argument::type('array'))->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('dummy', [
            (new ApiResource())->withShortName('dummy')->withDescription('dummy')->withTypes(['#dummy'])->withOperations(new Operations([
                'get' => (new Get())->withTypes(['#dummy'])->withShortName('dummy')->withInput(['class' => 'inputClass'])->withOutput(['class' => 'outputClass']),
            ])),
        ]));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create('dummy', 'name', Argument::type('array'))->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('b')
                ->withReadable(true)
                ->withWritable(true)
                ->withJsonldContext([
                    'hydra:property' => [
                        '@type' => 'https://schema.org/Enumeration',
                    ],
                ])
        );

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
            $urlGenerator->reveal()
        );

        $this->assertEquals([
            '@id' => '#dummy/name',
            '@type' => 'https://schema.org/Enumeration',
            'label' => 'name',
            'domain' => '#dummy',
            'range' => 'xmls:string',
        ], $documentationNormalizer->normalize($documentation)['hydra:supportedClass'][0]['hydra:supportedProperty'][0]['hydra:property']);
    }

    public function testNormalizeWithoutPrefix(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('dummy', [
            (new ApiResource())->withShortName('dummy')->withDescription('dummy')->withTypes(['#dummy'])->withOperations(new Operations([
                'get' => (new Get())->withHydraContext(['foo' => 'bar', 'title' => 'foobar'])->withTypes(['#dummy'])->withShortName('dummy'),
                'put' => (new Put())->withShortName('dummy'),
                'get_collection' => (new GetCollection())->withShortName('dummy'),
                'post' => (new Post())->withShortName('dummy'),
            ])),
            (new ApiResource())->withShortName('relatedDummy')->withOperations(new Operations(['get' => (new Get())->withTypes(['#relatedDummy'])->withShortName('relatedDummy')])),
        ]));
        $resourceMetadataFactoryProphecy->create('relatedDummy')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('relatedDummy', [
            (new ApiResource())->withShortName('relatedDummy')->withOperations(new Operations(['get' => (new Get())->withShortName('relatedDummy')])),
        ]));

        $title = 'Test Api';
        $desc = 'test ApiGerard';
        $version = '0.0.0';
        $documentation = new Documentation(new ResourceNameCollection(['dummy' => 'dummy']), $title, $desc, $version);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create('dummy', [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name', 'description', 'nameConverted', 'relatedDummy', 'iri']));

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
        $propertyMetadataFactoryProphecy->create('dummy', 'iri', Argument::type('array'))->shouldBeCalled()->willReturn((new ApiProperty())->withIris(['https://schema.org/Dummy']));

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
            $urlGenerator->reveal(),
            new CustomConverter()
        );

        $expected = [
            '@context' => [
                HYDRA_CONTEXT,
                [
                    '@vocab' => '/doc#',
                    'hydra' => 'http://www.w3.org/ns/hydra/core#',
                    'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                    'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                    'xmls' => 'http://www.w3.org/2001/XMLSchema#',
                    'owl' => 'http://www.w3.org/2002/07/owl#',
                    'schema' => 'https://schema.org/',
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
                ],
            ],
            '@id' => '/doc',
            '@type' => 'ApiDocumentation',
            'title' => 'Test Api',
            'description' => 'test ApiGerard',
            'supportedClass' => [
                [
                    '@id' => '#dummy',
                    '@type' => 'Class',
                    'title' => 'dummy',
                    'description' => 'dummy',
                    'supportedProperty' => [
                        [
                            '@type' => 'SupportedProperty',
                            'property' => [
                                '@id' => '#dummy/name',
                                '@type' => 'rdf:Property',
                                'label' => 'name',
                                'domain' => '#dummy',
                                'range' => 'xmls:string',
                            ],
                            'title' => 'name',
                            'required' => false,
                            'readable' => true,
                            'writeable' => true,
                            'description' => 'name',
                        ],
                        [
                            '@type' => 'SupportedProperty',
                            'property' => [
                                '@id' => '#dummy/description',
                                '@type' => 'rdf:Property',
                                'label' => 'description',
                                'domain' => '#dummy',
                                'range' => '@id',
                            ],
                            'title' => 'description',
                            'required' => false,
                            'readable' => true,
                            'writeable' => true,
                            'description' => 'description',
                        ],
                        [
                            '@type' => 'SupportedProperty',
                            'property' => [
                                '@id' => '#dummy/name_converted',
                                '@type' => 'rdf:Property',
                                'label' => 'name_converted',
                                'domain' => '#dummy',
                                'range' => 'xmls:string',
                            ],
                            'title' => 'name_converted',
                            'required' => false,
                            'readable' => true,
                            'writeable' => true,
                            'description' => 'name converted',
                        ],
                        [
                            '@type' => 'SupportedProperty',
                            'property' => [
                                '@id' => '#dummy/relatedDummy',
                                '@type' => 'rdf:Property',
                                'label' => 'relatedDummy',
                                'domain' => '#dummy',
                                'range' => '#relatedDummy',
                            ],
                            'title' => 'relatedDummy',
                            'required' => false,
                            'readable' => true,
                            'writeable' => true,
                            'description' => 'This is a name.',
                        ],
                        [
                            '@type' => 'SupportedProperty',
                            'property' => [
                                '@id' => 'https://schema.org/Dummy',
                                '@type' => 'rdf:Property',
                                'label' => 'iri',
                                'domain' => '#dummy',
                            ],
                            'title' => 'iri',
                            'required' => null,
                            'readable' => null,
                            'writeable' => false,
                        ],
                    ],
                    'supportedOperation' => [
                        [
                            '@type' => ['Operation', 'schema:FindAction'],
                            'method' => 'GET',
                            'title' => 'foobar',
                            'returns' => 'dummy',
                            'foo' => 'bar',
                            'description' => 'Retrieves a dummy resource.',
                        ],
                        [
                            '@type' => ['Operation', 'schema:ReplaceAction'],
                            'expects' => 'dummy',
                            'method' => 'PUT',
                            'title' => 'putdummy',
                            'description' => 'Replaces the dummy resource.',
                            'returns' => 'dummy',
                        ],
                        [
                            '@type' => ['Operation', 'schema:FindAction'],
                            'method' => 'GET',
                            'title' => 'getrelatedDummy',
                            'description' => 'Retrieves a relatedDummy resource.',
                            'returns' => 'relatedDummy',
                        ],
                    ],
                ],
                [
                    '@id' => '#Entrypoint',
                    '@type' => 'Class',
                    'title' => 'Entrypoint',
                    'supportedProperty' => [
                        [
                            '@type' => 'SupportedProperty',
                            'property' => [
                                '@id' => '#Entrypoint/dummy',
                                '@type' => 'Link',
                                'domain' => '#Entrypoint',
                                'range' => [
                                    ['@id' => 'Collection'],
                                    [
                                        'owl:equivalentClass' => [
                                            'owl:onProperty' => ['@id' => 'member'],
                                            'owl:allValuesFrom' => ['@id' => '#dummy'],
                                        ],
                                    ],
                                ],
                                'owl:maxCardinality' => 1,
                                'supportedOperation' => [
                                    [
                                        '@type' => ['Operation', 'schema:FindAction'],
                                        'method' => 'GET',
                                        'title' => 'getdummyCollection',
                                        'description' => 'Retrieves the collection of dummy resources.',
                                        'returns' => 'Collection',
                                    ],
                                    [
                                        '@type' => ['Operation', 'schema:CreateAction'],
                                        'expects' => 'dummy',
                                        'method' => 'POST',
                                        'title' => 'postdummy',
                                        'description' => 'Creates a dummy resource.',
                                        'returns' => 'dummy',
                                    ],
                                ],
                            ],
                            'title' => 'getdummyCollection',
                            'description' => 'The collection of dummy resources',
                            'readable' => true,
                            'writeable' => false,
                        ],
                    ],
                    'supportedOperation' => [
                        '@type' => 'Operation',
                        'method' => 'GET',
                        'title' => 'index',
                        'description' => 'The API Entrypoint.',
                        'returns' => 'Entrypoint',
                    ],
                ],
                [
                    '@id' => '#ConstraintViolationList',
                    '@type' => 'Class',
                    'title' => 'ConstraintViolationList',
                    'description' => 'A constraint violation List.',
                    'supportedProperty' => [
                        [
                            '@type' => 'SupportedProperty',
                            'property' => [
                                '@id' => '#ConstraintViolationList/propertyPath',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'propertyPath',
                                'domain' => '#ConstraintViolationList',
                                'range' => 'xmls:string',
                            ],
                            'title' => 'propertyPath',
                            'description' => 'The property path of the violation',
                            'readable' => true,
                            'writeable' => false,
                        ],
                        [
                            '@type' => 'SupportedProperty',
                            'property' => [
                                '@id' => '#ConstraintViolationList/message',
                                '@type' => 'rdf:Property',
                                'rdfs:label' => 'message',
                                'domain' => '#ConstraintViolationList',
                                'range' => 'xmls:string',
                            ],
                            'title' => 'message',
                            'description' => 'The message associated with the violation',
                            'readable' => true,
                            'writeable' => false,
                        ],
                    ],
                ],
            ],
            'entrypoint' => '/',
        ];

        $this->assertEquals($expected, $documentationNormalizer->normalize($documentation, null, [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false]));
    }
}
