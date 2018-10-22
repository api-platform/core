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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DataProvider;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainCollectionDataProvider;
use ApiPlatform\Core\DataProvider\ChainCollectionDataProvider;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use PHPUnit\Framework\TestCase;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
class TraceableChainCollectionDataCollectorTest extends TestCase
{
    /** @dataProvider dataProviderProvider */
    public function testGetCollection($provider, $context, $expected)
    {
        $dataProvider = new TraceableChainCollectionDataProvider($provider);
        $dataProvider->getCollection('', null, $context);

        $result = $dataProvider->getProvidersResponse();
        $this->assertCount(\count($expected), $result);
        $this->assertEmpty(array_filter($result, function ($key) {
            return 0 !== strpos($key, 'class@anonymous');
        }, ARRAY_FILTER_USE_KEY));
        $this->assertSame($expected, array_values($result));
        $this->assertSame($context, $dataProvider->getContext());
    }

    /**
     * @dataProvider deprecatedDataProviderProvider
     * @group legacy
     */
    public function testDeprecatedGetCollection($provider, $context, $expected)
    {
        $dataProvider = new TraceableChainCollectionDataProvider($provider);
        $dataProvider->getCollection('', null, $context);

        $result = $dataProvider->getProvidersResponse();
        $this->assertCount(\count($expected), $result);
        $this->assertEmpty(array_filter($result, function ($key) {
            return 0 !== strpos($key, 'class@anonymous');
        }, ARRAY_FILTER_USE_KEY));
        $this->assertSame($expected, array_values($result));
        $this->assertSame($context, $dataProvider->getContext());
    }

    public function dataProviderProvider(): iterable
    {
        yield 'Not a ChainCollectionDataProvider' => [
            new class() implements CollectionDataProviderInterface {
                public function getCollection(string $resourceClass, string $operationName = null)
                {
                }
            },
            ['some_context'],
            [],
        ];

        yield  'Empty ChainCollectionDataProvider' => [
            new ChainCollectionDataProvider([]),
            ['some_context'],
            [],
        ];

        yield 'ChainCollectionDataProvider' => [
            new ChainCollectionDataProvider([
                new class() implements CollectionDataProviderInterface, RestrictedDataProviderInterface {
                    public function getCollection(string $resourceClass, string $operationName = null)
                    {
                        return [];
                    }

                    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
                    {
                        return false;
                    }
                },
                new class() implements CollectionDataProviderInterface, RestrictedDataProviderInterface {
                    public function getCollection(string $resourceClass, string $operationName = null)
                    {
                        return [];
                    }

                    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
                    {
                        return true;
                    }
                },
                new class() implements CollectionDataProviderInterface {
                    public function getCollection(string $resourceClass, string $operationName = null)
                    {
                        return [];
                    }
                },
            ]),
            ['some_context'],
            [false, true, null],
        ];
    }

    public function deprecatedDataProviderProvider(): iterable
    {
        yield 'ChainCollectionDataProvider' => [
            new ChainCollectionDataProvider([
                new class() implements CollectionDataProviderInterface {
                    public function getCollection(string $resourceClass, string $operationName = null)
                    {
                        throw new ResourceClassNotSupportedException('nope');
                    }
                },
                new class() implements CollectionDataProviderInterface {
                    public function getCollection(string $resourceClass, string $operationName = null)
                    {
                    }
                },
            ]),
            ['some_context'],
            [false, true],
        ];
    }
}
