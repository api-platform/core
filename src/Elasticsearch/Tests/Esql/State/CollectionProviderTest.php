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
use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Elasticsearch\State\QueryLanguage;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use Elastic\Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;

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

        $provider = new CollectionProvider(
            ClientBuilder::create()->build(),
            null,
            [$regularExtension, $resultExtension],
        );

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
        $resultExtension = new class implements ResultCollectionExtensionInterface {
            public ?string $compiledQuery = null;

            public function applyToCollection(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
            {
            }

            public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool
            {
                return true;
            }

            public function getResult(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): iterable
            {
                $this->compiledQuery = $query->compile()['query'];

                return [];
            }
        };

        $provider = new CollectionProvider(ClientBuilder::create()->build(), null, [$resultExtension]);

        $operation = (new GetCollection(name: 'get_collection', shortName: 'FooBar'))->withClass(Foo::class);
        $provider->provide($operation);

        self::assertSame('FROM foo_bar METADATA _id', $resultExtension->compiledQuery);
    }

    public function testHandleLinksCallableIsInvoked(): void
    {
        $resultExtension = new class implements ResultCollectionExtensionInterface {
            public ?string $compiledQuery = null;

            public function applyToCollection(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
            {
            }

            public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool
            {
                return true;
            }

            public function getResult(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): iterable
            {
                $this->compiledQuery = $query->compile()['query'];

                return [];
            }
        };

        $handleLinks = static function (EsqlQuery $query, array $uriVariables, array $context): void {
            $query->andWhere(\sprintf('user_id == %s', $query->param($uriVariables['userId'])));
        };

        $provider = new CollectionProvider(ClientBuilder::create()->build(), null, [$resultExtension]);

        $operation = (new GetCollection(name: 'get_collection', shortName: 'Foo'))
            ->withClass(Foo::class)
            ->withStateOptions(new Options(index: 'foo', queryLanguage: QueryLanguage::Esql, handleLinks: $handleLinks));

        $provider->provide($operation, ['userId' => 42]);

        self::assertSame('FROM foo METADATA _id | WHERE user_id == ?p1', $resultExtension->compiledQuery);
    }
}
