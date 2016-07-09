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
        $operationMethodResolverProphecy->getItemOperationMethod('dummy', 'get')->shouldBeCalled()->willReturn('get');
        $operationMethodResolverProphecy->getItemOperationMethod('dummy', 'put')->shouldBeCalled()->willReturn('put');
        $operationMethodResolverProphecy->getCollectionOperationMethod('dummy', 'get')->shouldBeCalled()->willReturn('get');
        $operationMethodResolverProphecy->getCollectionOperationMethod('dummy', 'post')->shouldBeCalled()->willReturn('post');
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
            'tags' => [0 => 'dummy'],
                'produces' => ['application/ld+json'],
                'consumes' => ['application/ld+json'],
    ]
                       );

        $this->assertEquals($swaggerDocumentation['paths']['/dummies']['post'], [
                'tags' => [0 => 'dummy'],
                'produces' => ['application/ld+json'],
                'consumes' => ['application/ld+json'],
            ]
        );
        $this->assertEquals($swaggerDocumentation['paths']['/dummies/{id}']['get'], [
                'tags' => [0 => 'dummy'],
                'produces' => ['application/ld+json'],
                'consumes' => ['application/ld+json'],
            ]
        );
        $this->assertEquals($swaggerDocumentation['paths']['/dummies/{id}']['put'], [
                'tags' => [0 => 'dummy'],
                'produces' => ['application/ld+json'],
                'consumes' => ['application/ld+json'],
            ]
        );
    }
}
