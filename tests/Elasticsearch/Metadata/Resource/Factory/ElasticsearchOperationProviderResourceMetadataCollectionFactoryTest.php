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

namespace ApiPlatform\Tests\Elasticsearch\Metadata\Resource\Factory;

use ApiPlatform\Elasticsearch\Metadata\ElasticsearchDocument;
use ApiPlatform\Elasticsearch\Metadata\Resource\Factory\ElasticsearchOperationProviderResourceMetadataCollectionFactory;
use ApiPlatform\Elasticsearch\State\ElasticsearchItemProvider;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class ElasticsearchOperationProviderResourceMetadataCollectionFactoryTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testWithElasticsearchEqualsToFalse(): void
    {
        $get = new Get(elasticsearch: false, name: 'foo_get', persistenceMeans: new ElasticsearchDocument());
        $resourceMetadataFactory = new ElasticsearchOperationProviderResourceMetadataCollectionFactory($this->getResourceMetadataCollectionFactory(Foo::class, $get));
        self::expectException(\LogicException::class);
        $resourceMetadataFactory->create(Foo::class);
    }

    public function testWithElasticsearchDocument(): void
    {
        $get = new Get(name: 'foo_get', persistenceMeans: new ElasticsearchDocument());

        $resourceMetadataFactory = new ElasticsearchOperationProviderResourceMetadataCollectionFactory($this->getResourceMetadataCollectionFactory(Foo::class, $get));
        /** @var Get $operationResult */
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertSame(ElasticsearchItemProvider::class, $operationResult->getProvider());
    }

    public function testWithoutElasticsearchDocument(): void
    {
        $get = new Get(name: 'foo_get');

        $resourceMetadataFactory = new ElasticsearchOperationProviderResourceMetadataCollectionFactory($this->getResourceMetadataCollectionFactory(Foo::class, $get));
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertNull($operationResult->getProvider());
    }

    public function testExistingProviderIsNotReplaced(): void
    {
        $get = new Get(name: 'foo_get', provider: 'foo', persistenceMeans: new ElasticsearchDocument());

        $resourceMetadataFactory = new ElasticsearchOperationProviderResourceMetadataCollectionFactory($this->getResourceMetadataCollectionFactory(Foo::class, $get));
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertSame('foo', $operationResult->getProvider());
    }

    public function testWithGraphqlOperation(): void
    {
        $query = new Query(name: 'foo_query', persistenceMeans: new ElasticsearchDocument());

        $resourceMetadataFactory = new ElasticsearchOperationProviderResourceMetadataCollectionFactory($this->getResourceMetadataCollectionFactory(Foo::class, $query));
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation($query->getName());
        self::assertSame(ElasticsearchItemProvider::class, $operationResult->getProvider());
    }

    /**
     * @return Stub&ResourceMetadataCollectionFactoryInterface
     */
    private function getResourceMetadataCollectionFactory(string $resourceClass, Operation $operation): Stub
    {
        if ($operation instanceof GraphQlOperation) {
            $resource = new ApiResource(graphQlOperations: [$operation->getName() => $operation]);
        } else {
            $resource = new ApiResource(operations: [$operation->getName() => $operation]);
        }
        $metadata = new ResourceMetadataCollection($resourceClass, [$resource]);
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn($metadata);

        return $decorated;
    }
}
