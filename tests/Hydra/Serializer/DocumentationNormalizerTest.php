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

namespace ApiPlatform\Core\Tests\Hydra\Serializer;

use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Hydra\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class DocumentationNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalize()
    {
        $title = 'Test Api';
        $desc = 'test ApiGerard';
        $version = '0.0.0';
        $documentation = new Documentation(new ResourceNameCollection(['dummy' => 'dummy']), $title, $desc, $version, []);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create('dummy', [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name', 'description']));

        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create('dummy', 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'name', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create('dummy', 'description')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'description', true, true, true, true, false, false, null, null, ['jsonld_context' => ['@type' => '@id']]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod('dummy', 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod('dummy', 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod('dummy', 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod('dummy', 'post')->shouldBeCalled()->willReturn('POST');

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('api_entrypoint')->willReturn('/')->shouldBeCalled(1);
        $urlGenerator->generate('api_doc', ['_format' => 'jsonld'])->willReturn('/doc')->shouldBeCalled(1);

        $urlGenerator->generate('api_doc', ['_format' => 'jsonld'], 0)->willReturn('/doc')->shouldBeCalled(1);

        $apiDocumentationBuilder = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $urlGenerator->reveal());

        $expected = [
            '@context' => [
                '@vocab' => '/doc#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                'xmls' => 'http://www.w3.org/2001/XMLSchema#',
                'owl' => 'http://www.w3.org/2002/07/owl#',
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
            'hydra:title' => 'Test Api',
            'hydra:description' => 'test ApiGerard',
            'hydra:supportedClass' => [
                0 => [
                    '@id' => '#dummy',
                    '@type' => 'hydra:Class',
                    'rdfs:label' => 'dummy',
                    'hydra:title' => 'dummy',
                    'hydra:description' => 'dummy',
                    'hydra:supportedProperty' => [
                        0 => [
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
                            'hydra:writable' => true,
                            'hydra:description' => 'name',
                        ],
                        1 => [
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
                            'hydra:writable' => true,
                            'hydra:description' => 'description',
                        ],
                    ],
                    'hydra:supportedOperation' => [
                        0 => [
                            '@type' => 'hydra:Operation',
                            'hydra:method' => 'GET',
                            'hydra:title' => 'Retrieves dummy resource.',
                            'rdfs:label' => 'Retrieves dummy resource.',
                            'returns' => '#dummy',
                        ],
                        1 => [
                            '@type' => 'hydra:ReplaceResourceOperation',
                            'expects' => '#dummy',
                            'hydra:method' => 'PUT',
                            'hydra:title' => 'Replaces the dummy resource.',
                            'rdfs:label' => 'Replaces the dummy resource.',
                            'returns' => '#dummy',
                        ],
                    ],
                ],
                1 => [
                    '@id' => '#Entrypoint',
                    '@type' => 'hydra:Class',
                    'hydra:title' => 'The API entrypoint',
                    'hydra:supportedProperty' => [
                        0 => [
                            '@type' => 'hydra:SupportedProperty',
                            'hydra:property' => [
                                '@id' => '#Entrypoint/dummy',
                                '@type' => 'hydra:Link',
                                'rdfs:label' => 'The collection of dummy resources',
                                'domain' => '#Entrypoint',
                                'range' => 'hydra:PagedCollection',
                                'hydra:supportedOperation' => [
                                    0 => [
                                        '@type' => 'hydra:Operation',
                                        'hydra:method' => 'GET',
                                        'hydra:title' => 'Retrieves the collection of dummy resources.',
                                        'rdfs:label' => 'Retrieves the collection of dummy resources.',
                                        'returns' => 'hydra:PagedCollection',
                                    ],
                                    1 => [
                                        '@type' => 'hydra:CreateResourceOperation',
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
                            'hydra:writable' => false,
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
                        0 => [
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
                            'hydra:writable' => false,
                        ],
                        1 => [
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
                            'hydra:writable' => false,
                        ],
                    ],
                ],
                3 => [
                    '@id' => '#ConstraintViolationList',
                    '@type' => 'hydra:Class',
                    'subClassOf' => 'hydra:Error',
                    'hydra:title' => 'A constraint violation list',
                    'hydra:supportedProperty' => [
                        0 => [
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
                            'hydra:writable' => false,
                        ],
                    ],
                ],
            ],
            'hydra:entrypoint' => '/',
        ];
        $this->assertEquals($expected, $apiDocumentationBuilder->normalize($documentation));
    }
}
