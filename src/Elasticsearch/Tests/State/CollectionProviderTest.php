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

namespace ApiPlatform\Elasticsearch\Tests\State;

use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Paginator;
use ApiPlatform\Elasticsearch\State\CollectionProvider;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Pagination\Pagination;
use Elastic\Elasticsearch\ClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class CollectionProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        if (interface_exists(ClientInterface::class)) {
            $this->markTestSkipped('Can not test using Elasticsearch 8.');
        }

        self::assertInstanceOf(
            CollectionProvider::class,
            new CollectionProvider(
                $this->prophesize(\Elasticsearch\Client::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(DenormalizerInterface::class)->reveal(),
                new Pagination()
            )
        );
    }

    public function testGetCollection(): void
    {
        if (interface_exists(ClientInterface::class)) {
            $this->markTestSkipped('Can not test using Elasticsearch 8.');
        }

        $context = [
            'groups' => ['custom'],
        ];

        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadataCollection(Foo::class));

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

        $clientProphecy = $this->prophesize(\Elasticsearch\Client::class);
        $clientProphecy
            ->search(
                Argument::allOf(
                    Argument::withEntry('index', 'foo'),
                    Argument::withEntry('body', Argument::allOf(
                        Argument::withEntry('size', 2),
                        Argument::withEntry('from', 0),
                        Argument::withEntry('query', Argument::allOf(
                            Argument::withEntry('match_all', Argument::type(\stdClass::class)),
                            Argument::size(1)
                        )),
                        Argument::size(3)
                    )),
                    Argument::size(2)
                )
            )
            ->willReturn($documents)
            ->shouldBeCalled();

        $operation = (new Get())->withName('get')->withClass(Foo::class)->withShortName('foo');

        $requestBodySearchCollectionExtensionProphecy = $this->prophesize(RequestBodySearchCollectionExtensionInterface::class);
        $requestBodySearchCollectionExtensionProphecy->applyToCollection([], Foo::class, $operation, $context)->willReturn([])->shouldBeCalled();

        $provider = new CollectionProvider(
            $clientProphecy->reveal(),
            $documentMetadataFactoryProphecy->reveal(),
            $denormalizer = $this->prophesize(DenormalizerInterface::class)->reveal(),
            new Pagination(['items_per_page' => 2]),
            [$requestBodySearchCollectionExtensionProphecy->reveal()]
        );

        self::assertEquals(
            new Paginator($denormalizer, $documents, Foo::class, 2, 0, $context),
            $provider->provide($operation, [], $context)
        );
    }
}
