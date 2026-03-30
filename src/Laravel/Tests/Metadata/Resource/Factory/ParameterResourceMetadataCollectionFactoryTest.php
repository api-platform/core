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

namespace ApiPlatform\Laravel\Tests\Metadata\Resource\Factory;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Laravel\Metadata\Resource\Factory\ParameterResourceMetadataCollectionFactory as LaravelParameterResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ParameterResourceMetadataCollectionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class ParameterResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testNestedPropertyWithEloquentRelationship(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'name']));

        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturnCallback(
            static function (string $class, string $property): ApiProperty {
                return new ApiProperty(readable: true);
            }
        );

        $filterLocator = $this->createStub(ContainerInterface::class);
        $filterLocator->method('has')->willReturn(false);

        $coreFactory = new ParameterResourceMetadataCollectionFactory(
            $nameCollection,
            $propertyMetadata,
            new AttributesResourceMetadataCollectionFactory(),
            $filterLocator
        );

        $parameterFactory = new LaravelParameterResourceMetadataCollectionFactory($coreFactory, new ModelMetadata(), new CamelCaseToSnakeCaseNameConverter());

        $resourceMetadataCollection = $parameterFactory->create(TestProductOrderResource::class);
        $operation = $resourceMetadataCollection->getOperation(forceCollection: true);
        $parameters = $operation->getParameters();

        $this->assertNotNull($parameters);
        $this->assertTrue($parameters->has('search[name]'));
        $this->assertTrue($parameters->has('search[product.name]'));

        $searchNameParam = $parameters->get('search[name]');
        $this->assertSame('name', $searchNameParam->getProperty());

        $searchProductNameParam = $parameters->get('search[product.name]');
        $this->assertSame('product.name', $searchProductNameParam->getProperty());

        $this->assertNotNull($searchProductNameParam);
    }
}

// Test fixtures

#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'search[:property]' => new QueryParameter(
                    properties: ['name', 'product.name']
                ),
            ]
        ),
    ]
)]
class TestProductOrderResource
{
    public ?int $id = null;
    public ?string $name = null;
    public ?object $product = null;
}
