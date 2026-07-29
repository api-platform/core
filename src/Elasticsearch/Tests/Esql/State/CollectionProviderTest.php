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

namespace ApiPlatform\Elasticsearch\Tests\Esql\State;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Elasticsearch\Esql\Extension\CollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Esql\Extension\ResultCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Esql\State\CollectionProvider;
use ApiPlatform\Elasticsearch\Esql\State\LinksHandlerInterface;
use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Elasticsearch\State\QueryLanguage;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use Elastic\Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CollectionProviderTest extends TestCase
{
    public function testExtensionsAreAppliedAndResultExtensionShortCircuits(): void
    {
        $applied = new \ArrayObject();

        $regularExtension = new class($applied) implements CollectionExtensionInterface {
            public function __construct(private readonly \ArrayObject $applied)
            {
            }

            public function applyToCollection(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
            {
                $this->applied->append('regular');
                $query->andWhere('a == 1');
            }
        };

        $resultExtension = new class($applied) implements ResultCollectionExtensionInterface {
            public ?string $compiledQuery = null;

            public function __construct(private readonly \ArrayObject $applied)
            {
            }

            public function applyToCollection(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
            {
                $this->applied->append('result');
            }

            public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool
            {
                return true;
            }

            public function getResult(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): iterable
            {
                $this->compiledQuery = $query->compile()['query'];

                return [new Foo()];
            }
        };

        $provider = $this->createProvider([$regularExtension, $resultExtension]);

        $operation = (new GetCollection(name: 'get_collection', shortName: 'Foo'))
            ->withClass(Foo::class)
            ->withStateOptions(new Options(index: 'custom_index', queryLanguage: QueryLanguage::Esql));

        $result = $provider->provide($operation);

        self::assertSame(['regular', 'result'], $applied->getArrayCopy());
        self::assertCount(1, $result);
        self::assertSame('FROM custom_index METADATA _id | WHERE a == 1', $resultExtension->compiledQuery);
    }

    public function testIndexDefaultsToTableizedShortName(): void
    {
        $compiled = new \ArrayObject();
        $provider = $this->createProvider([$this->createResultExtension($compiled)]);

        $operation = (new GetCollection(name: 'get_collection', shortName: 'FooBar'))->withClass(Foo::class);
        $provider->provide($operation);

        self::assertSame('FROM foo_bar METADATA _id', $compiled['query']);
    }

    public function testHandleLinksCallableIsInvoked(): void
    {
        $compiled = new \ArrayObject();

        $handleLinks = static function (EsqlQuery $query, array $uriVariables, array $context): void {
            $query->andWhere(\sprintf('user_id == %s', $query->param($uriVariables['userId'])));
        };

        $provider = $this->createProvider([$this->createResultExtension($compiled)]);

        $operation = (new GetCollection(name: 'get_collection', shortName: 'Foo'))
            ->withClass(Foo::class)
            ->withStateOptions(new Options(index: 'foo', queryLanguage: QueryLanguage::Esql, handleLinks: $handleLinks));

        $provider->provide($operation, ['userId' => 42]);

        self::assertSame('FROM foo METADATA _id | WHERE user_id == ?p1', $compiled['query']);
    }

    public function testHandleLinksInterfaceIsInvoked(): void
    {
        $compiled = new \ArrayObject();

        $handleLinks = new class implements LinksHandlerInterface {
            public function handleLinks(EsqlQuery $query, array $uriVariables, array $context): void
            {
                $query->andWhere(\sprintf('user_id == %s', $query->param($uriVariables['userId'])));
            }
        };

        $provider = $this->createProvider([$this->createResultExtension($compiled)]);

        $operation = (new GetCollection(name: 'get_collection', shortName: 'Foo'))
            ->withClass(Foo::class)
            ->withStateOptions(new Options(index: 'foo', queryLanguage: QueryLanguage::Esql, handleLinks: $handleLinks));

        $provider->provide($operation, ['userId' => 42]);

        self::assertSame('FROM foo METADATA _id | WHERE user_id == ?p1', $compiled['query']);
    }

    public function testHandleLinksWithAnUnsupportedValue(): void
    {
        $provider = $this->createProvider([$this->createResultExtension(new \ArrayObject())]);

        $operation = (new GetCollection(name: 'get_collection', shortName: 'Foo'))
            ->withClass(Foo::class)
            ->withStateOptions(new Options(index: 'foo', queryLanguage: QueryLanguage::Esql, handleLinks: 'app.links_handler'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not resolve the handleLinks option of the operation "get_collection"');

        $provider->provide($operation, ['userId' => 42]);
    }

    /**
     * @param iterable<CollectionExtensionInterface> $collectionExtensions
     */
    private function createProvider(iterable $collectionExtensions): CollectionProvider
    {
        return new CollectionProvider(
            ClientBuilder::create()->build(),
            $this->createStub(DenormalizerInterface::class),
            $collectionExtensions,
        );
    }

    /**
     * @param \ArrayObject<string, string> $compiled receives the compiled query under the "query" key
     */
    private function createResultExtension(\ArrayObject $compiled): ResultCollectionExtensionInterface
    {
        return new class($compiled) implements ResultCollectionExtensionInterface {
            /**
             * @param \ArrayObject<string, string> $compiled
             */
            public function __construct(private readonly \ArrayObject $compiled)
            {
            }

            public function applyToCollection(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
            {
            }

            public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool
            {
                return true;
            }

            public function getResult(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): iterable
            {
                $this->compiled['query'] = $query->compile()['query'];

                return [];
            }
        };
    }
}
