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

namespace ApiPlatform\Tests\Elasticsearch\State;

use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Metadata\ElasticsearchDocument;
use ApiPlatform\Elasticsearch\Paginator;
use ApiPlatform\Elasticsearch\State\ElasticsearchCollectionProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use Elasticsearch\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ElasticsearchCollectionProviderTest extends TestCase
{
    public function testConstruct(): void
    {
        self::assertInstanceOf(
            ElasticsearchCollectionProvider::class,
            new ElasticsearchCollectionProvider(
                $this->createStub(Client::class),
                $this->createStub(DenormalizerInterface::class),
                new Pagination()
            )
        );
    }

    public function testGetCollection(): void
    {
        $context = [
            'groups' => ['custom'],
        ];

        $resourceMetadataCollectionFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->method('create')->willReturn(new ResourceMetadataCollection(Foo::class));

        $documents = [
            'took' => 15,
            'time_out' => false,
            '_shards' => [
                'total' => 5,
                'successful' => 5,
                'skipped' => 0,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 4,
                'max_score' => 1,
                'hits' => [
                    [
                        '_index' => 'foo',
                        '_type' => '_doc',
                        '_id' => '1',
                        '_score' => 1,
                        '_source' => [
                            'id' => 1,
                            'name' => 'Kilian',
                            'bar' => 'Jornet',
                        ],
                    ],
                    [
                        '_index' => 'foo',
                        '_type' => '_doc',
                        '_id' => '2',
                        '_score' => 1,
                        '_source' => [
                            'id' => 2,
                            'name' => 'François',
                            'bar' => 'D\'Haene',
                        ],
                    ],
                ],
            ],
        ];

        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->method('search')->with(
                [
                    'index' => 'foo',
                    'body' => [
                        'size' => 2,
                        'from' => 0,
                        'query' => [
                            'match_all' => new \stdClass(),
                        ],
                    ],
                ]
            )
            ->willReturn($documents);

        $operation = (new GetCollection(class: Foo::class, name: 'get', persistenceMeans: new ElasticsearchDocument('foo')));

        $requestBodySearchCollectionExtension = $this->createMock(RequestBodySearchCollectionExtensionInterface::class);
        $requestBodySearchCollectionExtension->expects($this->once())->method('applyToCollection')->willReturn([]);

        $provider = new ElasticsearchCollectionProvider(
            $clientMock,
            $denormalizer = $this->createStub(DenormalizerInterface::class),
            new Pagination(['items_per_page' => 2]),
            [$requestBodySearchCollectionExtension]
        );

        self::assertEquals(
            new Paginator($denormalizer, $documents, Foo::class, 2, 0, $context),
            $provider->provide($operation, [], $context)
        );
    }
}
