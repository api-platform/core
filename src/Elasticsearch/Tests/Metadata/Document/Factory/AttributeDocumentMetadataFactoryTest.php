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
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\AttributeDocumentMetadataFactory;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class AttributeDocumentMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            DocumentMetadataFactoryInterface::class,
            new AttributeDocumentMetadataFactory(
                $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal()
            )
        );
    }

    public function testCreate(): void
    {
        $originalDocumentMetadata = new DocumentMetadata();

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        $resourceMetadata = new ResourceMetadataCollection(Foo::class, [(new ApiResource())->withOperations(new Operations([(new Get())->withExtraProperties(['elasticsearch_index' => 'foo', 'elasticsearch_type' => 'bar'])]))]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $documentMetadata = (new AttributeDocumentMetadataFactory($resourceMetadataFactoryProphecy->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertNotSame($originalDocumentMetadata, $documentMetadata);
        self::assertSame('foo', $documentMetadata->getIndex());
        self::assertSame('bar', $documentMetadata->getType());
    }

    public function testCreateWithNoParentDocumentMetadataAndNoAttributes(): void
    {
        $this->expectException(IndexNotFoundException::class);
        $this->expectExceptionMessage(sprintf('No index associated with the "%s" resource class.', Foo::class));

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willThrow(new IndexNotFoundException())->shouldBeCalled();

        $resourceMetadata = new ResourceMetadataCollection(Foo::class, [(new ApiResource())->withOperations(new Operations([new Get()]))]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn($resourceMetadata)->shouldBeCalled();

        (new AttributeDocumentMetadataFactory($resourceMetadataFactoryProphecy->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);
    }
}
