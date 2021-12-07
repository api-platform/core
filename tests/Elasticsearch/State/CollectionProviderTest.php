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

namespace ApiPlatform\Core\Tests\Elasticsearch\State;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Paginator;
use ApiPlatform\Elasticsearch\State\CollectionProvider;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use Elasticsearch\Client;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @requires PHP 8.0
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class CollectionProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct()
    {
        self::assertInstanceOf(
            CollectionProvider::class,
            new CollectionProvider(
                $this->prophesize(Client::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(DenormalizerInterface::class)->reveal(),
                new Pagination($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal()),
                $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal()
            )
        );
    }

    public function testSupports()
    {
        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactoryProphecy->create(Foo::class)->willReturn(new DocumentMetadata('foo'))->shouldBeCalled();
        $documentMetadataFactoryProphecy->create(Dummy::class)->willThrow(new IndexNotFoundException())->shouldBeCalled();
        $documentMetadataFactoryProphecy->create(CompositeRelation::class)->willReturn(new DocumentMetadata('composite_relation'))->shouldNotBeCalled();

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $fooResourceMetadataCollection = new ResourceMetadataCollection(Foo::class);
        $fooResourceMetadataCollection[] = (new ApiResource())->withOperations(new Operations([
            'api_foo_get_collection' => (new Operation())->withElasticsearch(true),
        ]));
        $resourceMetadataCollectionFactoryProphecy->create(Foo::class)->shouldBeCalled()->willReturn($fooResourceMetadataCollection);

        $dummyCarResourceMetadataCollection = new ResourceMetadataCollection(DummyCar::class);
        $dummyCarResourceMetadataCollection[] = (new ApiResource())->withOperations(new Operations([
            'api_dummy_car_get_collection' => (new Operation())->withElasticsearch(false),
        ]));
        $resourceMetadataCollectionFactoryProphecy->create(DummyCar::class)->shouldBeCalled()->willReturn($dummyCarResourceMetadataCollection);

        $dummyResourceMetadataCollection = new ResourceMetadataCollection(Dummy::class);
        $dummyResourceMetadataCollection[] = (new ApiResource())->withOperations(new Operations([
            'api_dummy_get_collection' => (new Operation())->withElasticsearch(true),
        ]));
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyResourceMetadataCollection);

        $compositeRelationResourceMetadataCollection = new ResourceMetadataCollection(CompositeRelation::class);
        $compositeRelationResourceMetadataCollection[] = (new ApiResource())->withOperations(new Operations([
            'api_composite_relation_get_collection' => (new Operation())->withElasticsearch(true)->withUriVariables(['id' => new Link(), 'slug' => new Link()]),
        ]));
        $resourceMetadataCollectionFactoryProphecy->create(CompositeRelation::class)->shouldBeCalled()->willReturn($compositeRelationResourceMetadataCollection);

        $provider = new CollectionProvider(
            $this->prophesize(Client::class)->reveal(),
            $documentMetadataFactoryProphecy->reveal(),
            $this->prophesize(DenormalizerInterface::class)->reveal(),
            new Pagination($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal()),
            $resourceMetadataCollectionFactoryProphecy->reveal()
        );

        self::assertTrue($provider->supports(Foo::class, [], 'api_foo_get_collection'));
        self::assertFalse($provider->supports(DummyCar::class, [], 'api_dummy_car_get_collection'));
        self::assertFalse($provider->supports(Dummy::class, [], 'api_dummy_get_collection'));
        self::assertFalse($provider->supports(CompositeRelation::class, [], 'api_composite_relation_get_collection'));
    }

    public function testGetCollection()
    {
        $context = [
            'groups' => ['custom'],
        ];

        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactoryProphecy->create(Foo::class)->willReturn(new DocumentMetadata('foo'))->shouldBeCalled();

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

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy
            ->search(
                Argument::allOf(
                    Argument::withEntry('index', 'foo'),
                    Argument::withEntry('type', DocumentMetadata::DEFAULT_TYPE),
                    Argument::withEntry('body', Argument::allOf(
                        Argument::withEntry('size', 2),
                        Argument::withEntry('from', 0),
                        Argument::withEntry('query', Argument::allOf(
                            Argument::withEntry('match_all', Argument::type(\stdClass::class)),
                            Argument::size(1)
                        )),
                        Argument::size(3)
                    )),
                    Argument::size(3)
                )
            )
            ->willReturn($documents)
            ->shouldBeCalled();

        $requestBodySearchCollectionExtensionProphecy = $this->prophesize(RequestBodySearchCollectionExtensionInterface::class);
        $requestBodySearchCollectionExtensionProphecy->applyToCollection([], Foo::class, 'get', $context)->willReturn([])->shouldBeCalled();

        $provider = new CollectionProvider(
            $clientProphecy->reveal(),
            $documentMetadataFactoryProphecy->reveal(),
            $denormalizer = $this->prophesize(DenormalizerInterface::class)->reveal(),
            new Pagination($resourceMetadataCollectionFactoryProphecy->reveal(), ['items_per_page' => 2]),
            $resourceMetadataCollectionFactoryProphecy->reveal(),
            [$requestBodySearchCollectionExtensionProphecy->reveal()]
        );

        self::assertEquals(
            new Paginator($denormalizer, $documents, Foo::class, 2, 0, $context),
            $provider->provide(Foo::class, [], 'get', $context)
        );
    }
}
