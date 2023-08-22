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
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\ConfiguredDocumentMetadataFactory;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ConfiguredDocumentMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            DocumentMetadataFactoryInterface::class,
            new ConfiguredDocumentMetadataFactory([], $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal())
        );
    }

    public function testCreate(): void
    {
        $originalDocumentMetadata = new DocumentMetadata();

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        $configuredDocumentMetadata = (new ConfiguredDocumentMetadataFactory([Foo::class => ['index' => 'foo', 'type' => 'bar']], $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertNotSame($originalDocumentMetadata, $configuredDocumentMetadata);
        self::assertSame('foo', $configuredDocumentMetadata->getIndex());
        self::assertSame('bar', $configuredDocumentMetadata->getType());
    }

    public function testCreateWithEmptyMapping(): void
    {
        $originalDocumentMetadata = new DocumentMetadata();

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        $configuredDocumentMetadata = (new ConfiguredDocumentMetadataFactory([], $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame($originalDocumentMetadata, $configuredDocumentMetadata);
    }

    public function testCreateWithEmptyMappingAndNoParentDocumentMetadata(): void
    {
        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willThrow(new IndexNotFoundException())->shouldBeCalled();

        $this->expectException(IndexNotFoundException::class);
        $this->expectExceptionMessage(sprintf('No index associated with the "%s" resource class.', Foo::class));

        (new ConfiguredDocumentMetadataFactory([], $decoratedProphecy->reveal()))->create(Foo::class);
    }
}
