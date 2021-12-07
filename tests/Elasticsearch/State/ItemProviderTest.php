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
use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @requires PHP 8.0
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ItemProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct()
    {
        self::assertInstanceOf(
            ItemProvider::class,
            new ItemProvider(
                $this->prophesize(Client::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(DenormalizerInterface::class)->reveal(),
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
            'api_foo_get' => (new Operation())->withElasticsearch(true),
        ]));
        $resourceMetadataCollectionFactoryProphecy->create(Foo::class)->shouldBeCalled()->willReturn($fooResourceMetadataCollection);

        $dummyCarResourceMetadataCollection = new ResourceMetadataCollection(DummyCar::class);
        $dummyCarResourceMetadataCollection[] = (new ApiResource())->withOperations(new Operations([
            'api_dummy_car_get' => (new Operation())->withElasticsearch(false),
        ]));
        $resourceMetadataCollectionFactoryProphecy->create(DummyCar::class)->shouldBeCalled()->willReturn($dummyCarResourceMetadataCollection);

        $dummyResourceMetadataCollection = new ResourceMetadataCollection(Dummy::class);
        $dummyResourceMetadataCollection[] = (new ApiResource())->withOperations(new Operations([
            'api_dummy_get' => (new Operation())->withElasticsearch(true),
        ]));
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyResourceMetadataCollection);

        $compositeRelationResourceMetadataCollection = new ResourceMetadataCollection(CompositeRelation::class);
        $compositeRelationResourceMetadataCollection[] = (new ApiResource())->withOperations(new Operations([
            'api_composite_relation_get' => (new Operation())->withElasticsearch(true)->withUriVariables(['id' => new Link(), 'slug' => new Link()]),
        ]));
        $resourceMetadataCollectionFactoryProphecy->create(CompositeRelation::class)->shouldBeCalled()->willReturn($compositeRelationResourceMetadataCollection);

        $provider = new ItemProvider(
            $this->prophesize(Client::class)->reveal(),
            $documentMetadataFactoryProphecy->reveal(),
            $this->prophesize(DenormalizerInterface::class)->reveal(),
            $resourceMetadataCollectionFactoryProphecy->reveal()
        );

        self::assertTrue($provider->supports(Foo::class, [], 'api_foo_get'));
        self::assertFalse($provider->supports(DummyCar::class, [], 'api_dummy_car_get'));
        self::assertFalse($provider->supports(Dummy::class, [], 'api_dummy_get'));
        self::assertFalse($provider->supports(CompositeRelation::class, [], 'api_composite_relation_get'));
    }

    public function testGetItem()
    {
        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactoryProphecy->create(Foo::class)->willReturn(new DocumentMetadata('foo'))->shouldBeCalled();

        $document = [
            '_index' => 'foo',
            '_type' => '_doc',
            '_id' => '1',
            '_version' => 1,
            'found' => true,
            '_source' => [
                'id' => 1,
                'name' => 'Rossinière',
                'bar' => 'erèinissor',
            ],
        ];

        $foo = new Foo();
        $foo->setName('Rossinière');
        $foo->setBar('erèinissor');

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->get(['index' => 'foo', 'type' => DocumentMetadata::DEFAULT_TYPE, 'id' => '1'])->willReturn($document)->shouldBeCalled();

        $denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $denormalizerProphecy->denormalize($document, Foo::class, DocumentNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true])->willReturn($foo)->shouldBeCalled();

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $itemDataProvider = new ItemProvider($clientProphecy->reveal(), $documentMetadataFactoryProphecy->reveal(), $denormalizerProphecy->reveal(), $resourceMetadataCollectionFactoryProphecy->reveal());

        self::assertSame($foo, $itemDataProvider->provide(Foo::class, ['id' => 1]));
    }

    public function testGetItemWithMissing404Exception()
    {
        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactoryProphecy->create(Foo::class)->willReturn(new DocumentMetadata('foo'))->shouldBeCalled();

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->get(['index' => 'foo', 'type' => DocumentMetadata::DEFAULT_TYPE, 'id' => '404'])->willThrow(new Missing404Exception())->shouldBeCalled();

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $itemDataProvider = new ItemProvider($clientProphecy->reveal(), $documentMetadataFactoryProphecy->reveal(), $this->prophesize(DenormalizerInterface::class)->reveal(), $resourceMetadataCollectionFactoryProphecy->reveal());

        self::assertNull($itemDataProvider->provide(Foo::class, ['id' => 404]));
    }
}
