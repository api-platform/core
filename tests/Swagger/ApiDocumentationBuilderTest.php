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
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Swagger\ApiDocumentationBuilder;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ApiDocumentationBuilderTest extends \PHPUnit_Framework_TestCase /**/
{
    public function testGetApiDocumention()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $titre = 'Test Api';
        $desc = 'test ApiGerard';
        $iriConverter = $this->prophesize(IriConverterInterface::class);
        $formats = ['jsonld' => ['application/ld+json']];
        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST']], []);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummy' => 'dummy']))->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn($dummyMetadata);
        $propertyNameCollectionFactoryProphecy->create('dummy', [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));
        $propertyMetadataFactoryProphecy->create('dummy', 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'name', true, true, true, true, false, false, null, []));
        $operationMethodResolverProphecy->getItemOperationMethod('dummy', 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod('dummy', 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod('dummy', 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod('dummy', 'post')->shouldBeCalled()->willReturn('POST');
        $iriConverter->getIriFromResourceClass('dummy')->shouldBeCalled()->willReturn('/dummies');
        $apiDocumentationBuilder = new ApiDocumentationBuilder($resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $contextBuilderProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $operationMethodResolverProphecy->reveal(), $urlGeneratorProphecy->reveal(), $iriConverter->reveal(), $formats, $titre, $desc);

        $swaggerDocumentation = $apiDocumentationBuilder->getApiDocumentation();
        $this->assertEquals($swaggerDocumentation['swagger'], 2.0);
        $this->assertEquals($swaggerDocumentation['info']['title'], $titre);
        $this->assertEquals($swaggerDocumentation['info']['description'], $desc);
        $this->assertEquals($swaggerDocumentation['definitions'], ['dummy' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]]]);
        $this->assertEquals($swaggerDocumentation['externalDocs'
         ], ['description' => 'Find more about API Platform', 'url' => 'https://api-platform.com']);

        $this->assertEquals($swaggerDocumentation['paths']['/dummies']['get'], [
                'tags' => ['dummy'],
                'produces' => ['application/ld+json'],
                'summary' => 'Retrieves the collection of dummy resources.',
                'responses' => [
                        200 => ['description' => 'Successful operation'],
                    ],
        ]);

        $this->assertEquals($swaggerDocumentation['paths']['/dummies']['post'], [
                'tags' => array(
                        0 => 'dummy',
                    ),
                'produces' => array(
                        0 => 'application/ld+json',
                    ),
                'consumes' => array(
                        0 => 'application/ld+json',
                    ),
                'summary' => 'Creates a dummy resource.',
                'parameters' => array(
                        0 => array(
                                'in' => 'body',
                                'name' => 'body',
                                'description' => 'dummy resource to be added',
                                'schema' => array(
                                        '$ref' => '#/definitions/dummy',
                                    ),
                            ),
                    ),
                'responses' => array(
                        201 => array(
                                'description' => 'Successful operation',
                                'schema' => array(
                                        '$ref' => '#/definitions/dummy',
                                    ),
                            ),
                        400 => array(
                                'description' => 'Invalid input',
                            ),
                        404 => array(
                                'description' => 'Resource not found',
                            ),
                    ),
        ]);

        $this->assertEquals($swaggerDocumentation['paths']['/dummies/{id}']['get'], [
                'tags' => array(
                        0 => 'dummy',
                    ),
                'produces' => array(
                        0 => 'application/ld+json',
                    ),
                'summary' => 'Retrieves dummy resource.',
                'parameters' => array(
                        0 => array(
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'type' => 'integer',
                            ),
                    ),
                'responses' => array(
                        200 => array(
                                'description' => 'Successful operation',
                                'schema' => array(
                                        '$ref' => '#/definitions/dummy',
                                    ),
                            ),
                        404 => array(
                                'description' => 'Resource not found',
                            ),
                    ),
        ]);

        $this->assertEquals($swaggerDocumentation['paths']['/dummies/{id}']['put'], [
            'tags' => [
                    0 => 'dummy',
                ],
            'produces' => [
                    0 => 'application/ld+json',
                ],
            'consumes' => array(
                    0 => 'application/ld+json',
                ),
            'summary' => 'Replaces the dummy resource.',
            'parameters' => array(
                    0 => array(
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'type' => 'integer',
                        ),
                    1 => array(
                            'in' => 'body',
                            'name' => 'body',
                            'description' => 'dummy resource to be added',
                            'schema' => array(
                                    '$ref' => '#/definitions/dummy',
                                ),
                        ),
                ),
            'responses' => array(
                    200 => array(
                            'description' => 'Successful operation',
                            'schema' => array(
                                    '$ref' => '#/definitions/dummy',
                                ),
                        ),
                    400 => array(
                            'description' => 'Invalid input',
                        ),
                    404 => array(
                            'description' => 'Resource not found',
                        ),
                ),
        ]);
    }
}
