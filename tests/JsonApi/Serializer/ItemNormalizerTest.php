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
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\JsonApi\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ItemNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportDenormalization()
    {
        $propertyNameCollectionFactoryProphecy = $this
            ->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this
            ->prophesize(PropertyMetadataFactoryInterface::class);

        $iriConverterProphecy = $this
            ->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this
            ->prophesize(ResourceClassResolverInterface::class);

        $resourceClassResolverProphecy
            ->isResourceClass(Dummy::class)
            ->willReturn(true)
            ->shouldBeCalled();

        $resourceClassResolverProphecy
            ->isResourceClass(\stdClass::class)
            ->willReturn(false)
            ->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this
            ->prophesize(ResourceMetadataFactoryInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            $resourceMetadataFactoryProphecy->reveal(),
            $this->prophesize(ItemDataProviderInterface::class)->reveal()
        );

        $this->assertTrue($normalizer->supportsDenormalization(null, Dummy::class, ItemNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsDenormalization(null, \stdClass::class, ItemNormalizer::FORMAT));
    }

    public function testSupportNormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();

        $propertyNameCollectionFactoryProphecy = $this
            ->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this
            ->prophesize(PropertyMetadataFactoryInterface::class);

        $iriConverterProphecy = $this
            ->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this
            ->prophesize(ResourceClassResolverInterface::class);

        $resourceClassResolverProphecy
            ->getResourceClass($dummy)
            ->willReturn(Dummy::class)
            ->shouldBeCalled();

        $resourceClassResolverProphecy
            ->getResourceClass($std)
            ->willThrow(new InvalidArgumentException())
            ->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this
            ->prophesize(ResourceMetadataFactoryInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            $resourceMetadataFactoryProphecy->reveal(),
            $this->prophesize(ItemDataProviderInterface::class)->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization($dummy, ItemNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($dummy, 'xml'));
        $this->assertFalse($normalizer->supportsNormalization($std, ItemNormalizer::FORMAT));
    }

    public function testNormalize()
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $propertyNameCollectionFactoryProphecy = $this
            ->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyNameCollectionFactoryProphecy
            ->create(Dummy::class, [])
            ->willReturn(new PropertyNameCollection(['name']))
            ->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this
            ->prophesize(PropertyMetadataFactoryInterface::class);

        $propertyMetadataFactoryProphecy
            ->create(Dummy::class, 'name', [])
            ->willReturn(new PropertyMetadata(null, null, true))
            ->shouldBeCalled();

        $propertyMetadataFactoryProphecy
            ->create(Dummy::class, 'name')
            ->willReturn(new PropertyMetadata(null, null, true))
            ->shouldBeCalled();

        $resourceClassResolverProphecy = $this
            ->prophesize(ResourceClassResolverInterface::class);

        $resourceClassResolverProphecy
            ->getResourceClass($dummy, null, true)
            ->willReturn(Dummy::class)
            ->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this
            ->prophesize(ResourceMetadataFactoryInterface::class);

        $resourceMetadataFactoryProphecy
            ->create(Dummy::class)
            ->willReturn(new ResourceMetadata(
                'Dummy', 'A dummy', '/dummy', null, null, ['id', 'name']
            ))
            ->shouldBeCalled();

        $serializerProphecy = $this
            ->prophesize(SerializerInterface::class);

        $serializerProphecy->willImplement(NormalizerInterface::class);

        $serializerProphecy
            ->normalize('hello', null, Argument::type('array'))
            ->willReturn('hello')
            ->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            $resourceMetadataFactoryProphecy->reveal(),
            $this->prophesize(ItemDataProviderInterface::class)->reveal()
        );

        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'data' => [
                'type' => 'Dummy',
                'id' => null,
                'attributes' => ['name' => 'hello'],
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($dummy));
    }

    // TODO: Add metho to testDenormalize
}
