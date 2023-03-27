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

namespace ApiPlatform\Elasticsearch\Tests\Metadata\Document\Factory;

use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\CatDocumentMetadataFactory;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\CatNamespace;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CatDocumentMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        if (interface_exists(\Elastic\Elasticsearch\ClientInterface::class)) {
            $this->markTestSkipped('\Elastic\Elasticsearch\ClientInterface doesn\'t have cat method signature.');
        }
    }

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            DocumentMetadataFactoryInterface::class,
            new CatDocumentMetadataFactory(
                $this->prophesize(Client::class)->reveal(),
                $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal()
            )
        );
    }

    public function testCreate(): void
    {
        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willThrow(new IndexNotFoundException())->shouldBeCalled();

        $resourceMetadata = new ResourceMetadataCollection(Foo::class, [(new ApiResource())->withOperations(new Operations([new Get(shortName: 'Foo')]))]);

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create(Foo::class)->willReturn($resourceMetadata)->shouldBeCalled();

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

    public function testCreateWithIndexAlreadySet(): void
    {
        $originalDocumentMetadata = new DocumentMetadata('foo');

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        $documentMetadata = (new CatDocumentMetadataFactory($this->prophesize(Client::class)->reveal(), $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame($originalDocumentMetadata, $documentMetadata);
        self::assertSame('foo', $documentMetadata->getIndex());
        self::assertSame(DocumentMetadata::DEFAULT_TYPE, $documentMetadata->getType());
    }

    public function testCreateWithNoResourceShortName(): void
    {
        $originalDocumentMetadata = new DocumentMetadata();

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        $resourceMetadata = new ResourceMetadataCollection(Foo::class, [(new ApiResource())->withOperations(new Operations([new Get()]))]);

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create(Foo::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $documentMetadata = (new CatDocumentMetadataFactory($this->prophesize(Client::class)->reveal(), $resourceMetadataFactory->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame($originalDocumentMetadata, $documentMetadata);
        self::assertNull($documentMetadata->getIndex());
        self::assertSame(DocumentMetadata::DEFAULT_TYPE, $documentMetadata->getType());
    }

    public function testCreateWithIndexNotFound(): void
    {
        $this->expectException(IndexNotFoundException::class);
        $this->expectExceptionMessage(sprintf('No index associated with the "%s" resource class.', Foo::class));

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willThrow(new IndexNotFoundException())->shouldBeCalled();

        $resourceMetadata = new ResourceMetadataCollection(Foo::class, [(new ApiResource())->withOperations(new Operations([new Get(shortName: 'Foo')]))]);

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create(Foo::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $catNamespaceProphecy = $this->prophesize(CatNamespace::class);
        // @phpstan-ignore-next-line
        $catNamespaceProphecy->indices(['index' => 'foo'])->willThrow(new Missing404Exception())->shouldBeCalled();

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->cat()->willReturn($catNamespaceProphecy)->shouldBeCalled();

        (new CatDocumentMetadataFactory($clientProphecy->reveal(), $resourceMetadataFactory->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);
    }
}
