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

use ApiPlatform\Doctrine\Odm\State\ItemProvider as DoctrineItemProvider;
use ApiPlatform\Elasticsearch\Metadata\Get as ElasticsearchGet;
use ApiPlatform\Elasticsearch\Metadata\Operation as ElasticsearchOperation;
use ApiPlatform\Elasticsearch\Metadata\Resource\Factory\ElasticsearchProviderResourceMetadataCollectionFactory;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Util\Inflector;
use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ElasticsearchProviderResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testElasticsearchOperationWithElasticsearchEqualsToFalse(): void
    {
        $get = new ElasticsearchGet(shortName: 'Foo', elasticsearch: false);

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($this->getClient(), $this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]), []);
        self::expectException(\LogicException::class);
        self::expectExceptionMessage(sprintf('You cannot disable elasticsearch with %s, use %s instead', ElasticsearchOperation::class, Operation::class));
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
    }

    public function testElasticsearchOperationWithIndexEqualsToNull(): void
    {
        $get = new ElasticsearchGet(shortName: 'Foo', elasticsearch: null);

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($this->getClient(), $this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]), []);
        /** @var ElasticsearchGet $operationResult */
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertSame(ItemProvider::class, $operationResult->getProvider());
        self::assertSame(self::guessIndexName('Foo'), $operationResult->getIndex());
    }

    public function testOperationWithElasticsearchEqualsToFalse(): void
    {
        $get = new Get(shortName: 'Foo', elasticsearch: false);

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($this->getClient(), $this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]), []);
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertSame($get, $operationResult);
    }

    public function testOperationWhichAlreadyHasAProvider(): void
    {
        $get = new Get(shortName: 'Foo', elasticsearch: null, provider: DoctrineItemProvider::class);

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($this->getClient(), $this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]), []);
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertSame($get, $operationResult);
    }

    public function testOperationWithIndexWhichDoesNotExist(): void
    {
        $get = new Get(shortName: 'Foo', elasticsearch: null);

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($this->getClient(false), $this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]), []);
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertSame($get, $operationResult);
    }

    public function testOperationWithElasticsearchEqualsToTrueAndIndexWhichDoesNotExist(): void
    {
        $get = new Get(shortName: 'Foo', elasticsearch: true);

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($this->getClient(false), $this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]), []);
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('No index exists with the name "foo".');
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
    }

    public function testOperationWithExistedIndexAndWithMapping(): void
    {
        $get = new Get(shortName: 'Foo');
        $mapping = [Foo::class => ['index' => 'foo_mapping_index', 'type' => 'foo_mapping_type']];

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($this->getClient(), $this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]), $mapping);
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertInstanceOf(ElasticsearchOperation::class, $operationResult);
        self::assertSame('foo_mapping_index', $operationResult->getIndex());
        self::assertSame('foo_mapping_type', $operationResult->getType());
        self::assertSame(ItemProvider::class, $operationResult->getProvider());
    }

    public function testAttributesIndexAndTypeAreUsedIfNoMappingConfigured(): void
    {
        $get = new Get(shortName: 'Foo', extraProperties: ['elasticsearch_index' => 'foo_elasticsearch_index', 'elasticsearch_type' => 'foo_elasticsearch_type']);

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($this->getClient(), $this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]), []);
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertInstanceOf(ElasticsearchOperation::class, $operationResult);
        self::assertSame('foo_elasticsearch_index', $operationResult->getIndex());
        self::assertSame('foo_elasticsearch_type', $operationResult->getType());
        self::assertSame(ItemProvider::class, $operationResult->getProvider());
    }

    public function testResourceClassIsUsedForIndexIfMappingAndAttributesAreNotConfigured(): void
    {
        $get = new Get(shortName: 'Foo');

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($this->getClient(), $this->getResourceMetadataCollectionFactory(Foo::class, ['foo_get' => $get]), []);
        $operationResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get');
        self::assertInstanceOf(ElasticsearchOperation::class, $operationResult);
        self::assertSame('foo', $operationResult->getIndex());
        self::assertNull($operationResult->getType());
        self::assertSame(ItemProvider::class, $operationResult->getProvider());
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

    /**
     * @return Stub&Client
     */
    private function getClient(bool $willIndexExist = true): Stub
    {
        $client = $this->createStub(Client::class);
        $indicesNamespace = $this->createStub(IndicesNamespace::class);
        $indicesNamespace->method('exists')->willReturn($willIndexExist);
        $client->method('indices')->willReturn($indicesNamespace);

        return $client;
    }

    private static function guessIndexName(string $shortName): string
    {
        return Inflector::tableize($shortName);
    }
}
