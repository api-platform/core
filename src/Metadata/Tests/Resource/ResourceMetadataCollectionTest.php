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

namespace ApiPlatform\Metadata\Tests\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

final class ResourceMetadataCollectionTest extends TestCase
{
    use ProphecyTrait;

    public function testGetOperation(): void
    {
        $operation = new Get();
        $query = new Query();
        $resource = (new ApiResource())->withUriTemplate('/dummies/{id}')->withOperations(new Operations(['name' => $operation]))->withGraphQlOperations(['query' => $query]);
        $resourceMetadataCollection = new ResourceMetadataCollection('class', [$resource]);

        $this->assertSame($operation, $resourceMetadataCollection->getOperation('name'));
        $this->assertSame($query, $resourceMetadataCollection->getOperation('query'));
    }

    public function testOperationNotFound(): void
    {
        $this->expectException(OperationNotFoundException::class);
        $operation = new Get();
        $resource = (new ApiResource())->withUriTemplate('/dummies/{id}')->withOperations(new Operations(['name' => $operation]));
        $resourceMetadataCollection = new ResourceMetadataCollection('class', [$resource]);

        $this->assertSame($operation, $resourceMetadataCollection->getOperation('noname'));
    }

    public function testCache(): void
    {
        $defaultOperation = new Get(name: 'get');
        $defaultGqlOperation = new Query();
        $defaultCollectionOperation = new GetCollection(name: 'get_collection');
        $defaultGqlCollectionOperation = new QueryCollection();
        $resource = new ApiResource(
            operations: [
                'get' => $defaultOperation,
                'get_collection' => $defaultCollectionOperation,
            ],
            graphQlOperations: [
                'query' => $defaultGqlOperation,
                'query_collection' => $defaultGqlCollectionOperation,
            ]
        );

        $resourceMetadataCollection = new ResourceMetadataCollection('class', [$resource]);

        $this->assertEquals($resourceMetadataCollection->getOperation(), $defaultOperation);
        $this->assertEquals($resourceMetadataCollection->getOperation(null, true), $defaultCollectionOperation);
        $this->assertEquals($resourceMetadataCollection->getOperation(null, false, true), $defaultOperation);

        $resource = new ApiResource(
            graphQlOperations: [
                'query' => $defaultGqlOperation,
                'query_collection' => $defaultGqlCollectionOperation,
            ]
        );

        $resourceMetadataCollection = new ResourceMetadataCollection('class', [$resource]);

        $this->assertEquals($resourceMetadataCollection->getOperation(), $defaultGqlOperation);
        $this->assertEquals($resourceMetadataCollection->getOperation(null, true), $defaultGqlCollectionOperation);

        try {
            $resourceMetadataCollection->getOperation(null, false, true);
        } catch (\Exception $e) {
            $this->assertInstanceOf(OperationNotFoundException::class, $e);
        }
    }
}
