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

namespace ApiPlatform\Tests\Validator\Metadata\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Validator\Metadata\Resource\Factory\ParameterValidationResourceMetadataCollectionFactory;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for ParameterValidationResourceMetadataCollectionFactory with default parameters.
 *
 * @author Maxence Castel <maxence.castel59@gmail.com>
 */
final class ParameterValidationResourceMetadataCollectionFactoryDefaultParametersTest extends TestCase
{
    private const DEFAULT_PARAMETERS = [
        HeaderParameter::class => [
            'key' => 'API-Version',
            'required' => true,
            'description' => 'API Version',
        ],
    ];

    public function testDefaultParametersAppliedToRealResource(): void
    {
        $attributesFactory = new AttributesResourceMetadataCollectionFactory();
        $parameterValidationFactory = new ParameterValidationResourceMetadataCollectionFactory(
            $attributesFactory,
            null,
            self::DEFAULT_PARAMETERS
        );

        $resourceClass = TestProductResource::class;

        $collection = $parameterValidationFactory->create($resourceClass);

        $this->assertCount(1, $collection);
        $resource = $collection[0];
        $operations = $resource->getOperations();
        $this->assertNotNull($operations);

        $collectionOperation = null;
        foreach ($operations as $operation) {
            if ($operation instanceof GetCollection) {
                $collectionOperation = $operation;
                break;
            }
        }

        $this->assertNotNull($collectionOperation, 'GetCollection operation not found');

        $parameters = $collectionOperation->getParameters();
        $this->assertNotNull($parameters);
        $this->assertTrue($parameters->has('API-Version', HeaderParameter::class), 'Default header parameter not found');

        $headerParam = $parameters->get('API-Version', HeaderParameter::class);
        $this->assertSame('API-Version', $headerParam->getKey());
        $this->assertTrue($headerParam->getRequired());
        $this->assertSame('API Version', $headerParam->getDescription());
    }

    public function testDefaultParametersWithOperationOverride(): void
    {
        $attributesFactory = new AttributesResourceMetadataCollectionFactory();
        $parameterValidationFactory = new ParameterValidationResourceMetadataCollectionFactory(
            $attributesFactory,
            null,
            self::DEFAULT_PARAMETERS
        );

        $resourceClass = TestProductResourceWithParameters::class;

        $collection = $parameterValidationFactory->create($resourceClass);

        $this->assertCount(1, $collection);
        $resource = $collection[0];
        $operations = $resource->getOperations();
        $this->assertNotNull($operations);

        $collectionOperation = null;
        foreach ($operations as $operation) {
            if ($operation instanceof GetCollection) {
                $collectionOperation = $operation;
                break;
            }
        }

        $this->assertNotNull($collectionOperation);

        $parameters = $collectionOperation->getParameters();
        $this->assertNotNull($parameters);

        $this->assertTrue($parameters->has('API-Version', HeaderParameter::class));
        $this->assertTrue($parameters->has('filter', QueryParameter::class));
    }
}

#[ApiResource(operations: [new GetCollection()])]
class TestProductResource
{
    public int $id = 1;
    public string $name = 'Test Product';
}

#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'filter' => new QueryParameter(key: 'filter', description: 'Filter by name'),
            ]
        ),
    ]
)]
class TestProductResourceWithParameters
{
    public int $id = 1;
    public string $name = 'Test Product';
}
