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

use ApiPlatform\Elasticsearch\Metadata\Get as ElasticsearchGet;
use ApiPlatform\Elasticsearch\Metadata\GetCollection as ElasticsearchGetCollection;
use ApiPlatform\Elasticsearch\Metadata\Operation as ElasticsearchOperation;
use ApiPlatform\Elasticsearch\Metadata\Resource\Factory\ElasticsearchOperationProviderResourceMetadataCollectionFactory;
use ApiPlatform\Elasticsearch\State\ElasticsearchCollectionProvider;
use ApiPlatform\Elasticsearch\State\ElasticsearchItemProvider;
use ApiPlatform\Metadata\ApiResource;
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
    public function testElasticsearchOperationWithElasticsearchEqualsToFalse(): void
    {
        $get = new ElasticsearchGet(index: 'foo_index', shortName: 'Foo', elasticsearch: false);

        $resourceMetadataFactory = new ElasticsearchOperationProviderResourceMetadataCollectionFactory($this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]));
        self::expectException(\LogicException::class);
        self::expectExceptionMessage(sprintf('You cannot disable elasticsearch with %s, use %s instead', ElasticsearchOperation::class, Operation::class));
        $resourceMetadataFactory->create(Foo::class);
    }

    public function testElasticsearchGetWithIndexAndType(): void
    {
        $get = new ElasticsearchGet(index: 'foo_index', type: 'foo_type');

        $resourceMetadataFactory = new ElasticsearchOperationProviderResourceMetadataCollectionFactory($this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]));
        /** @var ElasticsearchGet $operationResult */
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertSame(ElasticsearchItemProvider::class, $operationResult->getProvider());
        self::assertSame('foo_index', $operationResult->getIndex());
        self::assertSame('foo_type', $operationResult->getType());
    }

    public function testElasticsearchGetCollectionWithIndexAndType(): void
    {
        $get = new ElasticsearchGetCollection(index: 'foo_index', type: 'foo_type');

        $resourceMetadataFactory = new ElasticsearchOperationProviderResourceMetadataCollectionFactory($this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]));
        /** @var ElasticsearchGet $operationResult */
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertSame(ElasticsearchCollectionProvider::class, $operationResult->getProvider());
        self::assertSame('foo_index', $operationResult->getIndex());
        self::assertSame('foo_type', $operationResult->getType());
    }

    /**
     * @param Operation[] $operations
     *
     * @return Stub&ResourceMetadataCollectionFactoryInterface
     */
    private function getResourceMetadataCollectionFactory(string $resourceClass, array $operations): Stub
    {
        $resource = new ApiResource(operations: $operations);
        $metadata = new ResourceMetadataCollection($resourceClass, [$resource]);
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn($metadata);

        return $decorated;
    }
}
