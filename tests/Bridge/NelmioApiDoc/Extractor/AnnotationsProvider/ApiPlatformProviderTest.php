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

namespace ApiPlatform\Core\Tests\NelmioApiDoc\Extractor\AnnotationsProvider;

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
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\AnnotationsProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
class ApiPlatformProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory = $resourceNameCollectionFactoryProphecy->reveal();

        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizer = $documentationNormalizerProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $filters = new FilterCollection();

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolver = $operationMethodResolverProphecy->reveal();

        $apiPlatformProvider = new ApiPlatformProvider($resourceNameCollectionFactory, $documentationNormalizer, $resourceMetadataFactory, $filters, $operationMethodResolver);

        $this->assertInstanceOf(AnnotationsProviderInterface::class, $apiPlatformProvider);
    }

    public function testGetAnnotations()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();
        $resourceNameCollectionFactory = $resourceNameCollectionFactoryProphecy->reveal();

        $apiDocumentationBuilderProphecy = $this->prophesize(NormalizerInterface::class);
        $hydraDoc = $this->getHydraDoc();
        $apiDocumentationBuilderProphecy->normalize(new Documentation(new ResourceNameCollection([Dummy::class])))->willReturn($hydraDoc)->shouldBeCalled();
        $apiDocumentationBuilder = $apiDocumentationBuilderProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
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
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($dummyResourceMetadata)->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $dummySearchFilterProphecy = $this->prophesize(FilterInterface::class);
        $dummySearchFilterProphecy->getDescription(Dummy::class)->willReturn([
            'name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => 'false',
                'strategy' => 'partial',
            ],
        ])->shouldBeCalled();
        $dummySearchFilter = $dummySearchFilterProphecy->reveal();
        $filters = new FilterCollection([
            'my_dummy.search' => $dummySearchFilter,
        ]);

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
        $operationMethodResolver = $operationMethodResolverProphecy->reveal();

        $apiPlatformProvider = new ApiPlatformProvider($resourceNameCollectionFactory, $apiDocumentationBuilder, $resourceMetadataFactory, $filters, $operationMethodResolver);

        $actual = $apiPlatformProvider->getAnnotations();

        $this->assertInternalType('array', $actual);
        $this->assertCount(5, $actual);

        $this->assertInstanceOf(ApiDoc::class, $actual[0]);
        $this->assertEquals('/dummies', $actual[0]->getResource());
        $this->assertEquals('Retrieves the collection of Dummy resources.', $actual[0]->getDescription());
        $this->assertEquals('Dummy', $actual[0]->getResourceDescription());
        $this->assertEquals('Dummy', $actual[0]->getSection());
        $this->assertEquals(sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'get'), $actual[0]->getOutput());
        $this->assertEquals([
            'name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => 'false',
                'strategy' => 'partial',
            ],
        ], $actual[0]->getFilters());
        $this->assertInstanceOf(Route::class, $actual[0]->getRoute());
        $this->assertEquals('/dummies', $actual[0]->getRoute()->getPath());
        $this->assertEquals(['GET'], $actual[0]->getRoute()->getMethods());

        $this->assertInstanceOf(ApiDoc::class, $actual[1]);
        $this->assertEquals('/dummies', $actual[1]->getResource());
        $this->assertEquals('Creates a Dummy resource.', $actual[1]->getDescription());
        $this->assertEquals('Dummy', $actual[1]->getResourceDescription());
        $this->assertEquals('Dummy', $actual[1]->getSection());
        $this->assertEquals(sprintf('%s:%s:%s', ApiPlatformParser::IN_PREFIX, Dummy::class, 'post'), $actual[1]->getInput());
        $this->assertEquals(sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'post'), $actual[1]->getOutput());
        $this->assertInstanceOf(Route::class, $actual[1]->getRoute());
        $this->assertEquals('/dummies', $actual[1]->getRoute()->getPath());
        $this->assertEquals(['POST'], $actual[1]->getRoute()->getMethods());

        $this->assertInstanceOf(ApiDoc::class, $actual[2]);
        $this->assertEquals('/dummies/{id}', $actual[2]->getResource());
        $this->assertEquals('Retrieves Dummy resource.', $actual[2]->getDescription());
        $this->assertEquals('Dummy', $actual[2]->getResourceDescription());
        $this->assertEquals('Dummy', $actual[2]->getSection());
        $this->assertEquals(sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'get'), $actual[2]->getOutput());
        $this->assertInstanceOf(Route::class, $actual[2]->getRoute());
        $this->assertEquals('/dummies/{id}', $actual[2]->getRoute()->getPath());
        $this->assertEquals(['GET'], $actual[2]->getRoute()->getMethods());

        $this->assertInstanceOf(ApiDoc::class, $actual[3]);
        $this->assertEquals('/dummies/{id}', $actual[3]->getResource());
        $this->assertEquals('Replaces the Dummy resource.', $actual[3]->getDescription());
        $this->assertEquals('Dummy', $actual[3]->getResourceDescription());
        $this->assertEquals('Dummy', $actual[3]->getSection());
        $this->assertEquals(sprintf('%s:%s:%s', ApiPlatformParser::IN_PREFIX, Dummy::class, 'put'), $actual[3]->getInput());
        $this->assertEquals(sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'put'), $actual[3]->getOutput());
        $this->assertInstanceOf(Route::class, $actual[3]->getRoute());
        $this->assertEquals('/dummies/{id}', $actual[3]->getRoute()->getPath());
        $this->assertEquals(['PUT'], $actual[3]->getRoute()->getMethods());

        $this->assertInstanceOf(ApiDoc::class, $actual[4]);
        $this->assertEquals('/dummies/{id}', $actual[4]->getResource());
        $this->assertEquals('Deletes the Dummy resource.', $actual[4]->getDescription());
        $this->assertEquals('Dummy', $actual[4]->getResourceDescription());
        $this->assertEquals('Dummy', $actual[4]->getSection());
        $this->assertInstanceOf(Route::class, $actual[4]->getRoute());
        $this->assertEquals('/dummies/{id}', $actual[4]->getRoute()->getPath());
        $this->assertEquals(['DELETE'], $actual[4]->getRoute()->getMethods());
    }

    private function getHydraDoc()
    {
        $hydraDocJson = <<<JSON
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
}
