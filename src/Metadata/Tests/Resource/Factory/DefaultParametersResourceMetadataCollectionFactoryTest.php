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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\DefaultParametersResourceMetadataCollectionFactory;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for DefaultParametersResourceMetadataCollectionFactory with real resources.
 *
 * @author Maxence Castel <maxence.castel59@gmail.com>
 */
final class DefaultParametersResourceMetadataCollectionFactoryTest extends TestCase
{
    private const DEFAULT_PARAMETERS = [
        HeaderParameter::class => [
            'key' => 'X-API-Version',
            'required' => true,
            'description' => 'API Version',
        ],
    ];

    public function testDefaultParametersAppliedToRealResource(): void
    {
        $attributesFactory = new AttributesResourceMetadataCollectionFactory();
        $defaultParametersFactory = new DefaultParametersResourceMetadataCollectionFactory(self::DEFAULT_PARAMETERS, $attributesFactory);

        $resourceClass = TestProductResource::class;

        $collection = $defaultParametersFactory->create($resourceClass);

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
        $this->assertTrue($parameters->has('X-API-Version', HeaderParameter::class), 'Default header parameter not found');

        $headerParam = $parameters->get('X-API-Version', HeaderParameter::class);
        $this->assertSame('X-API-Version', $headerParam->getKey());
        $this->assertTrue($headerParam->getRequired());
        $this->assertSame('API Version', $headerParam->getDescription());
    }

    public function testDefaultParametersWithOperationOverride(): void
    {
        $attributesFactory = new AttributesResourceMetadataCollectionFactory();
        $defaultParametersFactory = new DefaultParametersResourceMetadataCollectionFactory(self::DEFAULT_PARAMETERS, $attributesFactory);

        $resourceClass = TestProductResourceWithParameters::class;

        $collection = $defaultParametersFactory->create($resourceClass);

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

        $this->assertTrue($parameters->has('X-API-Version', HeaderParameter::class));
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
