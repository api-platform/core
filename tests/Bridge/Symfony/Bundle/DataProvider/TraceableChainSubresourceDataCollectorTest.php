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

use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainSubresourceDataProvider;
use ApiPlatform\Core\DataProvider\ChainSubresourceDataProvider;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use PHPUnit\Framework\TestCase;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
class TraceableChainSubresourceDataCollectorTest extends TestCase
{
    /** @dataProvider dataProviderProvider */
    public function testGetSubresource($provider, $context, $expected)
    {
        $dataProvider = new TraceableChainSubresourceDataProvider($provider);
        $dataProvider->getSubresource('', [], $context);

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
    public function testDeprecatedGetSubResource($provider, $context, $expected)
    {
        $dataProvider = new TraceableChainSubresourceDataProvider($provider);
        $dataProvider->getSubresource('', [], $context);

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
        yield 'Not a ChainSubresourceDataProvider' => [
            new class() implements SubresourceDataProviderInterface {
                public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
                {
                }
            },
            ['some_context'],
            [],
        ];

        yield  'Empty ChainSubresourceDataProvider' => [
            new ChainSubresourceDataProvider([]),
            ['some_context'],
            [],
        ];

        yield 'ChainSubresourceDataProvider' => [
            new ChainSubresourceDataProvider([
                new class() implements SubresourceDataProviderInterface, RestrictedDataProviderInterface {
                    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
                    {
                    }

                    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
                    {
                        return false;
                    }
                },
                new class() implements SubresourceDataProviderInterface, RestrictedDataProviderInterface {
                    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
                    {
                    }

                    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
                    {
                        return true;
                    }
                },
                new class() implements SubresourceDataProviderInterface {
                    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
                    {
                    }
                },
            ]),
            ['some_context'],
            [false, true, null],
        ];
    }

    public function deprecatedDataProviderProvider(): iterable
    {
        yield 'deprecated ChainSubresourceDataProvider - ResourceClassNotSupportedException' => [
            new ChainSubresourceDataProvider([
                new class() implements SubresourceDataProviderInterface {
                    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
                    {
                        throw new ResourceClassNotSupportedException('nope');
                    }
                },
                new class() implements SubresourceDataProviderInterface {
                    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
                    {
                    }
                },
            ]),
            ['some_context'],
            [false, true],
        ];
    }
}
