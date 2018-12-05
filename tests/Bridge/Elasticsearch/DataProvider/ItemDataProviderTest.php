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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\DataProvider;

use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\ItemDataProvider;
use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\ItemNormalizer;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ItemDataProviderTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            ItemDataProviderInterface::class,
            new ItemDataProvider(
                $this->prophesize(Client::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(DenormalizerInterface::class)->reveal()
            )
        );
    }

    public function testSupports()
    {
        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactoryProphecy->create(Foo::class)->willReturn(new DocumentMetadata('foo'))->shouldBeCalled();
        $documentMetadataFactoryProphecy->create(Dummy::class)->willThrow(new IndexNotFoundException())->shouldBeCalled();

        $itemDataProvider = new ItemDataProvider(
            $this->prophesize(Client::class)->reveal(),
            $documentMetadataFactoryProphecy->reveal(),
            $this->prophesize(DenormalizerInterface::class)->reveal()
        );

        self::assertTrue($itemDataProvider->supports(Foo::class));
        self::assertFalse($itemDataProvider->supports(Dummy::class));
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
        $denormalizerProphecy->denormalize($document, Foo::class, ItemNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true])->willReturn($foo)->shouldBeCalled();

        $itemDataProvider = new ItemDataProvider($clientProphecy->reveal(), $documentMetadataFactoryProphecy->reveal(), $denormalizerProphecy->reveal());

        self::assertSame($foo, $itemDataProvider->getItem(Foo::class, ['id' => 1]));
    }

    public function testGetItemWithMissing404Exception()
    {
        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactoryProphecy->create(Foo::class)->willReturn(new DocumentMetadata('foo'))->shouldBeCalled();

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->get(['index' => 'foo', 'type' => DocumentMetadata::DEFAULT_TYPE, 'id' => '404'])->willThrow(new Missing404Exception())->shouldBeCalled();

        $itemDataProvider = new ItemDataProvider($clientProphecy->reveal(), $documentMetadataFactoryProphecy->reveal(), $this->prophesize(DenormalizerInterface::class)->reveal());

        self::assertNull($itemDataProvider->getItem(Foo::class, ['id' => 404]));
    }

    public function testGetItemWithCompositeIdentifiers()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Composite identifiers not supported.');

        $itemDataProvider = new ItemDataProvider($this->prophesize(Client::class)->reveal(), $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal(), $this->prophesize(DenormalizerInterface::class)->reveal());
        $itemDataProvider->getItem(CompositeRelation::class, ['compositeItem' => 1, 'compositeLabel' => 2]);
    }
}
