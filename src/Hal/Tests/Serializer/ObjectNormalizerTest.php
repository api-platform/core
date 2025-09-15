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

namespace ApiPlatform\Tests\Hal\Serializer;

use ApiPlatform\Hal\Serializer\ObjectNormalizer;
use ApiPlatform\Hal\Tests\Fixtures\Dummy;
use ApiPlatform\Metadata\IriConverterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Tomasz Grochowski <tg@urias.it>
 */
class ObjectNormalizerTest extends TestCase
{
    public function testDoesNotSupportDenormalization(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('jsonhal is a read-only format.');

        $normalizerInterfaceMock = $this->createMock(NormalizerInterface::class);
        $iriConverterMock = $this->createMock(IriConverterInterface::class);

        $normalizer = new ObjectNormalizer(
            $normalizerInterfaceMock,
            $iriConverterMock
        );

        $this->assertFalse($normalizer->supportsDenormalization('foo', 'type', 'format'));
        $normalizer->denormalize(['foo'], 'Foo');
    }

    #[\PHPUnit\Framework\Attributes\Group('legacy')]
    public function testSupportsNormalization(): void
    {
        $std = new \stdClass();
        $dummy = new Dummy();

        $normalizerInterfaceMock = $this->createMock(NormalizerInterface::class);
        $iriConverterMock = $this->createMock(IriConverterInterface::class);
        $normalizer = new ObjectNormalizer(
            $normalizerInterfaceMock,
            $iriConverterMock
        );

        $normalizerInterfaceMock->method('supportsNormalization')->willReturn(true);

        $this->assertFalse($normalizer->supportsNormalization($dummy, 'xml'));
        $this->assertTrue($normalizer->supportsNormalization($std, ObjectNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsNormalization($dummy, ObjectNormalizer::FORMAT));
    }
}
