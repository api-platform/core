<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Swagger\Util;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Swagger\Util\SwaggerDefinitions;
use ApiPlatform\Core\Swagger\Util\SwaggerTypeResolver;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SwaggerDefinitionsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDefinition()
    {
        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'put' => ['method' => 'PUT'],
            ],
            [],
            []
        );
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'put',
            'operation' => ['method' => 'PUT'],
            'isCollection' => true,
            'path' => '/dummies', 'method' => 'PUT',
            'mimeTypes' => ['application/ld+json'],
        ];

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(
            new PropertyNameCollection(['name', 'nameConverted'])
        );
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, null, null, false)
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'nameConverted')->shouldBeCalled()->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a converted name.', true, true, null, null, false)
        );

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

        $this->assertEquals('Dummy', $swaggerDefinitions->get($operationData));
        $definitions = $swaggerDefinitions->getDefinitions();
        $this->assertEquals(new \ArrayObject([
            'Dummy' => new \ArrayObject([
                'type' => 'object',
                'description' => 'This is a dummy.',
                'externalDocs' => [
                    'url' => 'http://schema.example.com/Dummy',
                ],
                'properties' => [
                    'name' => new \ArrayObject([
                        'description' => 'This is a name.',
                        'type' => 'string',
                    ]),
                    'nameConverted' => new \ArrayObject([
                        'description' => 'This is a converted name.',
                        'type' => 'string',
                    ]),
                ],
            ]),
        ]), $definitions);
    }

    public function testGetDefinitionWithNameConverter()
    {
        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'put' => ['method' => 'PUT'],
            ],
            [],
            []
        );
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'put',
            'operation' => ['method' => 'PUT'],
            'isCollection' => true,
            'path' => '/dummies', 'method' => 'PUT',
            'mimeTypes' => ['application/ld+json'],
        ];

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(
            new PropertyNameCollection(['name', 'nameConverted'])
        );
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, null, null, false)
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'nameConverted')->shouldBeCalled()->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a converted name.', true, true, null, null, false)
        );

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $typeResolver = new SwaggerTypeResolver(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('name')->willReturn('name')->shouldBeCalled();
        $nameConverterProphecy->normalize('nameConverted')->willReturn('name_converted')->shouldBeCalled();

        $swaggerDefinitions = new SwaggerDefinitions(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $typeResolver,
            $nameConverterProphecy->reveal()
        );

        $this->assertEquals('Dummy', $swaggerDefinitions->get($operationData));
        $definitions = $swaggerDefinitions->getDefinitions();
        $this->assertArrayHasKey('name_converted', $definitions['Dummy']['properties']);
    }
}
