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

namespace ApiPlatform\Elasticsearch\Tests\Serializer;

use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Elasticsearch\Serializer\ItemNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ItemNormalizerTest extends TestCase
{
    use ProphecyTrait;

    private $normalizerProphecy;
    private ItemNormalizer $itemNormalizer;

    protected function setUp(): void
    {
        $this->normalizerProphecy = $this
            ->prophesize(NormalizerInterface::class)
            ->willImplement(DenormalizerInterface::class)
            ->willImplement(SerializerAwareInterface::class);

        if (!method_exists(Serializer::class, 'getSupportedTypes')) {
            $this->normalizerProphecy->willImplement(CacheableSupportsMethodInterface::class);
        }

        $this->itemNormalizer = new ItemNormalizer($this->normalizerProphecy->reveal());
    }

    public function testConstruct(): void
    {
        self::assertInstanceOf(NormalizerInterface::class, $this->itemNormalizer);
        self::assertInstanceOf(DenormalizerInterface::class, $this->itemNormalizer);
        self::assertInstanceOf(SerializerAwareInterface::class, $this->itemNormalizer);
    }

    /**
     * @group legacy
     */
    public function testHasCacheableSupportsMethod(): void
    {
        if (method_exists(Serializer::class, 'getSupportedTypes')) {
            $this->markTestSkipped('Symfony Serializer >= 6.3');
        }

        $this->normalizerProphecy->hasCacheableSupportsMethod()->willReturn(true)->shouldBeCalledOnce();

        self::assertTrue($this->itemNormalizer->hasCacheableSupportsMethod());
    }

    public function testDenormalize(): void
    {
        $this->normalizerProphecy->denormalize('foo', 'string', 'json', ['groups' => 'foo'])->willReturn('foo')->shouldBeCalledOnce();

        self::assertEquals('foo', $this->itemNormalizer->denormalize('foo', 'string', 'json', ['groups' => 'foo']));
    }

    public function testSupportsDenormalization(): void
    {
        $this->normalizerProphecy->supportsDenormalization('foo', 'string', 'json', Argument::type('array'))->willReturn(true)->shouldBeCalledOnce();
        $this->normalizerProphecy->supportsDenormalization('foo', 'string', DocumentNormalizer::FORMAT, Argument::type('array'))->shouldNotBeCalled();

        self::assertTrue($this->itemNormalizer->supportsDenormalization('foo', 'string', 'json'));
        self::assertFalse($this->itemNormalizer->supportsDenormalization('foo', 'string', DocumentNormalizer::FORMAT));
    }

    public function testNormalize(): void
    {
        $this->normalizerProphecy->normalize($object = (object) ['foo'], 'json', ['groups' => 'foo'])->willReturn(['foo'])->shouldBeCalledOnce();

        self::assertEquals(['foo'], $this->itemNormalizer->normalize($object, 'json', ['groups' => 'foo']));
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

    /**
     * @group legacy
     */
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

    public function testGetSupportedTypes(): void
    {
        if (!method_exists(Serializer::class, 'getSupportedTypes')) {
            $this->markTestSkipped('Symfony Serializer < 6.3');
        }

        // TODO: use prophecy when getSupportedTypes() will be added to the interface
        $this->itemNormalizer = new ItemNormalizer(new class() implements NormalizerInterface {
            public function normalize(mixed $object, ?string $format = null, array $context = []): \ArrayObject|array|string|int|float|bool|null
            {
                return null;
            }

            public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function getSupportedTypes(?string $format): array
            {
                return ['*' => true];
            }
        });

        $this->assertEmpty($this->itemNormalizer->getSupportedTypes($this->itemNormalizer::FORMAT));
        $this->assertSame(['*' => true], $this->itemNormalizer->getSupportedTypes('json'));
    }
}
