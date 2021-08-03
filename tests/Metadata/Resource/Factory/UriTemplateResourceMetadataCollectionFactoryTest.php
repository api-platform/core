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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\UriTemplateResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

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
                    identifiers: ['id' => [AttributeResource::class, 'id']],
                    operations: [
                        '_api_AttributeResource_get' => new Get(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_AttributeResource_put' => new Put(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_AttributeResource_delete' => new Delete(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_AttributeResource_get_collection' => new GetCollection(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: []),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    identifiers: ['dummyId' => [Dummy::class, 'id'], 'id' => [AttributeResource::class, 'id']],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{id}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                            shortName: 'AttributeResource',
                            identifiers: ['dummyId' => [Dummy::class, 'id'], 'id' => [AttributeResource::class, 'id']],
                        ),
                    ]
                ),
            ]),
        );
        $uriTemplateResourceMetadataCollectionFactory = new UriTemplateResourceMetadataCollectionFactory($pathSegmentNameGeneratorProphecy->reveal(), $resourceCollectionMetadataFactory->reveal());

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    identifiers: ['id' => [AttributeResource::class, 'id']],
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    operations: [
                        '_api_/attribute_resources/{id}.{_format}_get' => new Get(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_/attribute_resources/{id}.{_format}_put' => new Put(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_/attribute_resources/{id}.{_format}_delete' => new Delete(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_/attribute_resources.{_format}_get_collection' => new GetCollection(uriTemplate: '/attribute_resources.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: []),
                    ]
                ),
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    identifiers: ['dummyId' => [Dummy::class, 'id'], 'id' => [AttributeResource::class, 'id']],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{id}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                            shortName: 'AttributeResource',
                            identifiers: ['dummyId' => [Dummy::class, 'id'], 'id' => [AttributeResource::class, 'id']],
                            extraProperties: ['user_defined_uri_template' => true]
                        ),
                    ]
                ),
            ]),
            $uriTemplateResourceMetadataCollectionFactory->create(AttributeResource::class)
        );
    }
}
