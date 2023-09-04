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

use ApiPlatform\Elasticsearch\Metadata\Resource\Factory\ElasticsearchProviderResourceMetadataCollectionFactory;
use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Tests\Fixtures\Metadata\Get;
use Elasticsearch\Client as LegacyClient;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\CatNamespace;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class ElasticsearchProviderResourceMetadataCollectionFactoryTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            ResourceMetadataCollectionFactoryInterface::class,
            new ElasticsearchProviderResourceMetadataCollectionFactory(
                class_exists(LegacyClient::class) ? $this->prophesize(LegacyClient::class)->reveal() : null,
                $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal()
            )
        );
    }

    /**
     * @dataProvider elasticsearchProvider
     */
    public function testCreate(?bool $elasticsearchFlag, int $expectedCatCallCount, ?bool $expectedResult): void
    {
        if (interface_exists(\Elastic\Elasticsearch\ClientInterface::class)) {
            $this->markTestSkipped('\Elastic\Elasticsearch\ClientInterface doesn\'t have cat method signature.');
        }

        if (null !== $elasticsearchFlag) {
            $solution = $elasticsearchFlag
                ? sprintf('Pass an instance of %s to $stateOptions instead', Options::class)
                : 'You will have to remove it when upgrading to v4';
            $this->expectDeprecation(sprintf('Since api-platform/core 3.1: Setting "elasticsearch" in Operation is deprecated. %s', $solution));
        }
        $get = (new Get(elasticsearch: $elasticsearchFlag, shortName: 'Foo'));
        $resource = (new ApiResource(operations: ['foo_get' => $get]));
        $metadata = new ResourceMetadataCollection(Foo::class, [$resource]);

        $decorated = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->create(Foo::class)->willReturn($metadata)->shouldBeCalled();

        // @phpstan-ignore-next-line
        $catNamespace = $this->prophesize(CatNamespace::class);
        if ($elasticsearchFlag) {
            $catNamespace->indices(['index' => 'foo'])->willReturn([[
                'health' => 'yellow',
                'status' => 'open',
                'index' => 'foo',
                'uuid' => '123456789abcdefghijklmn',
                'pri' => '5',
                'rep' => '1',
                'docs.count' => '42',
                'docs.deleted' => '0',
                'store.size' => '42kb',
                'pri.store.size' => '42kb',
            ]]);
        } else {
            // @phpstan-ignore-next-line
            $catNamespace->indices(['index' => 'foo'])->willThrow(new Missing404Exception());
        }

        // @phpstan-ignore-next-line
        $client = $this->prophesize(LegacyClient::class);
        $client->cat()->willReturn($catNamespace)->shouldBeCalledTimes($expectedCatCallCount);

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($client->reveal(), $decorated->reveal(), false);
        $elasticsearchResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get')->getElasticsearch();
        self::assertEquals($expectedResult, $elasticsearchResult);
    }

    public static function elasticsearchProvider(): array
    {
        return [
            'elasticsearch: false' => [false, 0, false],
            'elasticsearch: null' => [null, 1, false],
            'elasticsearch: true' => [true, 1, true],
        ];
    }
}
