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

namespace ApiPlatform\Elasticsearch\Tests\Metadata\Resource\Factory;

use ApiPlatform\Elasticsearch\Esql\State\CollectionProvider as EsqlCollectionProvider;
use ApiPlatform\Elasticsearch\Metadata\Resource\Factory\ElasticsearchProviderResourceMetadataCollectionFactory;
use ApiPlatform\Elasticsearch\State\CollectionProvider;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Elasticsearch\State\QueryLanguage;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ElasticsearchProviderResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            ResourceMetadataCollectionFactoryInterface::class,
            new ElasticsearchProviderResourceMetadataCollectionFactory(
                $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal()
            )
        );
    }

    public function testDslProvidersAreAssignedByDefault(): void
    {
        $resourceMetadataCollection = $this->createFactory()->create(Foo::class);
        $operations = iterator_to_array($resourceMetadataCollection[0]->getOperations());

        self::assertSame(CollectionProvider::class, $operations['get_collection']->getProvider());
        self::assertSame(ItemProvider::class, $operations['get']->getProvider());
    }

    public function testEsqlCollectionProviderIsAssignedFromStateOptions(): void
    {
        $resourceMetadataCollection = $this->createFactory(new Options(queryLanguage: QueryLanguage::Esql))->create(Foo::class);
        $operations = iterator_to_array($resourceMetadataCollection[0]->getOperations());

        self::assertSame(EsqlCollectionProvider::class, $operations['get_collection']->getProvider());
        // items are always fetched through the document GET API
        self::assertSame(ItemProvider::class, $operations['get']->getProvider());
    }

    public function testEsqlCollectionProviderIsAssignedFromGlobalDefault(): void
    {
        $resourceMetadataCollection = $this->createFactory(defaultQueryLanguage: 'esql')->create(Foo::class);
        $operations = iterator_to_array($resourceMetadataCollection[0]->getOperations());

        self::assertSame(EsqlCollectionProvider::class, $operations['get_collection']->getProvider());
        self::assertSame(ItemProvider::class, $operations['get']->getProvider());
    }

    public function testStateOptionsOverrideGlobalDefault(): void
    {
        $resourceMetadataCollection = $this->createFactory(new Options(queryLanguage: QueryLanguage::Dsl), 'esql')->create(Foo::class);
        $operations = iterator_to_array($resourceMetadataCollection[0]->getOperations());

        self::assertSame(CollectionProvider::class, $operations['get_collection']->getProvider());
    }

    public function testEsqlStateOptionsThrowWhenEsqlIsNotAvailable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ES|QL is not supported by the OpenSearch client');

        $this->createFactory(new Options(queryLanguage: QueryLanguage::Esql), esqlAvailable: false)->create(Foo::class);
    }

    public function testEsqlGlobalDefaultThrowsWhenEsqlIsNotAvailable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ES|QL is not supported by the OpenSearch client');

        $this->createFactory(defaultQueryLanguage: 'esql', esqlAvailable: false)->create(Foo::class);
    }

    public function testGraphQlOperationsAlwaysUseTheDslProviders(): void
    {
        // the ES|QL paginator implements partial pagination only, which is incompatible with GraphQL connections
        $resourceMetadataCollection = $this->createFactory(new Options(queryLanguage: QueryLanguage::Esql), 'esql')->create(Foo::class);
        $graphQlOperations = $resourceMetadataCollection[0]->getGraphQlOperations();

        self::assertSame(CollectionProvider::class, $graphQlOperations['collection_query']->getProvider());
        self::assertSame(ItemProvider::class, $graphQlOperations['item_query']->getProvider());
    }

    private function createFactory(?Options $options = null, QueryLanguage|string $defaultQueryLanguage = QueryLanguage::Dsl, bool $esqlAvailable = true): ElasticsearchProviderResourceMetadataCollectionFactory
    {
        $options ??= new Options();

        $resource = (new ApiResource(shortName: 'Foo'))
            ->withOperations(new Operations([
                'get_collection' => (new GetCollection())->withStateOptions($options),
                'get' => (new Get())->withStateOptions($options),
            ]))
            ->withGraphQlOperations([
                'collection_query' => (new QueryCollection())->withStateOptions($options),
                'item_query' => (new Query())->withStateOptions($options),
            ]);

        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceMetadataCollection(Foo::class, [$resource]));

        return new ElasticsearchProviderResourceMetadataCollectionFactory($decorated, $defaultQueryLanguage, $esqlAvailable);
    }
}
