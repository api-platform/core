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
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
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

    public function testPatternParameterPriorityIsPreserved(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'description']));

        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturn(new ApiProperty(readable: true));

        $filterLocator = $this->createStub(ContainerInterface::class);
        $filterLocator->method('has')->willReturn(false);

        $parameterFactory = new ParameterResourceMetadataCollectionFactory(
            $nameCollection,
            $propertyMetadata,
            new AttributesResourceMetadataCollectionFactory(),
            $filterLocator
        );

        $resourceMetadataCollection = $parameterFactory->create(HasPatternParameterWithPriority::class);
        $operation = $resourceMetadataCollection->getOperation(forceCollection: true);
        $parameters = $operation->getParameters();

        $this->assertInstanceOf(Parameters::class, $parameters);

        $expandedParam = $parameters->get('order[name]');
        $this->assertNotNull($expandedParam);
        $this->assertSame(10, $expandedParam->getPriority(), 'Expanded pattern parameter must inherit parent priority');

        $qParam = $parameters->get('q');
        $this->assertNotNull($qParam);
        $this->assertSame(0, $qParam->getPriority());

        // Parameters must be iterated in priority order (highest first)
        $iteratedKeys = [];
        foreach ($parameters as $key => $parameter) {
            $iteratedKeys[] = $key;
        }

        $qIndex = array_search('q', $iteratedKeys, true);
        $orderNameIndex = array_search('order[name]', $iteratedKeys, true);
        $this->assertLessThan($qIndex, $orderNameIndex, 'Pattern parameter with priority 10 must be iterated before parameter with priority 0');
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

    public function testQueryParameterFromPropertyAttributes(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'isActive']));

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

        $resourceMetadataCollection = $parameterFactory->create(ParameterOnProperties::class);
        $operations = array_values(iterator_to_array($resourceMetadataCollection[0]->getOperations()));

        $this->assertCount(5, $operations);

        $getOperation = $operations[0];
        $this->assertInstanceOf(Get::class, $getOperation);
        $this->assertTrue($getOperation->getParameters()->has('search'));
        $this->assertTrue($getOperation->getParameters()->has('filter_active'));

        $collectionOperation = $operations[1];
        $this->assertInstanceOf(GetCollection::class, $collectionOperation);
        $this->assertTrue($collectionOperation->getParameters()->has('search'));
        $this->assertTrue($collectionOperation->getParameters()->has('filter_active'));

        $postOperation = $operations[2];
        $this->assertInstanceOf(Post::class, $postOperation);
        $this->assertTrue($postOperation->getParameters()->has('search'));
        $this->assertTrue($postOperation->getParameters()->has('filter_active'));

        $patchOperation = $operations[3];
        $this->assertInstanceOf(Patch::class, $patchOperation);
        $this->assertTrue($patchOperation->getParameters()->has('search'));
        $this->assertTrue($patchOperation->getParameters()->has('filter_active'));

        $deleteOperation = $operations[4];
        $this->assertInstanceOf(Delete::class, $deleteOperation);
        $this->assertTrue($deleteOperation->getParameters()->has('search'));
        $this->assertTrue($deleteOperation->getParameters()->has('filter_active'));

        $searchParam = $collectionOperation->getParameters()->get('search', QueryParameter::class);
        $this->assertInstanceOf(QueryParameter::class, $searchParam);
        $this->assertSame('search', $searchParam->getKey());
        $this->assertSame('name', $searchParam->getProperty());
        $this->assertSame('Search by name', $searchParam->getDescription());

        $filterParam = $collectionOperation->getParameters()->get('filter_active', QueryParameter::class);
        $this->assertInstanceOf(QueryParameter::class, $filterParam);
        $this->assertSame('filter_active', $filterParam->getKey());
        $this->assertSame('isActive', $filterParam->getProperty());
        $this->assertSame('Filter by active status', $filterParam->getDescription());
    }

    public function testQueryParameterFromPropertyAttributeThrowsExceptionWhenPropertyMismatch(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parameter attribute on property "name" must target itself or have no explicit property. Got "property: \'description\'" instead.');

        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'description']));

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

        $parameterFactory->create(ParameterOnPropertiesMismatchPropertyException::class);
    }

    public function testQueryParameterFromPropertyAttributeThrowsExceptionWhenPropertiesMismatch(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parameter attribute on property "name" must target itself or have no explicit properties. Got "properties: [description]" instead.');

        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'description']));

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

        $parameterFactory->create(ParameterOnPropertiesMismatchPropertiesException::class);
    }

    public function testQueryParameterFromPropertyAttributeThrowsExceptionWhenPropertiesHasMultipleWithoutSelf(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parameter attribute on property "name" must target itself or have no explicit properties. Got "properties: [description, active]" instead.');

        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'description', 'active']));

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

        $parameterFactory->create(ParameterOnPropertiesMismatchMultiplePropertiesException::class);
    }

    public function testQueryParameterFromPropertyAttributePropertiesSingleCorrectProperty(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'description']));

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

        $resourceMetadataCollection = $parameterFactory->create(ParameterOnPropertiesSingleCorrectProperty::class);
        $operations = array_values(iterator_to_array($resourceMetadataCollection[0]->getOperations()));

        $this->assertCount(5, $operations);

        $getOperation = $operations[0];
        $this->assertInstanceOf(Get::class, $getOperation);
        $this->assertTrue($getOperation->getParameters()->has('search'));

        $collectionOperation = $operations[1];
        $this->assertInstanceOf(GetCollection::class, $collectionOperation);
        $this->assertTrue($collectionOperation->getParameters()->has('search'));

        $postOperation = $operations[2];
        $this->assertInstanceOf(Post::class, $postOperation);
        $this->assertTrue($postOperation->getParameters()->has('search'));

        $patchOperation = $operations[3];
        $this->assertInstanceOf(Patch::class, $patchOperation);
        $this->assertTrue($patchOperation->getParameters()->has('search'));

        $deleteOperation = $operations[4];
        $this->assertInstanceOf(Delete::class, $deleteOperation);
        $this->assertTrue($deleteOperation->getParameters()->has('search'));

        $searchParam = $collectionOperation->getParameters()->get('search', QueryParameter::class);
        $this->assertInstanceOf(QueryParameter::class, $searchParam);
        $this->assertSame('search', $searchParam->getKey());
        $this->assertSame('name', $searchParam->getProperty());
        $this->assertSame(['name'], $searchParam->getProperties());
    }

    public function testQueryParameterFromPropertyAttributePropertiesHasMultipleIncludingSelf(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'description']));

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

        $resourceMetadataCollection = $parameterFactory->create(ParameterOnPropertiesMultiplePropertiesIncludingSelf::class);
        $operations = array_values(iterator_to_array($resourceMetadataCollection[0]->getOperations()));

        $this->assertCount(5, $operations);

        $getOperation = $operations[0];
        $this->assertInstanceOf(Get::class, $getOperation);
        $this->assertTrue($getOperation->getParameters()->has('search'));

        $collectionOperation = $operations[1];
        $this->assertInstanceOf(GetCollection::class, $collectionOperation);
        $this->assertTrue($collectionOperation->getParameters()->has('search'));

        $postOperation = $operations[2];
        $this->assertInstanceOf(Post::class, $postOperation);
        $this->assertTrue($postOperation->getParameters()->has('search'));

        $patchOperation = $operations[3];
        $this->assertInstanceOf(Patch::class, $patchOperation);
        $this->assertTrue($patchOperation->getParameters()->has('search'));

        $deleteOperation = $operations[4];
        $this->assertInstanceOf(Delete::class, $deleteOperation);
        $this->assertTrue($deleteOperation->getParameters()->has('search'));

        $searchParam = $collectionOperation->getParameters()->get('search', QueryParameter::class);
        $this->assertInstanceOf(QueryParameter::class, $searchParam);
        $this->assertSame('search', $searchParam->getKey());
        $this->assertSame('name', $searchParam->getProperty());
        $this->assertSame(['name'], $searchParam->getProperties());
    }

    public function testHeaderParameterFromPropertyAttributes(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'authToken', 'token']));

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

        $resourceMetadataCollection = $parameterFactory->create(HeaderParameterOnPropertiesTest::class);
        $operations = array_values(iterator_to_array($resourceMetadataCollection[0]->getOperations()));

        $this->assertCount(5, $operations);

        $getOperation = $operations[0];
        $this->assertInstanceOf(Get::class, $getOperation);
        $this->assertTrue($getOperation->getParameters()->has('X-Authorization', HeaderParameter::class));
        $this->assertTrue($getOperation->getParameters()->has('X-Token', HeaderParameter::class));

        $collectionOperation = $operations[1];
        $this->assertInstanceOf(GetCollection::class, $collectionOperation);
        $this->assertTrue($collectionOperation->getParameters()->has('X-Authorization', HeaderParameter::class));
        $this->assertTrue($collectionOperation->getParameters()->has('X-Token', HeaderParameter::class));

        $postOperation = $operations[2];
        $this->assertInstanceOf(Post::class, $postOperation);
        $this->assertTrue($postOperation->getParameters()->has('X-Authorization', HeaderParameter::class));
        $this->assertTrue($postOperation->getParameters()->has('X-Token', HeaderParameter::class));

        $patchOperation = $operations[3];
        $this->assertInstanceOf(Patch::class, $patchOperation);
        $this->assertTrue($patchOperation->getParameters()->has('X-Authorization', HeaderParameter::class));
        $this->assertTrue($patchOperation->getParameters()->has('X-Token', HeaderParameter::class));

        $deleteOperation = $operations[4];
        $this->assertInstanceOf(Delete::class, $deleteOperation);
        $this->assertTrue($deleteOperation->getParameters()->has('X-Authorization', HeaderParameter::class));
        $this->assertTrue($deleteOperation->getParameters()->has('X-Token', HeaderParameter::class));

        $authParam = $collectionOperation->getParameters()->get('X-Authorization', HeaderParameter::class);
        $this->assertInstanceOf(HeaderParameter::class, $authParam);
        $this->assertSame('X-Authorization', $authParam->getKey());
        $this->assertSame('authToken', $authParam->getProperty());
        $this->assertSame('Authorization header', $authParam->getDescription());

        $tokenParam = $collectionOperation->getParameters()->get('X-Token', HeaderParameter::class);
        $this->assertInstanceOf(HeaderParameter::class, $tokenParam);
        $this->assertSame('X-Token', $tokenParam->getKey());
        $this->assertSame('token', $tokenParam->getProperty());
        $this->assertSame('API Token header', $tokenParam->getDescription());
    }

    public function testHeaderParameterFromPropertyAttributeThrowsExceptionWhenPropertyMismatch(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parameter attribute on property "authToken" must target itself or have no explicit property. Got "property: \'token\'" instead.');

        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'authToken', 'token']));

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

        $parameterFactory->create(HeaderParameterOnPropertiesMismatchException::class);
    }

    public function testHeaderParameterFromPropertyAttributeThrowsExceptionWhenPropertiesMismatch(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parameter attribute on property "authToken" must target itself or have no explicit properties. Got "properties: [token]" instead.');

        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'authToken', 'token']));

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

        $parameterFactory->create(HeaderParameterOnPropertiesMismatchPropertiesException::class);
    }

    public function testHeaderParameterFromPropertyAttributeThrowsExceptionWhenPropertiesHasMultipleWithoutSelf(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parameter attribute on property "authToken" must target itself or have no explicit properties. Got "properties: [token, token2]" instead.');

        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'authToken', 'token', 'token2']));

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

        $parameterFactory->create(HeaderParameterOnPropertiesMismatchMultiplePropertiesException::class);
    }

    public function testHeaderParameterFromPropertyAttributePropertiesSingleCorrectProperty(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'authToken', 'token']));

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

        $resourceMetadataCollection = $parameterFactory->create(HeaderParameterOnPropertiesSingleCorrectProperty::class);
        $operations = array_values(iterator_to_array($resourceMetadataCollection[0]->getOperations()));

        $this->assertCount(5, $operations);

        $getOperation = $operations[0];
        $this->assertInstanceOf(Get::class, $getOperation);
        $this->assertTrue($getOperation->getParameters()->has('X-Authorization', HeaderParameter::class));

        $collectionOperation = $operations[1];
        $this->assertInstanceOf(GetCollection::class, $collectionOperation);
        $this->assertTrue($collectionOperation->getParameters()->has('X-Authorization', HeaderParameter::class));

        $postOperation = $operations[2];
        $this->assertInstanceOf(Post::class, $postOperation);
        $this->assertTrue($postOperation->getParameters()->has('X-Authorization', HeaderParameter::class));

        $patchOperation = $operations[3];
        $this->assertInstanceOf(Patch::class, $patchOperation);
        $this->assertTrue($patchOperation->getParameters()->has('X-Authorization', HeaderParameter::class));

        $deleteOperation = $operations[4];
        $this->assertInstanceOf(Delete::class, $deleteOperation);
        $this->assertTrue($deleteOperation->getParameters()->has('X-Authorization', HeaderParameter::class));

        $authParam = $collectionOperation->getParameters()->get('X-Authorization', HeaderParameter::class);
        $this->assertInstanceOf(HeaderParameter::class, $authParam);
        $this->assertSame('X-Authorization', $authParam->getKey());
        $this->assertSame('authToken', $authParam->getProperty());
        $this->assertSame(['authToken'], $authParam->getProperties());
    }

    public function testHeaderParameterFromPropertyAttributePropertiesHasMultipleIncludingSelf(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'authToken', 'token']));

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

        $resourceMetadataCollection = $parameterFactory->create(HeaderParameterOnPropertiesMultiplePropertiesIncludingSelf::class);
        $operations = array_values(iterator_to_array($resourceMetadataCollection[0]->getOperations()));

        $this->assertCount(5, $operations);

        $getOperation = $operations[0];
        $this->assertInstanceOf(Get::class, $getOperation);
        $this->assertTrue($getOperation->getParameters()->has('X-Authorization', HeaderParameter::class));

        $collectionOperation = $operations[1];
        $this->assertInstanceOf(GetCollection::class, $collectionOperation);
        $this->assertTrue($collectionOperation->getParameters()->has('X-Authorization', HeaderParameter::class));

        $postOperation = $operations[2];
        $this->assertInstanceOf(Post::class, $postOperation);
        $this->assertTrue($postOperation->getParameters()->has('X-Authorization', HeaderParameter::class));

        $patchOperation = $operations[3];
        $this->assertInstanceOf(Patch::class, $patchOperation);
        $this->assertTrue($patchOperation->getParameters()->has('X-Authorization', HeaderParameter::class));

        $deleteOperation = $operations[4];
        $this->assertInstanceOf(Delete::class, $deleteOperation);
        $this->assertTrue($deleteOperation->getParameters()->has('X-Authorization', HeaderParameter::class));

        $authParam = $collectionOperation->getParameters()->get('X-Authorization', HeaderParameter::class);
        $this->assertInstanceOf(HeaderParameter::class, $authParam);
        $this->assertSame('X-Authorization', $authParam->getKey());
        $this->assertSame('authToken', $authParam->getProperty());
        $this->assertSame(['authToken'], $authParam->getProperties());
    }

    public function testQueryParameterOnPropertiesWithOperations(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name']));

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

        $resourceMetadataCollection = $parameterFactory->create(QueryParameterOnPropertiesWithOperations::class);
        $operations = array_values(iterator_to_array($resourceMetadataCollection[0]->getOperations()));

        $this->assertCount(2, $operations);

        $collectionOperation = $operations[0];
        $this->assertInstanceOf(GetCollection::class, $collectionOperation);
        $collectionParameters = $collectionOperation->getParameters();
        $this->assertTrue($collectionParameters->has('search'));
        $this->assertFalse($collectionParameters->has('filter_id'));

        $getOperation = $operations[1];
        $this->assertInstanceOf(Get::class, $getOperation);
        $getParameters = $getOperation->getParameters();
        $this->assertTrue($getParameters->has('search'));
        $this->assertTrue($getParameters->has('filter_id'));
    }

    public function testHeaderParameterOnPropertiesWithOperations(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'authToken', 'apiKey']));

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

        $resourceMetadataCollection = $parameterFactory->create(HeaderParameterOnPropertiesWithOperations::class);
        $operations = array_values(iterator_to_array($resourceMetadataCollection[0]->getOperations()));

        $this->assertCount(2, $operations);

        $collectionOperation = $operations[0];
        $this->assertInstanceOf(GetCollection::class, $collectionOperation);
        $collectionParameters = $collectionOperation->getParameters();
        $this->assertFalse($collectionParameters->has('X-Authorization', HeaderParameter::class));
        $this->assertTrue($collectionParameters->has('X-API-Key', HeaderParameter::class));

        $getOperation = $operations[1];
        $this->assertInstanceOf(Get::class, $getOperation);
        $getParameters = $getOperation->getParameters();
        $this->assertTrue($getParameters->has('X-Authorization', HeaderParameter::class));
        $this->assertTrue($getParameters->has('X-API-Key', HeaderParameter::class));
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
        $this->assertArrayHasKey('nested_properties_info', $extra);

        $info = $extra['nested_properties_info']['product.name'];
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
        $this->assertArrayHasKey('nested_properties_info', $extra);

        $info = $extra['nested_properties_info']['product.productVariations.variantName'];
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
        $this->assertArrayHasKey('nested_properties_info', $extra);

        $info = $extra['nested_properties_info']['product.name'];
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
        $this->assertArrayNotHasKey('nested_properties_info', $param->getExtraProperties());
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
                'order[:property]' => new QueryParameter(
                    properties: ['name'],
                    priority: 10,
                ),
                'q' => new QueryParameter(
                    priority: 0,
                ),
            ]
        ),
    ]
)]
class HasPatternParameterWithPriority
{
    public $id;
    public $name;
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

#[ApiResource]
class ParameterOnProperties
{
    #[QueryParameter(key: 'search', description: 'Search by name')]
    public string $name = '';

    #[QueryParameter(key: 'filter_active', description: 'Filter by active status')]
    public bool $isActive = true;
}

#[ApiResource]
class ParameterOnPropertiesMismatchPropertyException
{
    #[QueryParameter(key: 'search', property: 'description')]
    public string $name = '';

    public string $description = '';
}

#[ApiResource]
class ParameterOnPropertiesMismatchPropertiesException
{
    #[QueryParameter(key: 'search', properties: ['description'])]
    public string $name = '';

    public string $description = '';
}

#[ApiResource]
class ParameterOnPropertiesMismatchMultiplePropertiesException
{
    #[QueryParameter(key: 'search', properties: ['description', 'active'])]
    public string $name = '';

    public string $description = '';

    public bool $active = true;
}

#[ApiResource]
class ParameterOnPropertiesSingleCorrectProperty
{
    #[QueryParameter(key: 'search', properties: ['name'])]
    public string $name = '';

    public string $description = '';
}

#[ApiResource]
class ParameterOnPropertiesMultiplePropertiesIncludingSelf
{
    #[QueryParameter(key: 'search', properties: ['description', 'name'])]
    public string $name = '';

    public string $description = '';
}

#[ApiResource]
class HeaderParameterOnPropertiesTest
{
    #[HeaderParameter(key: 'X-Authorization', description: 'Authorization header')]
    public string $authToken = '';

    #[HeaderParameter(key: 'X-Token', description: 'API Token header')]
    public string $token = '';
}

#[ApiResource]
class HeaderParameterOnPropertiesMismatchException
{
    #[HeaderParameter(key: 'X-Authorization', property: 'token')]
    public string $authToken = '';

    public string $token = '';
}

#[ApiResource]
class HeaderParameterOnPropertiesSingleCorrectProperty
{
    #[HeaderParameter(key: 'X-Authorization', properties: ['authToken'])]
    public string $authToken = '';

    public string $token = '';
}

#[ApiResource]
class HeaderParameterOnPropertiesMultiplePropertiesIncludingSelf
{
    #[HeaderParameter(key: 'X-Authorization', properties: ['token', 'authToken'])]
    public string $authToken = '';

    public string $token = '';
}

#[ApiResource]
class HeaderParameterOnPropertiesMismatchPropertiesException
{
    #[HeaderParameter(key: 'X-Authorization', properties: ['token'])]
    public string $authToken = '';

    public string $token = '';
}

#[ApiResource]
class HeaderParameterOnPropertiesMismatchMultiplePropertiesException
{
    #[HeaderParameter(key: 'X-Authorization', properties: ['token', 'token2'])]
    public string $authToken = '';

    public string $token = '';

    public string $token2 = '';
}

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ]
)]
class QueryParameterOnPropertiesWithOperations
{
    #[QueryParameter(key: 'search', description: 'Search by name', operations: [new GetCollection(), new Get()])]
    public string $name = '';

    #[QueryParameter(key: 'filter_id', description: 'Filter by ID', operations: [new Get(), new Patch()])]
    public int $id = 0;
}

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ]
)]
class HeaderParameterOnPropertiesWithOperations
{
    #[HeaderParameter(key: 'X-Authorization', description: 'Authorization header', operations: [new Get()])]
    public string $authToken = '';

    #[HeaderParameter(key: 'X-API-Key', description: 'API key header', operations: [new GetCollection(), new Get()])]
    public string $apiKey = '';
}
