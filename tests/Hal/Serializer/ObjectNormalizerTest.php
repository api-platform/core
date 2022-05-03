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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Tests\ProphecyTrait;
use ApiPlatform\Hal\Serializer\ObjectNormalizer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Tomasz Grochowski <tg@urias.it>
 */
class ObjectNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testDoesNotSupportDenormalization()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('jsonhal is a read-only format.');

        $normalizerInterfaceProphecy = $this->prophesize(NormalizerInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $normalizer = new ObjectNormalizer(
            $normalizerInterfaceProphecy->reveal(),
            $iriConverterProphecy->reveal()
        );

        $this->assertFalse($normalizer->supportsDenormalization('foo', 'type', 'format'));
        $normalizer->denormalize(['foo'], 'Foo');
    }

    public function testSupportsNormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();
        $dummy->setDescription('hello');

        $normalizerInterfaceProphecy = $this->prophesize(NormalizerInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $normalizer = new ObjectNormalizer(
            $normalizerInterfaceProphecy->reveal(),
            $iriConverterProphecy->reveal()
        );

        $normalizerInterfaceProphecy->supportsNormalization($dummy, 'xml', [])->willReturn(true);
        $normalizerInterfaceProphecy->supportsNormalization($std, ObjectNormalizer::FORMAT, [])->willReturn(true);
        $normalizerInterfaceProphecy->supportsNormalization($dummy, ObjectNormalizer::FORMAT, [])->willReturn(true);

        $this->assertFalse($normalizer->supportsNormalization($dummy, 'xml'));
        $this->assertTrue($normalizer->supportsNormalization($std, ObjectNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsNormalization($dummy, ObjectNormalizer::FORMAT));
    }
}
