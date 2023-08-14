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

use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\Get;
use Elasticsearch\Client;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ItemProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            ItemProvider::class,
            new ItemProvider(
                $this->prophesize(Client::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(DenormalizerInterface::class)->reveal()
            )
        );
    }

    public function testGetItem(): void
    {
        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);

        $document = [
            '_index' => 'foo',
            '_type' => '_doc',
            '_id' => '1',
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
        $clientProphecy->get(['client' => ['ignore' => 404], 'index' => 'foo', 'id' => '1'])->willReturn($document)->shouldBeCalled();

        $denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $denormalizerProphecy->denormalize($document, Foo::class, DocumentNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true])->willReturn($foo)->shouldBeCalled();

        $itemDataProvider = new ItemProvider($clientProphecy->reveal(), $documentMetadataFactoryProphecy->reveal(), $denormalizerProphecy->reveal());

        self::assertSame($foo, $itemDataProvider->provide((new Get())->withClass(Foo::class)->withShortName('foo'), ['id' => 1]));
    }

    public function testGetInexistantItem(): void
    {
        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->get(['client' => ['ignore' => 404], 'index' => 'foo', 'id' => '404'])->willReturn([
            '_index' => 'foo',
            '_type' => '_doc',
            '_id' => '404',
            'found' => false,
        ])->shouldBeCalled();

        $itemDataProvider = new ItemProvider($clientProphecy->reveal(), $documentMetadataFactoryProphecy->reveal(), $this->prophesize(DenormalizerInterface::class)->reveal());

        self::assertNull($itemDataProvider->provide((new Get())->withClass(Foo::class)->withShortName('foo'), ['id' => 404]));
    }
}
