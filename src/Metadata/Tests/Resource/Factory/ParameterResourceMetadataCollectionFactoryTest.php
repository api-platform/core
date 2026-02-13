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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ParameterResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\WithLimitedPropertyParameter;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\WithParameter;
use ApiPlatform\OpenApi\Model\Parameter;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\TypeInfo\Type;

class ParameterResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testParameterFactory(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'hydra', 'everywhere']));
        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturnCallback(
            static fn (string $class, string $property) => match ($property) {
                'id' => new ApiProperty(identifier: true),
                default => new ApiProperty(readable: true),
            }
        );
        $filterLocator = $this->createStub(ContainerInterface::class);
        $filterLocator->method('has')->willReturn(true);
        $filterLocator->method('get')->willReturn(new class implements FilterInterface {
            public function getDescription(string $resourceClass): array
            {
                // @phpstan-ignore-next-line return.type
                return [
                    'hydra' => [
                        'property' => 'hydra',
                        'type' => 'string',
                        'required' => false,
                        'schema' => ['type' => 'foo'],
                        'openapi' => new Parameter('test', 'query'),
                    ],
                    'everywhere' => [
                        'property' => 'everywhere',
                        'type' => 'string',
                        'required' => false,
                        'openapi' => ['allowEmptyValue' => true],
                    ],
                ];
            }
        });
        $parameter = new ParameterResourceMetadataCollectionFactory(
            $nameCollection,
            $propertyMetadata,
            new AttributesResourceMetadataCollectionFactory(),
            $filterLocator
        );
        $operation = $parameter->create(WithParameter::class)->getOperation('collection');
        $this->assertInstanceOf(Parameters::class, $parameters = $operation->getParameters());
        $hydraParameter = $parameters->get('hydra', QueryParameter::class);
        $this->assertEquals(['type' => 'foo'], $hydraParameter->getSchema());
        $this->assertEquals(new Parameter('test', 'query'), $hydraParameter->getOpenApi());
        $everywhere = $parameters->get('everywhere', QueryParameter::class);
        $this->assertNull($everywhere->getOpenApi());
    }

    public function testQueryParameterWithPropertyPlaceholder(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'description']));

        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturn(
            new ApiProperty(readable: true),
        );

        $filterLocator = $this->createStub(ContainerInterface::class);
        $filterLocator->method('has')->willReturn(false); // No specific filter logic needed for this test

        $parameterFactory = new ParameterResourceMetadataCollectionFactory(
            $nameCollection,
            $propertyMetadata,
            new AttributesResourceMetadataCollectionFactory(),
            $filterLocator
        );

        $resourceMetadataCollection = $parameterFactory->create(HasParameterAttribute::class);
        $operation = $resourceMetadataCollection->getOperation(forceCollection: true);
        $parameters = $operation->getParameters();

        $this->assertInstanceOf(Parameters::class, $parameters);

        // Assert that the original parameter with ':property' is removed
        $this->assertFalse($parameters->has('search[:property]'));

        // Assert that the new parameters are created and have the correct properties
        $this->assertTrue($parameters->has('search[name]'));
        $this->assertTrue($parameters->has('search[description]'));
        $this->assertTrue($parameters->has('static_param'));

        $searchNameParam = $parameters->get('search[name]');
        $this->assertInstanceOf(QueryParameter::class, $searchNameParam);
        $this->assertSame('Search by property', $searchNameParam->getDescription());
        $this->assertSame('name', $searchNameParam->getProperty());
        $this->assertSame('search[name]', $searchNameParam->getKey());

        $searchDescriptionParam = $parameters->get('search[description]');
        $this->assertInstanceOf(QueryParameter::class, $searchDescriptionParam);
        $this->assertSame('Search by property', $searchDescriptionParam->getDescription());
        $this->assertSame('description', $searchDescriptionParam->getProperty());
        $this->assertSame('search[description]', $searchDescriptionParam->getKey());

        $staticParam = $parameters->get('static_param');
        $this->assertInstanceOf(QueryParameter::class, $staticParam);
        $this->assertSame('A static parameter', $staticParam->getDescription());
        $this->assertNull($staticParam->getProperty());
        $this->assertSame('static_param', $staticParam->getKey());
    }

    public function testQueryParameterWithNestedPropertyPlaceholder(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'related']));

        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturn(
            new ApiProperty(readable: true),
        );

        $filterLocator = $this->createStub(ContainerInterface::class);
        $filterLocator->method('has')->willReturn(false);

        $parameterFactory = new ParameterResourceMetadataCollectionFactory(
            $nameCollection,
            $propertyMetadata,
            new AttributesResourceMetadataCollectionFactory(),
            $filterLocator
        );

        $resourceMetadataCollection = $parameterFactory->create(HasNestedParameterAttribute::class);
        $operation = $resourceMetadataCollection->getOperation(forceCollection: true);
        $parameters = $operation->getParameters();

        $this->assertInstanceOf(Parameters::class, $parameters);

        $this->assertFalse($parameters->has('search[:property]'));
        $this->assertTrue($parameters->has('search[name]'));
        $this->assertTrue($parameters->has('search[related.nested]'));

        $searchNameParam = $parameters->get('search[name]');
        $this->assertInstanceOf(QueryParameter::class, $searchNameParam);
        $this->assertNull($searchNameParam->getDescription());
        $this->assertSame('name', $searchNameParam->getProperty());
        $this->assertSame('search[name]', $searchNameParam->getKey());

        $searchRelatedNestedParam = $parameters->get('search[related.nested]');
        $this->assertInstanceOf(QueryParameter::class, $searchRelatedNestedParam);
        $this->assertNull($searchRelatedNestedParam->getDescription());
        $this->assertSame('related.nested', $searchRelatedNestedParam->getProperty());
        $this->assertSame('search[related.nested]', $searchRelatedNestedParam->getKey());
    }

    public function testParameterFactoryNoFilter(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'hydra', 'everywhere']));
        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturnCallback(
            static fn (string $class, string $property) => match ($property) {
                'id' => new ApiProperty(identifier: true),
                default => new ApiProperty(readable: true),
            }
        );
        $filterLocator = $this->createStub(ContainerInterface::class);
        $filterLocator->method('has')->willReturn(false);
        $parameter = new ParameterResourceMetadataCollectionFactory(
            $nameCollection,
            $propertyMetadata,
            new AttributesResourceMetadataCollectionFactory(),
            $filterLocator
        );
        $operation = $parameter->create(WithParameter::class)->getOperation('collection');
        $this->assertInstanceOf(Parameters::class, $parameters = $operation->getParameters());
    }

    public function testParameterFactoryWithLimitedProperties(): void
    {
        $nameCollection = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->expects($this->never())->method('create');

        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturnMap([
            [WithLimitedPropertyParameter::class, 'name', [], new ApiProperty(readable: true)],
        ]);

        $filterLocator = $this->createStub(ContainerInterface::class);
        $filterLocator->method('has')->willReturn(false);

        $attributesFactory = new AttributesResourceMetadataCollectionFactory();
        $parameterFactory = new ParameterResourceMetadataCollectionFactory(
            $nameCollection,
            $propertyMetadata,
            $attributesFactory,
            $filterLocator
        );

        $resourceCollection = $parameterFactory->create(WithLimitedPropertyParameter::class);
        $operation = $resourceCollection->getOperation('collection');
        $parameters = $operation->getParameters();

        $this->assertInstanceOf(Parameters::class, $parameters);
        $this->assertCount(1, $parameters);
        $this->assertTrue($parameters->has('name'));
        $this->assertFalse($parameters->has('id'));
        $this->assertFalse($parameters->has('description'));

        $param = $parameters->get('name');
        $this->assertInstanceOf(QueryParameter::class, $param);
        $this->assertSame('name', $param->getKey());
        $this->assertSame(['name'], $param->getProperties());
    }

    public function testNestedPropertyWithNameConverter(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'related']));

        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturn(
            new ApiProperty(readable: true),
        );

        $filterLocator = $this->createStub(ContainerInterface::class);
        $filterLocator->method('has')->willReturn(false);

        $parameterFactory = new ParameterResourceMetadataCollectionFactory(
            $nameCollection,
            $propertyMetadata,
            new AttributesResourceMetadataCollectionFactory(),
            $filterLocator,
            new CamelCaseToSnakeCaseNameConverter()
        );

        $resourceMetadataCollection = $parameterFactory->create(HasNestedParameterAttribute::class);
        $operation = $resourceMetadataCollection->getOperation(forceCollection: true);
        $parameters = $operation->getParameters();

        $this->assertInstanceOf(Parameters::class, $parameters);

        $this->assertFalse($parameters->has('search[:property]'));

        $this->assertTrue($parameters->has('search[name]'));
        $searchSimpleParam = $parameters->get('search[name]');
        $this->assertSame('name', $searchSimpleParam->getProperty());
        $this->assertSame('search[name]', $searchSimpleParam->getKey());

        $this->assertTrue($parameters->has('search[related.nested]'));
        $searchNestedParam = $parameters->get('search[related.nested]');

        $this->assertSame('related.nested', $searchNestedParam->getProperty());
        $this->assertSame('search[related.nested]', $searchNestedParam->getKey());
    }

    private function createNestedPropertyFactory(): ParameterResourceMetadataCollectionFactory
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name']));

        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturnCallback(
            static function (string $class, string $property): ApiProperty {
                if (NestedTestOrder::class === $class && 'product' === $property) {
                    return new ApiProperty(readable: true, nativeType: Type::object(NestedTestProduct::class));
                }
                if (NestedTestProduct::class === $class && 'productVariations' === $property) {
                    return new ApiProperty(readable: true, nativeType: Type::list(Type::object(NestedTestVariation::class)));
                }

                return new ApiProperty(readable: true);
            }
        );

        $filterLocator = $this->createStub(ContainerInterface::class);
        $filterLocator->method('has')->willReturn(false);

        return new ParameterResourceMetadataCollectionFactory(
            $nameCollection,
            $propertyMetadata,
            new AttributesResourceMetadataCollectionFactory(),
            $filterLocator,
            new CamelCaseToSnakeCaseNameConverter(),
        );
    }

    public function testNestedPropertyInfoOnSingularProperty(): void
    {
        $factory = $this->createNestedPropertyFactory();
        $collection = $factory->create(NestedTestOrder::class);
        $operation = $collection->getOperation(forceCollection: true);
        $parameters = $operation->getParameters();

        $param = $parameters->get('product.name');
        $this->assertNotNull($param, 'Parameter product.name should exist');

        $extra = $param->getExtraProperties();
        $this->assertArrayHasKey('nested_property_info', $extra);

        $info = $extra['nested_property_info'];
        $this->assertSame(['product'], $info['relation_segments']);
        $this->assertSame(['product'], $info['converted_relation_segments']);
        $this->assertSame('name', $info['leaf_property']);
        $this->assertSame(NestedTestProduct::class, $info['leaf_class']);
        $this->assertSame([NestedTestOrder::class], $info['relation_classes']);
    }

    public function testNestedPropertyInfoOnDeeplyNestedProperty(): void
    {
        $factory = $this->createNestedPropertyFactory();
        $collection = $factory->create(NestedTestOrder::class);
        $operation = $collection->getOperation('deep_collection');
        $parameters = $operation->getParameters();

        $param = $parameters->get('product.productVariations.variantName');
        $this->assertNotNull($param, 'Parameter product.productVariations.variantName should exist');

        $extra = $param->getExtraProperties();
        $this->assertArrayHasKey('nested_property_info', $extra);

        $info = $extra['nested_property_info'];
        $this->assertSame(['product', 'productVariations'], $info['relation_segments']);
        $this->assertSame(['product', 'product_variations'], $info['converted_relation_segments']);
        $this->assertSame('variant_name', $info['leaf_property']);
        $this->assertSame(NestedTestVariation::class, $info['leaf_class']);
        $this->assertSame([NestedTestOrder::class, NestedTestProduct::class], $info['relation_classes']);
    }

    public function testNestedPropertyInfoOnExpandedPlaceholderParameter(): void
    {
        $factory = $this->createNestedPropertyFactory();
        $collection = $factory->create(NestedTestOrder::class);
        $operation = $collection->getOperation('search_collection');
        $parameters = $operation->getParameters();

        $searchProductName = $parameters->get('search[product.name]');
        $this->assertNotNull($searchProductName, 'Parameter search[product.name] should exist');

        $extra = $searchProductName->getExtraProperties();
        $this->assertArrayHasKey('nested_property_info', $extra);

        $info = $extra['nested_property_info'];
        $this->assertSame(['product'], $info['relation_segments']);
        $this->assertSame('name', $info['leaf_property']);
        $this->assertSame(NestedTestProduct::class, $info['leaf_class']);
    }

    public function testSimplePropertyHasNoNestedPropertyInfo(): void
    {
        $factory = $this->createNestedPropertyFactory();
        $collection = $factory->create(NestedTestOrder::class);
        $operation = $collection->getOperation(forceCollection: true);
        $parameters = $operation->getParameters();

        $param = $parameters->get('name');
        $this->assertNotNull($param);
        $this->assertArrayNotHasKey('nested_property_info', $param->getExtraProperties());
    }
}

#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'search[:property]' => new QueryParameter(
                    description: 'Search by property',
                    properties: ['name', 'description']
                ),
                'static_param' => new QueryParameter(
                    description: 'A static parameter'
                ),
            ]
        ),
    ]
)]
class HasParameterAttribute
{
    public $id;
    public $name;
    public $description;
}

#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'search[:property]' => new QueryParameter(
                    properties: ['name', 'related.nested']
                ),
            ]
        ),
    ]
)]
class HasNestedParameterAttribute
{
    public $id;
    public $name;
    public $related;
}

#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'name' => new QueryParameter(),
                'product.name' => new QueryParameter(property: 'product.name'),
            ]
        ),
        new GetCollection(
            uriTemplate: '/nested_test_orders/deep',
            name: 'deep_collection',
            parameters: [
                'product.productVariations.variantName' => new QueryParameter(property: 'product.productVariations.variantName'),
            ]
        ),
        new GetCollection(
            uriTemplate: '/nested_test_orders/search',
            name: 'search_collection',
            parameters: [
                'search[:property]' => new QueryParameter(
                    properties: ['name', 'product.name']
                ),
            ]
        ),
    ]
)]
class NestedTestOrder
{
    public ?int $id = null;
    public ?string $name = null;
    public ?NestedTestProduct $product = null;
}

#[ApiResource]
class NestedTestProduct
{
    public ?int $id = null;
    public ?string $name = null;
    /** @var NestedTestVariation[] */
    public array $productVariations = [];
}

#[ApiResource]
class NestedTestVariation
{
    public ?int $id = null;
    public ?string $variantName = null;
}
