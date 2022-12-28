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

use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Metadata\Get;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @group legacy
 */
final class ItemProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            ItemProvider::class,
            new ItemProvider(
                $this->createStub(Client::class),
                $this->createStub(DocumentMetadataFactoryInterface::class),
                $this->createStub(DenormalizerInterface::class),
            )
        );
    }

    public function testGetItem(): void
    {
        $documentMetadataFactory = $this->createStub(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactory->method('create')->willReturn(new DocumentMetadata('foo'));

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
        $clientProphecy->get(['index' => 'foo', 'id' => '1'])->willReturn($document)->shouldBeCalled();

        $denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $denormalizerProphecy->denormalize($document, Foo::class, DocumentNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true])->willReturn($foo)->shouldBeCalled();

        $itemDataProvider = new ItemProvider($clientProphecy->reveal(), $documentMetadataFactory, $denormalizerProphecy->reveal());

        self::assertSame($foo, $itemDataProvider->provide((new Get('foo'))->withClass(Foo::class), ['id' => 1]));
    }

    public function testGetItemWithMissing404Exception(): void
    {
        $documentMetadataFactory = $this->createStub(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactory->method('create')->willReturn(new DocumentMetadata('foo'));

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->get(['index' => 'foo', 'id' => '404'])->willThrow(new Missing404Exception())->shouldBeCalled();

        $itemDataProvider = new ItemProvider($clientProphecy->reveal(), $documentMetadataFactory, $this->createStub(DenormalizerInterface::class));

        self::assertNull($itemDataProvider->provide((new Get('foo'))->withClass(Foo::class), ['id' => 404]));
    }
}
