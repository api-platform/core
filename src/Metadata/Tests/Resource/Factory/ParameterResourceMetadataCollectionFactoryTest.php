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
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ParameterResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\WithParameter;
use ApiPlatform\OpenApi\Model\Parameter;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ParameterResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testParameterFactory(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'hydra', 'everywhere']));
        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturnOnConsecutiveCalls(new ApiProperty(identifier: true), new ApiProperty(readable: true), new ApiProperty(readable: true));
        $filterLocator = $this->createStub(ContainerInterface::class);
        $filterLocator->method('has')->willReturn(true);
        $filterLocator->method('get')->willReturn(new class implements FilterInterface {
            public function getDescription(string $resourceClass): array
            {
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
                        'openapi' => ['allowEmptyValue' => true]],
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

    public function testParameterFactoryNoFilter(): void
    {
        $nameCollection = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $nameCollection->method('create')->willReturn(new PropertyNameCollection(['id', 'hydra', 'everywhere']));
        $propertyMetadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadata->method('create')->willReturnOnConsecutiveCalls(new ApiProperty(identifier: true), new ApiProperty(readable: true), new ApiProperty(readable: true));
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
}
