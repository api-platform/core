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

namespace ApiPlatform\Core\Tests\GraphQl\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemNormalizerTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testSupportNormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();
        $dummy->setDescription('hello');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(\stdClass::class)->willReturn(false)->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization($dummy, ItemNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsNormalization($dummy, ItemNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($std, ItemNormalizer::FORMAT));

        $this->assertTrue($normalizer->supportsDenormalization($dummy, Dummy::class, ItemNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsDenormalization($dummy, Dummy::class, ItemNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsDenormalization($std, \stdClass::class, ItemNormalizer::FORMAT));
    }

    public function testNormalize()
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection);

        $propertyMetadata = new PropertyMetadata(null, null, true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadata);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', ItemNormalizer::FORMAT, Argument::type('array'))->willReturn('hello');

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            null,
            null,
            false,
            null,
            [],
            null
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'name' => 'hello',
            ItemNormalizer::ITEM_KEY => serialize($dummy),
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy, ItemNormalizer::FORMAT, [
            'resources' => [],
        ]));
    }

    public function testDenormalize()
    {
        $context = ['resource_class' => Dummy::class, 'api_allow_update' => true];

        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection)->shouldBeCalled();

        $propertyMetadata = new PropertyMetadata(null, null, true, true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            null,
            null,
            false,
            null,
            [],
            null
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertInstanceOf(Dummy::class, $normalizer->denormalize(['name' => 'hello'], Dummy::class, ItemNormalizer::FORMAT, $context));
    }
}
