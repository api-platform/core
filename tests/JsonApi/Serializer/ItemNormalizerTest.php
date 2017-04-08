<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\JsonApi\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\JsonApi\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ItemNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     */
    public function testDonTSupportDenormalization()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceMetadataProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            $resourceMetadataProphecy->reveal()
        );

        $this->assertFalse($normalizer->supportsDenormalization('foo', ItemNormalizer::FORMAT));
        $normalizer->denormalize(['foo'], 'Foo');
    }

    public function testSupportNormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();
        $dummy->setDescription('hello');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy)->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass($std)->willThrow(new InvalidArgumentException())->shouldBeCalled();

        $resourceMetadataProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            $resourceMetadataProphecy->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization($dummy, ItemNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($dummy, 'xml'));
        $this->assertFalse($normalizer->supportsNormalization($std, ItemNormalizer::FORMAT));
    }

    public function testNormalize()
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection)->shouldBeCalled();

        $propertyMetadataFactory = new PropertyMetadata(null, null, true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadataFactory)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();

        $resourceMetadataProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello')->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            $resourceMetadataProphecy->reveal()
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'name' => 'hello',
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy));
    }
}
