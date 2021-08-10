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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\Serializer;

use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\ItemNormalizer;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ItemNormalizerTest extends TestCase
{
    use ProphecyTrait;

    private $normalizerProphecy;
    private $itemNormalizer;

    protected function setUp(): void
    {
        $this->itemNormalizer = new ItemNormalizer(
            (
                $this->normalizerProphecy = $this
                    ->prophesize(NormalizerInterface::class)
                    ->willImplement(DenormalizerInterface::class)
                    ->willImplement(SerializerAwareInterface::class)
                    ->willImplement(CacheableSupportsMethodInterface::class)
            )->reveal()
        );
    }

    public function testConstruct(): void
    {
        self::assertInstanceOf(NormalizerInterface::class, $this->itemNormalizer);
        self::assertInstanceOf(DenormalizerInterface::class, $this->itemNormalizer);
        self::assertInstanceOf(SerializerAwareInterface::class, $this->itemNormalizer);
        self::assertInstanceOf(CacheableSupportsMethodInterface::class, $this->itemNormalizer);
    }

    public function testHasCacheableSupportsMethod(): void
    {
        $this->normalizerProphecy->hasCacheableSupportsMethod()->willReturn(true)->shouldBeCalledOnce();

        self::assertTrue($this->itemNormalizer->hasCacheableSupportsMethod());
    }

    public function testDenormalize(): void
    {
        $this->normalizerProphecy->denormalize('foo', 'string', 'json', ['groups' => 'foo'])->willReturn('foo')->shouldBeCalledOnce();

        self::assertSame('foo', $this->itemNormalizer->denormalize('foo', 'string', 'json', ['groups' => 'foo']));
    }

    public function testSupportsDenormalization(): void
    {
        $this->normalizerProphecy->supportsDenormalization('foo', 'string', 'json')->willReturn(true)->shouldBeCalledOnce();
        $this->normalizerProphecy->supportsDenormalization('foo', 'string', DocumentNormalizer::FORMAT)->shouldNotBeCalled();

        self::assertTrue($this->itemNormalizer->supportsDenormalization('foo', 'string', 'json'));
        self::assertFalse($this->itemNormalizer->supportsDenormalization('foo', 'string', DocumentNormalizer::FORMAT));
    }

    public function testNormalize(): void
    {
        $this->normalizerProphecy->normalize($object = (object) ['foo'], 'json', ['groups' => 'foo'])->willReturn(['foo'])->shouldBeCalledOnce();

        self::assertSame(['foo'], $this->itemNormalizer->normalize($object, 'json', ['groups' => 'foo']));
    }

    public function testSupportsNormalization(): void
    {
        $this->normalizerProphecy->supportsNormalization($object = (object) ['foo'], 'json')->willReturn(true)->shouldBeCalledOnce();
        $this->normalizerProphecy->supportsNormalization($object, DocumentNormalizer::FORMAT)->shouldNotBeCalled();

        self::assertTrue($this->itemNormalizer->supportsNormalization($object, 'json'));
        self::assertFalse($this->itemNormalizer->supportsNormalization($object, DocumentNormalizer::FORMAT));
    }

    public function testSetSerializer(): void
    {
        $this->normalizerProphecy->setSerializer($serializer = $this->prophesize(SerializerInterface::class)->reveal())->shouldBeCalledOnce();

        $this->itemNormalizer->setSerializer($serializer);
    }

    public function testHasCacheableSupportsMethodWithDecoratedNormalizerNotAnInstanceOfCacheableSupportsMethodInterface(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The decorated normalizer must be an instance of "%s".', CacheableSupportsMethodInterface::class));

        (new ItemNormalizer($this->prophesize(NormalizerInterface::class)->reveal()))->hasCacheableSupportsMethod();
    }

    public function testDenormalizeWithDecoratedNormalizerNotAnInstanceOfDenormalizerInterface(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The decorated normalizer must be an instance of "%s".', DenormalizerInterface::class));

        (new ItemNormalizer($this->prophesize(NormalizerInterface::class)->reveal()))->denormalize('foo', 'string');
    }

    public function testSupportsDenormalizationWithDecoratedNormalizerNotAnInstanceOfDenormalizerInterface(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The decorated normalizer must be an instance of "%s".', DenormalizerInterface::class));

        (new ItemNormalizer($this->prophesize(NormalizerInterface::class)->reveal()))->supportsDenormalization('foo', 'string');
    }

    public function testSetSerializerWithDecoratedNormalizerNotAnInstanceOfSerializerAwareInterface(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The decorated normalizer must be an instance of "%s".', SerializerAwareInterface::class));

        (new ItemNormalizer($this->prophesize(NormalizerInterface::class)->reveal()))->setSerializer($this->prophesize(SerializerInterface::class)->reveal());
    }
}
