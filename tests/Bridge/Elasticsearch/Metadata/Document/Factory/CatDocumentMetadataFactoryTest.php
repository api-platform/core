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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\Metadata\Document\Factory;

use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\CatDocumentMetadataFactory;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\CatNamespace;
use PHPUnit\Framework\TestCase;

class CatDocumentMetadataFactoryTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            DocumentMetadataFactoryInterface::class,
            new CatDocumentMetadataFactory(
                $this->prophesize(Client::class)->reveal(),
                $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal()
            )
        );
    }

    public function testCreate()
    {
        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willThrow(new IndexNotFoundException())->shouldBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Foo::class)->willReturn(new ResourceMetadata('Foo'))->shouldBeCalled();

        $catNamespaceProphecy = $this->prophesize(CatNamespace::class);
        $catNamespaceProphecy->indices(['index' => 'foo'])
            ->willReturn([[
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
            ]])
            ->shouldBeCalled();

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->cat()->willReturn($catNamespaceProphecy)->shouldBeCalled();

        $documentMetadata = (new CatDocumentMetadataFactory($clientProphecy->reveal(), $resourceMetadataFactory->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame('foo', $documentMetadata->getIndex());
        self::assertSame(DocumentMetadata::DEFAULT_TYPE, $documentMetadata->getType());
    }

    public function testCreateWithIndexAlreadySet()
    {
        $originalDocumentMetadata = new DocumentMetadata('foo');

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        $documentMetadata = (new CatDocumentMetadataFactory($this->prophesize(Client::class)->reveal(), $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame($originalDocumentMetadata, $documentMetadata);
        self::assertSame('foo', $documentMetadata->getIndex());
        self::assertSame(DocumentMetadata::DEFAULT_TYPE, $documentMetadata->getType());
    }

    public function testCreateWithNoResourceShortName()
    {
        $originalDocumentMetadata = new DocumentMetadata();

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Foo::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $documentMetadata = (new CatDocumentMetadataFactory($this->prophesize(Client::class)->reveal(), $resourceMetadataFactory->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame($originalDocumentMetadata, $documentMetadata);
        self::assertNull($documentMetadata->getIndex());
        self::assertSame(DocumentMetadata::DEFAULT_TYPE, $documentMetadata->getType());
    }

    public function testCreateWithIndexNotFound()
    {
        $this->expectException(IndexNotFoundException::class);
        $this->expectExceptionMessage(sprintf('No index associated with the "%s" resource class.', Foo::class));

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willThrow(new IndexNotFoundException())->shouldBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Foo::class)->willReturn(new ResourceMetadata('Foo'))->shouldBeCalled();

        $catNamespaceProphecy = $this->prophesize(CatNamespace::class);
        $catNamespaceProphecy->indices(['index' => 'foo'])->willThrow(new Missing404Exception())->shouldBeCalled();

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->cat()->willReturn($catNamespaceProphecy)->shouldBeCalled();

        (new CatDocumentMetadataFactory($clientProphecy->reveal(), $resourceMetadataFactory->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);
    }
}
