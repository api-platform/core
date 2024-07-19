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
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Tests\Fixtures\Metadata\Get;
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

    #[\PHPUnit\Framework\Attributes\DataProvider('elasticsearchProvider')]
    public function testCreate(?bool $elasticsearchFlag, ?bool $expectedResult): void
    {
        $get = (new Get(elasticsearch: $elasticsearchFlag, shortName: 'Foo'));
        $resource = (new ApiResource(operations: ['foo_get' => $get]));
        $metadata = new ResourceMetadataCollection(Foo::class, [$resource]);

        $decorated = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->create(Foo::class)->willReturn($metadata)->shouldBeCalled();

        $resourceMetadataFactory = new ElasticsearchProviderResourceMetadataCollectionFactory($decorated->reveal());
        $elasticsearchResult = $resourceMetadataFactory->create(Foo::class)->getOperation('foo_get')->getElasticsearch();
        self::assertEquals($expectedResult, $elasticsearchResult);
    }

    public static function elasticsearchProvider(): array
    {
        return [
            'elasticsearch: false' => [false, false],
            'elasticsearch: null' => [null, false],
            'elasticsearch: true' => [true, true],
        ];
    }
}
