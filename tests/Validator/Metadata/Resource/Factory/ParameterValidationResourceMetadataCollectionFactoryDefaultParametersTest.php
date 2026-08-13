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

    private const REPEATED_DEFAULT_PARAMETERS = [
        'api_token' => [
            'class' => HeaderParameter::class,
            'key' => 'API-Token',
            'required' => true,
            'description' => 'API token',
        ],
        'request_id' => [
            'class' => HeaderParameter::class,
            'key' => 'Request-ID',
            'required' => false,
            'description' => 'Request correlation identifier',
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

    public function testNamedDefaultParametersWithSameClassAreApplied(): void
    {
        $attributesFactory = new AttributesResourceMetadataCollectionFactory();
        $parameterValidationFactory = new ParameterValidationResourceMetadataCollectionFactory(
            $attributesFactory,
            null,
            self::REPEATED_DEFAULT_PARAMETERS
        );

        $collection = $parameterValidationFactory->create(TestProductResource::class);
        $operation = $collection[0]->getOperations()?->getIterator()->current();
        $parameters = $operation?->getParameters();

        $this->assertNotNull($parameters);
        $this->assertCount(2, $parameters);
        $this->assertTrue($parameters->has('API-Token', HeaderParameter::class));
        $this->assertTrue($parameters->has('Request-ID', HeaderParameter::class));
        $this->assertSame('API token', $parameters->get('API-Token', HeaderParameter::class)?->getDescription());
        $this->assertSame('Request correlation identifier', $parameters->get('Request-ID', HeaderParameter::class)?->getDescription());
        $this->assertTrue($parameters->get('API-Token', HeaderParameter::class)?->getRequired());
        $this->assertFalse($parameters->get('Request-ID', HeaderParameter::class)?->getRequired());
    }

    public function testHistoricalAndNamedDefaultParametersAreAppliedTogether(): void
    {
        $attributesFactory = new AttributesResourceMetadataCollectionFactory();
        $parameterValidationFactory = new ParameterValidationResourceMetadataCollectionFactory(
            $attributesFactory,
            null,
            [
                QueryParameter::class => [
                    'key' => 'page_size',
                ],
                ...self::REPEATED_DEFAULT_PARAMETERS,
            ]
        );

        $collection = $parameterValidationFactory->create(TestProductResource::class);
        $operation = $collection[0]->getOperations()?->getIterator()->current();
        $parameters = $operation?->getParameters();

        $this->assertNotNull($parameters);
        $this->assertCount(3, $parameters);
        $this->assertTrue($parameters->has('page_size', QueryParameter::class));
        $this->assertTrue($parameters->has('API-Token', HeaderParameter::class));
        $this->assertTrue($parameters->has('Request-ID', HeaderParameter::class));
    }

    public function testOperationParameterOverridesOnlyMatchingNamedDefaultParameter(): void
    {
        $attributesFactory = new AttributesResourceMetadataCollectionFactory();
        $parameterValidationFactory = new ParameterValidationResourceMetadataCollectionFactory(
            $attributesFactory,
            null,
            self::REPEATED_DEFAULT_PARAMETERS
        );

        $collection = $parameterValidationFactory->create(TestProductResourceWithRepeatedHeaders::class);
        $operation = $collection[0]->getOperations()?->getIterator()->current();
        $parameters = $operation?->getParameters();

        $this->assertNotNull($parameters);
        $this->assertCount(2, $parameters);
        $this->assertSame('Local API token', $parameters->get('API-Token', HeaderParameter::class)?->getDescription());
        $this->assertSame('Request correlation identifier', $parameters->get('Request-ID', HeaderParameter::class)?->getDescription());
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

#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'API-Token' => new HeaderParameter(key: 'API-Token', description: 'Local API token'),
            ]
        ),
    ]
)]
class TestProductResourceWithRepeatedHeaders
{
    public int $id = 1;
    public string $name = 'Test Product';
}
