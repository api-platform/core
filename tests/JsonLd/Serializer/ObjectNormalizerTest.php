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

namespace ApiPlatform\Tests\JsonLd\Serializer;

use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\JsonLd\Serializer\ObjectNormalizer;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ObjectNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testNormalize(): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize($dummy, null, Argument::type('array'))->willReturn(['name' => 'hello']);

        $contextBuilderProphecy = $this->prophesize(AnonymousContextBuilderInterface::class);
        $contextBuilderProphecy->getAnonymousResourceContext($dummy, Argument::type('array'))->shouldBeCalled()->willReturn([
            '@context' => [],
            '@type' => 'Dummy',
            '@id' => '_:1234',
        ]);

        $normalizer = new ObjectNormalizer(
            $serializerProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $contextBuilderProphecy->reveal()
        );

        $expected = [
            '@context' => [],
            '@id' => '_:1234',
            '@type' => 'Dummy',
            'name' => 'hello',
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy));
    }

    public function testNormalizeEmptyArray(): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize($dummy, null, Argument::type('array'))->willReturn([]);

        $contextBuilderProphecy = $this->prophesize(AnonymousContextBuilderInterface::class);
        $contextBuilderProphecy->getAnonymousResourceContext($dummy, Argument::type('array'))->shouldNotBeCalled();

        $normalizer = new ObjectNormalizer(
            $serializerProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $contextBuilderProphecy->reveal()
        );

        $this->assertEquals([], $normalizer->normalize($dummy));
    }

    public function testNormalizeWithOutput(): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy)->willReturn('/dummy/1234');

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize($dummy, null, Argument::type('array'))->willReturn(['name' => 'hello']);

        $contextBuilderProphecy = $this->prophesize(AnonymousContextBuilderInterface::class);
        $contextBuilderProphecy->getAnonymousResourceContext($dummy, ['api_resource' => $dummy, 'iri' => '/dummy/1234'])->shouldBeCalled()->willReturn(['@id' => '/dummy/1234', '@type' => 'Dummy', '@context' => []]);

        $normalizer = new ObjectNormalizer(
            $serializerProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $contextBuilderProphecy->reveal()
        );

        $expected = [
            '@context' => [],
            '@id' => '/dummy/1234',
            '@type' => 'Dummy',
            'name' => 'hello',
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy, null, ['api_resource' => $dummy]));
    }

    public function testNormalizeWithContext(): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy)->willReturn('/dummy/1234');

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize($dummy, null, Argument::type('array'))->willReturn(['name' => 'hello']);

        $contextBuilderProphecy = $this->prophesize(AnonymousContextBuilderInterface::class);
        $contextBuilderProphecy->getAnonymousResourceContext($dummy, ['api_resource' => $dummy, 'has_context' => true, 'iri' => '/dummy/1234'])->shouldBeCalled()->willReturn(['@id' => '/dummy/1234', '@type' => 'Dummy']);

        $normalizer = new ObjectNormalizer(
            $serializerProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $contextBuilderProphecy->reveal()
        );

        $expected = [
            '@id' => '/dummy/1234',
            '@type' => 'Dummy',
            'name' => 'hello',
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy, null, ['api_resource' => $dummy, 'jsonld_has_context' => true]));
    }
}
