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

use ApiPlatform\Elasticsearch\Metadata\Resource\Factory\ElasticsearchProviderResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Tests\Fixtures\TestBundle\Metadata\Get;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\CatNamespace;
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
                $this->prophesize(Client::class)->reveal(),
                $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal()
            )
        );
    }

    /** @dataProvider elasticsearchProvider */
    public function testCreate(?bool $elasticsearchFlag, int $expectedCatCallCount, ?bool $expectedResult): void
    {
        $get = (new Get())->withShortName('Foo')->withElasticsearch($elasticsearchFlag);
        $resource = (new ApiResource())->withOperations(new Operations(['foo_get' => $get]));
        $metadata = new ResourceMetadataCollection(Foo::class, [$resource]);

        $decorated = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->create(Foo::class)->willReturn($metadata)->shouldBeCalled();

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
            $catNamespace->indices(['index' => 'foo'])->willThrow(new Missing404Exception());
        }

        $client = $this->prophesize(Client::class);
        $client->cat()->willReturn($catNamespace)->shouldBeCalledTimes($expectedCatCallCount);

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($client->reveal(), $decorated->reveal());
        $elasticsearchResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get')->getElasticsearch();
        self::assertEquals($expectedResult, $elasticsearchResult);
    }

    public function elasticsearchProvider(): array
    {
        return [
            'elasticsearch: false' => [false, 0, false],
            'elasticsearch: null' => [null, 1, false],
            'elasticsearch: true' => [true, 1, true],
        ];
    }
}
