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

namespace ApiPlatform\Core\Tests\Bridge\NelmioApiDoc\Extractor\AnnotationsProvider;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Bridge\NelmioApiDoc\Extractor\AnnotationsProvider\ApiPlatformProvider;
use ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser;
use ApiPlatform\Core\Bridge\Symfony\Routing\OperationMethodResolverInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\AnnotationsProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 *
 * @group legacy
 */
class ApiPlatformProviderTest extends TestCase
{
    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Extractor\AnnotationsProvider\ApiPlatformProvider class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testConstruct()
    {
        $apiPlatformProvider = new ApiPlatformProvider(
            $this->prophesize(ResourceNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(NormalizerInterface::class)->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal(),
            $this->prophesize(OperationMethodResolverInterface::class)->reveal()
        );

        $this->assertInstanceOf(AnnotationsProviderInterface::class, $apiPlatformProvider);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Extractor\AnnotationsProvider\ApiPlatformProvider class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testGetAnnotations()
    {
        $dummySearchFilterProphecy = $this->prophesize(FilterInterface::class);
        $dummySearchFilterProphecy->getDescription(Dummy::class)->willReturn([
            'name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => 'false',
                'strategy' => 'partial',
            ],
        ])->shouldBeCalled();

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('my_dummy.search')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('my_dummy.search')->willReturn($dummySearchFilterProphecy->reveal())->shouldBeCalled();

        $this->extractAnnotations($filterLocatorProphecy->reveal());
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Extractor\AnnotationsProvider\ApiPlatformProvider class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     * @expectedDeprecation The ApiPlatform\Core\Api\FilterCollection class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of Psr\Container\ContainerInterface instead.
     */
    public function testGetAnnotationsWithDeprecatedFilterCollection()
    {
        $dummySearchFilterProphecy = $this->prophesize(FilterInterface::class);
        $dummySearchFilterProphecy->getDescription(Dummy::class)->willReturn([
            'name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => 'false',
                'strategy' => 'partial',
            ],
        ])->shouldBeCalled();

        $this->extractAnnotations(new FilterCollection(['my_dummy.search' => $dummySearchFilterProphecy->reveal()]));
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Extractor\AnnotationsProvider\ApiPlatformProvider class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testConstructWithInvalidFilterLocator()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "$filterLocator" argument is expected to be an implementation of the "Psr\\Container\\ContainerInterface" interface.');

        new ApiPlatformProvider(
            $this->prophesize(ResourceNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(NormalizerInterface::class)->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            new \ArrayObject(),
            $this->prophesize(OperationMethodResolverInterface::class)->reveal()
        );
    }

    private function extractAnnotations($filterLocator)
    {
        $resourceNameCollection = new ResourceNameCollection([Dummy::class, RelatedDummy::class]);

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn($resourceNameCollection)->shouldBeCalled();

        $apiDocumentationBuilderProphecy = $this->prophesize(NormalizerInterface::class);
        $apiDocumentationBuilderProphecy->normalize(new Documentation($resourceNameCollection))->willReturn($this->getHydraDoc())->shouldBeCalled();

        $dummyResourceMetadata = (new ResourceMetadata())
            ->withShortName('Dummy')
            ->withItemOperations([
                'get' => [
                    'method' => 'GET',
                ],
                'put' => [
                    'method' => 'PUT',
                ],
                'delete' => [
                    'method' => 'DELETE',
                ],
            ])
            ->withCollectionOperations([
                'get' => [
                    'filters' => [
                        'my_dummy.search',
                    ],
                    'method' => 'GET',
                ],
                'post' => [
                    'method' => 'POST',
                ],
            ]);

        $relatedDummyResourceMetadata = (new ResourceMetadata())
            ->withShortName('RelatedDummy');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($dummyResourceMetadata)->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedDummyResourceMetadata)->shouldBeCalled();

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->willReturn('GET')->shouldBeCalled();
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->willReturn('POST')->shouldBeCalled();
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->willReturn('GET')->shouldBeCalled();
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->willReturn('PUT')->shouldBeCalled();
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'delete')->willReturn('DELETE')->shouldBeCalled();
        $operationMethodResolverProphecy->getCollectionOperationRoute(Dummy::class, 'get')->willReturn((new Route('/dummies'))->setMethods(['GET']))->shouldBeCalled();
        $operationMethodResolverProphecy->getCollectionOperationRoute(Dummy::class, 'post')->willReturn((new Route('/dummies'))->setMethods(['POST']))->shouldBeCalled();
        $operationMethodResolverProphecy->getItemOperationRoute(Dummy::class, 'get')->willReturn((new Route('/dummies/{id}'))->setMethods(['GET']))->shouldBeCalled();
        $operationMethodResolverProphecy->getItemOperationRoute(Dummy::class, 'put')->willReturn((new Route('/dummies/{id}'))->setMethods(['PUT']))->shouldBeCalled();
        $operationMethodResolverProphecy->getItemOperationRoute(Dummy::class, 'delete')->willReturn((new Route('/dummies/{id}'))->setMethods(['DELETE']))->shouldBeCalled();

        $apiPlatformProvider = new ApiPlatformProvider(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $apiDocumentationBuilderProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $filterLocator,
            $operationMethodResolverProphecy->reveal()
        );

        $annotations = $apiPlatformProvider->getAnnotations();

        $this->assertCount(5, $annotations);

        $expectedResults = [
            [
                'resource' => '/dummies',
                'description' => 'Retrieves the collection of Dummy resources.',
                'resource_description' => 'Dummy',
                'section' => 'Dummy',
                'input' => null,
                'output' => sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'get'),
                'filters' => [
                    'name' => [
                        'property' => 'name',
                        'type' => 'string',
                        'required' => 'false',
                        'strategy' => 'partial',
                    ],
                ],
                'path' => '/dummies',
                'methods' => ['GET'],
            ],
            [
                'resource' => '/dummies',
                'description' => 'Creates a Dummy resource.',
                'resource_description' => 'Dummy',
                'section' => 'Dummy',
                'input' => sprintf('%s:%s:%s', ApiPlatformParser::IN_PREFIX, Dummy::class, 'post'),
                'output' => sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'post'),
                'filters' => [],
                'path' => '/dummies',
                'methods' => ['POST'],
            ],
            [
                'resource' => '/dummies/{id}',
                'description' => 'Retrieves Dummy resource.',
                'resource_description' => 'Dummy',
                'section' => 'Dummy',
                'input' => null,
                'output' => sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'get'),
                'filters' => [],
                'path' => '/dummies/{id}',
                'methods' => ['GET'],
            ],
            [
                'resource' => '/dummies/{id}',
                'description' => 'Replaces the Dummy resource.',
                'resource_description' => 'Dummy',
                'section' => 'Dummy',
                'input' => sprintf('%s:%s:%s', ApiPlatformParser::IN_PREFIX, Dummy::class, 'put'),
                'output' => sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'put'),
                'filters' => [],
                'path' => '/dummies/{id}',
                'methods' => ['PUT'],
            ],
            [
                'resource' => '/dummies/{id}',
                'description' => 'Deletes the Dummy resource.',
                'resource_description' => 'Dummy',
                'section' => 'Dummy',
                'input' => null,
                'output' => null,
                'filters' => [],
                'path' => '/dummies/{id}',
                'methods' => ['DELETE'],
            ],
        ];

        foreach ($expectedResults as $i => $expected) {
            /** @var ApiDoc $apiDoc */
            $apiDoc = $annotations[$i];

            $this->assertInstanceOf(ApiDoc::class, $apiDoc);
            $this->assertEquals($expected['resource'], $apiDoc->getResource());
            $this->assertEquals($expected['description'], $apiDoc->getDescription());
            $this->assertEquals($expected['resource_description'], $apiDoc->getResourceDescription());
            $this->assertEquals($expected['section'], $apiDoc->getSection());
            $this->assertEquals($expected['input'], $apiDoc->getInput());
            $this->assertEquals($expected['output'], $apiDoc->getOutput());
            $this->assertEquals($expected['filters'], $apiDoc->getFilters());
            $this->assertInstanceOf(Route::class, $apiDoc->getRoute());
            $this->assertEquals($expected['path'], $apiDoc->getRoute()->getPath());
            $this->assertEquals($expected['methods'], $apiDoc->getRoute()->getMethods());
        }
    }

    private function getHydraDoc()
    {
        $hydraDocJson = <<<'JSON'
            {
                "hydra:supportedClass": [
                    {
                        "@id": "#Dummy",
                        "hydra:title": "Dummy",
                        "hydra:supportedOperation": [
                            {
                                "hydra:method": "GET",
                                "hydra:title": "Retrieves Dummy resource.",
                                "returns": "#Dummy"
                            },
                            {
                                "expects": "#Dummy",
                                "hydra:method": "PUT",
                                "hydra:title": "Replaces the Dummy resource.",
                                "returns": "#Dummy"
                            },
                            {
                                "hydra:method": "DELETE",
                                "hydra:title": "Deletes the Dummy resource.",
                                "returns": "owl:Nothing"
                            }
                        ]
                    },
                    {
                        "@id": "#Entrypoint",
                        "hydra:supportedProperty": [
                            {
                                "hydra:property": {
                                    "@id": "#Entrypoint\/dummy",
                                    "hydra:supportedOperation": [
                                        {
                                            "hydra:method": "GET",
                                            "hydra:title": "Retrieves the collection of Dummy resources.",
                                            "returns": "hydra:PagedCollection"
                                        },
                                        {
                                            "expects": "#Dummy",
                                            "hydra:method": "POST",
                                            "hydra:title": "Creates a Dummy resource.",
                                            "returns": "#Dummy"
                                        }
                                    ]
                                }
                            }
                        ]
                    }
                ]
            }
JSON;

        return json_decode($hydraDocJson, true);
    }

    public function testGetAnnotationsWithEmptyHydraDoc()
    {
        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizerProphecy->normalize(new Documentation(new ResourceNameCollection([Dummy::class])))->willReturn([])->shouldBeCalled();

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $apiPlatformProvider = new ApiPlatformProvider(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $documentationNormalizerProphecy->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal(),
            $this->prophesize(OperationMethodResolverInterface::class)->reveal()
        );

        $this->assertEquals([], $apiPlatformProvider->getAnnotations());
    }

    public function testGetAnnotationsWithInvalidHydraSupportedClass()
    {
        $hydraDoc = ['hydra:supportedClass' => 'not an array'];

        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizerProphecy->normalize(new Documentation(new ResourceNameCollection([Dummy::class])))->willReturn($hydraDoc)->shouldBeCalled();

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $apiPlatformProvider = new ApiPlatformProvider(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $documentationNormalizerProphecy->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal(),
            $this->prophesize(OperationMethodResolverInterface::class)->reveal()
        );

        $this->assertEquals([], $apiPlatformProvider->getAnnotations());
    }

    public function testGetAnnotationsWithEmptyHydraSupportedClass()
    {
        $hydraDoc = ['hydra:supportedClass' => []];

        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizerProphecy->normalize(new Documentation(new ResourceNameCollection([Dummy::class])))->willReturn($hydraDoc)->shouldBeCalled();

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $apiPlatformProvider = new ApiPlatformProvider(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $documentationNormalizerProphecy->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal(),
            $this->prophesize(OperationMethodResolverInterface::class)->reveal()
        );

        $this->assertEquals([], $apiPlatformProvider->getAnnotations());
    }

    public function testGetAnnotationsWithInvalidHydraSupportedOperation()
    {
        $hydraDoc = ['hydra:supportedClass' => [
            ['@id' => '#Entrypoint'],
            ['@id' => '#Dummy', 'hydra:supportedOperation' => 'not an array'],
        ]];

        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizerProphecy->normalize(new Documentation(new ResourceNameCollection([Dummy::class])))->willReturn($hydraDoc)->shouldBeCalled();

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $dummyResourceMetadata = (new ResourceMetadata())
            ->withShortName('Dummy')
            ->withItemOperations([
                'get' => [
                    'method' => 'GET',
                ],
            ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($dummyResourceMetadata)->shouldBeCalled();

        $route = (new Route('/dummies/{id}'))->setMethods(['GET']);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->willReturn('GET')->shouldBeCalled();
        $operationMethodResolverProphecy->getItemOperationRoute(Dummy::class, 'get')->willReturn($route)->shouldBeCalled();

        $apiPlatformProvider = new ApiPlatformProvider(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $documentationNormalizerProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal(),
            $operationMethodResolverProphecy->reveal()
        );

        $apiDoc = new ApiDoc(['resource' => $route->getPath(), 'description' => '', 'resourceDescription' => '', 'section' => '']);
        $apiDoc->setRoute($route);

        $this->assertEquals([$apiDoc], $apiPlatformProvider->getAnnotations());
    }

    public function testGetAnnotationsWithEmptyHydraSupportedOperation()
    {
        $hydraDoc = ['hydra:supportedClass' => [
            ['@id' => '#Entrypoint'],
            ['@id' => '#Dummy', 'hydra:supportedOperation' => []],
        ]];

        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizerProphecy->normalize(new Documentation(new ResourceNameCollection([Dummy::class])))->willReturn($hydraDoc)->shouldBeCalled();

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $dummyResourceMetadata = (new ResourceMetadata())
            ->withShortName('Dummy')
            ->withItemOperations([
                'get' => [
                    'method' => 'GET',
                ],
            ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($dummyResourceMetadata)->shouldBeCalled();

        $route = (new Route('/dummies/{id}'))->setMethods(['GET']);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->willReturn('GET')->shouldBeCalled();
        $operationMethodResolverProphecy->getItemOperationRoute(Dummy::class, 'get')->willReturn($route)->shouldBeCalled();

        $apiPlatformProvider = new ApiPlatformProvider(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $documentationNormalizerProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal(),
            $operationMethodResolverProphecy->reveal()
        );

        $apiDoc = new ApiDoc(['resource' => $route->getPath(), 'description' => '', 'resourceDescription' => '', 'section' => '']);
        $apiDoc->setRoute($route);

        $this->assertEquals([$apiDoc], $apiPlatformProvider->getAnnotations());
    }

    public function testGetAnnotationsWithInvalidHydraSupportedProperty()
    {
        $hydraDoc = ['hydra:supportedClass' => [
            ['@id' => '#Entrypoint', 'hydra:supportedProperty' => 'not an array'],
            ['@id' => '#Dummy'],
        ]];

        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizerProphecy->normalize(new Documentation(new ResourceNameCollection([Dummy::class])))->willReturn($hydraDoc)->shouldBeCalled();

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $dummyResourceMetadata = (new ResourceMetadata())
            ->withShortName('Dummy')
            ->withCollectionOperations([
                'get' => [
                    'method' => 'GET',
                ],
            ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($dummyResourceMetadata)->shouldBeCalled();

        $route = (new Route('/dummies'))->setMethods(['GET']);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->willReturn('GET')->shouldBeCalled();
        $operationMethodResolverProphecy->getCollectionOperationRoute(Dummy::class, 'get')->willReturn($route)->shouldBeCalled();

        $apiPlatformProvider = new ApiPlatformProvider(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $documentationNormalizerProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal(),
            $operationMethodResolverProphecy->reveal()
        );

        $apiDoc = new ApiDoc(['resource' => $route->getPath(), 'description' => '', 'resourceDescription' => '', 'section' => '', 'filters' => []]);
        $apiDoc->setRoute($route);

        $this->assertEquals([$apiDoc], $apiPlatformProvider->getAnnotations());
    }

    public function testGetAnnotationsWithEmptyHydraSupportedProperty()
    {
        $hydraDoc = ['hydra:supportedClass' => [
            ['@id' => '#Entrypoint', 'hydra:supportedProperty' => []],
            ['@id' => '#Dummy'],
        ]];

        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizerProphecy->normalize(new Documentation(new ResourceNameCollection([Dummy::class])))->willReturn($hydraDoc)->shouldBeCalled();

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $dummyResourceMetadata = (new ResourceMetadata())
            ->withShortName('Dummy')
            ->withCollectionOperations([
                'get' => [
                    'method' => 'GET',
                ],
            ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($dummyResourceMetadata)->shouldBeCalled();

        $route = (new Route('/dummies'))->setMethods(['GET']);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->willReturn('GET')->shouldBeCalled();
        $operationMethodResolverProphecy->getCollectionOperationRoute(Dummy::class, 'get')->willReturn($route)->shouldBeCalled();

        $apiPlatformProvider = new ApiPlatformProvider(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $documentationNormalizerProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal(),
            $operationMethodResolverProphecy->reveal()
        );

        $apiDoc = new ApiDoc(['resource' => $route->getPath(), 'description' => '', 'resourceDescription' => '', 'section' => '', 'filters' => []]);
        $apiDoc->setRoute($route);

        $this->assertEquals([$apiDoc], $apiPlatformProvider->getAnnotations());
    }
}
