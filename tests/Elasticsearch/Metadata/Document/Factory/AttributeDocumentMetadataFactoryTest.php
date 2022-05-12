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

namespace ApiPlatform\Tests\Elasticsearch\Metadata\Document\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\AttributeDocumentMetadataFactory;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

/**
 *  @group legacy
 */
class AttributeDocumentMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct()
    {
        self::assertInstanceOf(
            DocumentMetadataFactoryInterface::class,
            new AttributeDocumentMetadataFactory(
                $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal()
            )
        );
    }

    public function testCreate()
    {
        $originalDocumentMetadata = new DocumentMetadata();

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withAttributes(['elasticsearch_index' => 'foo', 'elasticsearch_type' => 'bar']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $documentMetadata = (new AttributeDocumentMetadataFactory($resourceMetadataFactoryProphecy->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertNotSame($originalDocumentMetadata, $documentMetadata);
        self::assertSame('foo', $documentMetadata->getIndex());
        self::assertSame('bar', $documentMetadata->getType());
    }

    public function testCreateWithNoParentDocumentMetadataAndNoAttributes()
    {
        $this->expectException(IndexNotFoundException::class);
        $this->expectExceptionMessage(sprintf('No index associated with the "%s" resource class.', Foo::class));

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willThrow(new IndexNotFoundException())->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        (new AttributeDocumentMetadataFactory($resourceMetadataFactoryProphecy->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);
    }
}
