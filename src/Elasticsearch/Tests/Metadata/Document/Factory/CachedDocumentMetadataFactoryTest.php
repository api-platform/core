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
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\CachedDocumentMetadataFactory;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Exception\CacheException;

class CachedDocumentMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            DocumentMetadataFactoryInterface::class,
            new CachedDocumentMetadataFactory(
                $this->prophesize(CacheItemPoolInterface::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal()
            )
        );
    }

    public function testCreate(): void
    {
        $originalDocumentMetadata = new DocumentMetadata('foo');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false)->shouldBeCalled();
        $cacheItemProphecy->set($originalDocumentMetadata)->willReturn($cacheItemProphecy)->shouldBeCalled();
        $cacheItemProphecy->get()->shouldNotBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy)->shouldBeCalled();
        $cacheItemPoolProphecy->save($cacheItemProphecy)->willReturn(true)->shouldBeCalled();

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        $documentMetadata = (new CachedDocumentMetadataFactory($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame($originalDocumentMetadata, $documentMetadata);
        self::assertSame('foo', $documentMetadata->getIndex());
        self::assertSame(DocumentMetadata::DEFAULT_TYPE, $documentMetadata->getType());
    }

    public function testCreateWithLocalCache(): void
    {
        $originalDocumentMetadata = new DocumentMetadata('foo');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false)->shouldBeCalledTimes(1);
        $cacheItemProphecy->set($originalDocumentMetadata)->willReturn($cacheItemProphecy)->shouldBeCalledTimes(1);
        $cacheItemProphecy->get()->shouldNotBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy)->shouldBeCalledTimes(1);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->willReturn(true)->shouldBeCalledTimes(1);

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalledTimes(1);

        $documentMetadataFactory = new CachedDocumentMetadataFactory($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());
        $documentMetadataFactory->create(Foo::class);

        $documentMetadata = $documentMetadataFactory->create(Foo::class);

        self::assertSame($originalDocumentMetadata, $documentMetadata);
        self::assertSame('foo', $documentMetadata->getIndex());
        self::assertSame(DocumentMetadata::DEFAULT_TYPE, $documentMetadata->getType());
    }

    public function testCreateWithCacheException(): void
    {
        $originalDocumentMetadata = new DocumentMetadata('foo');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->shouldNotBeCalled();
        $cacheItemProphecy->set(Argument::any())->shouldNotBeCalled();
        $cacheItemProphecy->get()->shouldNotBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willThrow(new CacheException())->shouldBeCalledTimes(1);
        $cacheItemPoolProphecy->save(Argument::any())->shouldNotBeCalled();

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        $documentMetadata = (new CachedDocumentMetadataFactory($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame($originalDocumentMetadata, $documentMetadata);
        self::assertSame('foo', $documentMetadata->getIndex());
        self::assertSame(DocumentMetadata::DEFAULT_TYPE, $documentMetadata->getType());
    }

    public function testCreateWithCacheHit(): void
    {
        $originalDocumentMetadata = new DocumentMetadata('foo');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true)->shouldBeCalled();
        $cacheItemProphecy->get()->willReturn($originalDocumentMetadata)->shouldBeCalled();
        $cacheItemProphecy->set(Argument::any())->shouldNotBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy)->shouldBeCalled();
        $cacheItemPoolProphecy->save(Argument::any())->shouldNotBeCalled();

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Argument::any())->shouldNotBeCalled();

        $documentMetadata = (new CachedDocumentMetadataFactory($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame($originalDocumentMetadata, $documentMetadata);
        self::assertSame('foo', $documentMetadata->getIndex());
        self::assertSame(DocumentMetadata::DEFAULT_TYPE, $documentMetadata->getType());
    }

    public function testCreateWithIndexNotDefined(): void
    {
        $this->expectException(IndexNotFoundException::class);
        $this->expectExceptionMessage(sprintf('No index associated with the "%s" resource class.', Foo::class));

        $originalDocumentMetadata = new DocumentMetadata();

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false)->shouldBeCalled();
        $cacheItemProphecy->set($originalDocumentMetadata)->willReturn($cacheItemProphecy)->shouldBeCalled();
        $cacheItemProphecy->get()->shouldNotBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy)->shouldBeCalled();
        $cacheItemPoolProphecy->save($cacheItemProphecy)->willReturn(true)->shouldBeCalled();

        $decoratedProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalDocumentMetadata)->shouldBeCalled();

        (new CachedDocumentMetadataFactory($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal()))->create(Foo::class);
    }
}
