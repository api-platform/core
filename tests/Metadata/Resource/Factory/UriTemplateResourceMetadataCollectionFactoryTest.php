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

namespace ApiPlatform\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\UriTemplateResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\UriVariable;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class UriTemplateResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('AttributeResource')->willReturn('attribute_resources');
        $pathSegmentNameGeneratorProphecy->getSegmentName('AttributeDefaultOperations')->willReturn('attribute_default_operations');
        $resourceCollectionMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactory->create(AttributeResource::class)->willReturn(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriVariables: ['id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])],
                    operations: [
                        '_api_AttributeResource_get' => new Get(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])]),
                        '_api_AttributeResource_put' => new Put(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])]),
                        '_api_AttributeResource_delete' => new Delete(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])]),
                        '_api_AttributeResource_get_collection' => new GetCollection(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder'),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    uriVariables: ['dummyId' => new UriVariable(targetClass: Dummy::class, identifiers: ['id']), 'id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{id}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                            shortName: 'AttributeResource',
                            uriVariables: ['dummyId' => new UriVariable(targetClass: Dummy::class, identifiers: ['id']), 'id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])],
                        ),
                    ]
                ),
            ]),
        );

        $uriTemplateResourceMetadataCollectionFactory = new UriTemplateResourceMetadataCollectionFactory($pathSegmentNameGeneratorProphecy->reveal(), null, null, $resourceCollectionMetadataFactory->reveal());

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    uriVariables: ['id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])],
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    operations: [
                        '_api_/attribute_resources/{id}.{_format}_get' => new Get(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])]),
                        '_api_/attribute_resources/{id}.{_format}_put' => new Put(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])]),
                        '_api_/attribute_resources/{id}.{_format}_delete' => new Delete(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', uriVariables: ['id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])]),
                        '_api_/attribute_resources.{_format}_get_collection' => new GetCollection(uriTemplate: '/attribute_resources.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder'),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    uriVariables: ['dummyId' => new UriVariable(targetClass: Dummy::class, identifiers: ['id']), 'id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{id}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                            shortName: 'AttributeResource',
                            uriVariables: ['dummyId' => new UriVariable(targetClass: Dummy::class, identifiers: ['id']), 'id' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['id'])],
                            extraProperties: ['user_defined_uri_template' => true]
                        ),
                    ]
                ),
            ]),
            $uriTemplateResourceMetadataCollectionFactory->create(AttributeResource::class)
        );
    }

    public function testCreateWithUriVariableAttribute()
    {
        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('AttributeResource')->willReturn('attribute_resources');
        $resourceCollectionMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactory->create(AttributeResource::class)->willReturn(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}',
                    uriVariables: ['identifier' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['identifier'])],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{identifier}_get' => new Get(
                            class: AttributeResource::class,
                            uriVariables: ['identifier' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['identifier'])],
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}',
                            shortName: 'AttributeResource'
                        ),
                    ]
                ),
            ]),
        );

        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(AttributeResource::class)->willReturn(new PropertyNameCollection(['identifier', 'dummy']));
        $propertyNameCollectionFactory->create(Dummy::class)->willReturn(new PropertyNameCollection(['id']));
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(AttributeResource::class, 'identifier')->willReturn(new ApiProperty())->shouldNotBeCalled();
        $propertyMetadataFactory->create(AttributeResource::class, 'dummy')->willReturn((new ApiProperty())->withBuiltinTypes([new Type('object', true, Dummy::class)]));
        $propertyMetadataFactory->create(Dummy::class, 'id')->willReturn(new ApiProperty());
        $uriTemplateResourceMetadataCollectionFactory = new UriTemplateResourceMetadataCollectionFactory($pathSegmentNameGeneratorProphecy->reveal(), $propertyNameCollectionFactory->reveal(), $propertyMetadataFactory->reveal(), $resourceCollectionMetadataFactory->reveal());

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}',
                    uriVariables: ['identifier' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['identifier']), 'dummyId' => new UriVariable(parameterName: 'dummyId', targetClass: Dummy::class, identifiers: ['id'], property: 'dummy')],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{identifier}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{identifier}',
                            shortName: 'AttributeResource',
                            uriVariables: ['dummyId' => new UriVariable(parameterName: 'dummyId', targetClass: Dummy::class, identifiers: ['id'], property: 'dummy'), 'identifier' => new UriVariable(targetClass: AttributeResource::class, identifiers: ['identifier'])],
                            extraProperties: ['user_defined_uri_template' => true]
                        ),
                    ]
                ),
            ]),
            $uriTemplateResourceMetadataCollectionFactory->create(AttributeResource::class)
        );
    }
}
