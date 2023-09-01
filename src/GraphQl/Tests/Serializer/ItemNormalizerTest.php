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

namespace ApiPlatform\GraphQl\Tests\Serializer;

use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\SecuredDummy;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportNormalization(): void
    {
        $std = new \stdClass();
        $dummy = new Dummy();
        $dummy->setDescription('hello');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(\stdClass::class)->willReturn(false)->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization($dummy, ItemNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsNormalization($dummy, ItemNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($std, ItemNormalizer::FORMAT));

        $this->assertTrue($normalizer->supportsDenormalization($dummy, Dummy::class, ItemNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsDenormalization($dummy, Dummy::class, ItemNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsDenormalization($std, \stdClass::class, ItemNormalizer::FORMAT));
    }

    public function testNormalize(): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection);

        $propertyMetadata = (new ApiProperty())->withReadable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadata);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, UrlGeneratorInterface::ABS_URL, Argument::any(), Argument::type('array'))->willReturn('/dummies/1');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($dummy, Argument::any())->willReturn(['id' => 1])->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', ItemNormalizer::FORMAT, Argument::type('array'))->willReturn('hello');

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'name' => 'hello',
            ItemNormalizer::ITEM_RESOURCE_CLASS_KEY => Dummy::class,
            ItemNormalizer::ITEM_IDENTIFIERS_KEY => [
                'id' => 1,
            ],
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy, ItemNormalizer::FORMAT, [
            'resources' => [],
            'resource_class' => Dummy::class,
        ]));
    }

    public function testNormalizeWithUnsafeCacheProperty(): void
    {
        $securedDummyWithOwnerOnlyPropertyAllowed = new SecuredDummy();
        $securedDummyWithOwnerOnlyPropertyAllowed->setTitle('hello');
        $securedDummyWithOwnerOnlyPropertyAllowed->setOwnerOnlyProperty('ownerOnly');
        $securedDummyWithoutOwnerOnlyPropertyAllowed = clone $securedDummyWithOwnerOnlyPropertyAllowed;
        $securedDummyWithoutOwnerOnlyPropertyAllowed->setTitle('hello from secured dummy');

        $propertyNameCollection = new PropertyNameCollection(['title', 'ownerOnlyProperty']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(SecuredDummy::class, [])->willReturn($propertyNameCollection);

        $unsecuredPropertyMetadata = (new ApiProperty())->withReadable(true);
        $securedPropertyMetadata = (new ApiProperty())->withReadable(true)->withSecurity('object == null or object.getOwner() == user');
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'title', [])->willReturn($unsecuredPropertyMetadata);
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'ownerOnlyProperty', [])->willReturn($securedPropertyMetadata);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($securedDummyWithOwnerOnlyPropertyAllowed, UrlGeneratorInterface::ABS_URL, Argument::any(), Argument::type('array'))->willReturn('/dummies/1');
        $iriConverterProphecy->getIriFromResource($securedDummyWithoutOwnerOnlyPropertyAllowed, UrlGeneratorInterface::ABS_URL, Argument::any(), Argument::type('array'))->willReturn('/dummies/2');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($securedDummyWithOwnerOnlyPropertyAllowed, Argument::any())->willReturn(['id' => 1])->shouldBeCalled();
        $identifiersExtractorProphecy->getIdentifiersFromItem($securedDummyWithoutOwnerOnlyPropertyAllowed, Argument::any())->willReturn(['id' => 2])->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($securedDummyWithOwnerOnlyPropertyAllowed, null)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->getResourceClass($securedDummyWithoutOwnerOnlyPropertyAllowed, null)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, SecuredDummy::class)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->isResourceClass(SecuredDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', ItemNormalizer::FORMAT, Argument::type('array'))->willReturn('hello');
        $serializerProphecy->normalize('hello from secured dummy', ItemNormalizer::FORMAT, Argument::type('array'))->willReturn('hello from secured dummy');
        $serializerProphecy->normalize('ownerOnly', ItemNormalizer::FORMAT, Argument::type('array'))->willReturn('ownerOnly');

        $resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessCheckerProphecy->isGranted(
            SecuredDummy::class,
            'object == null or object.getOwner() == user',
            Argument::type('array')
        )->will(function (array $args) {
            return 'hello' === $args[2]['object']->getTitle(); // Allow access only for securedDummyWithOwnerOnlyPropertyAllowed
        });

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            null,
            null,
            null,
            $resourceAccessCheckerProphecy->reveal()
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'title' => 'hello',
            'ownerOnlyProperty' => 'ownerOnly',
            ItemNormalizer::ITEM_RESOURCE_CLASS_KEY => SecuredDummy::class,
            ItemNormalizer::ITEM_IDENTIFIERS_KEY => [
                'id' => 1,
            ],
        ];
        $this->assertEquals($expected, $normalizer->normalize($securedDummyWithOwnerOnlyPropertyAllowed, ItemNormalizer::FORMAT, [
            'resources' => [],
            'resource_class' => SecuredDummy::class,
        ]));

        $expected = [
            'title' => 'hello from secured dummy',
            ItemNormalizer::ITEM_RESOURCE_CLASS_KEY => SecuredDummy::class,
            ItemNormalizer::ITEM_IDENTIFIERS_KEY => [
                'id' => 2,
            ],
        ];
        $this->assertEquals($expected, $normalizer->normalize($securedDummyWithoutOwnerOnlyPropertyAllowed, ItemNormalizer::FORMAT, [
            'resources' => [],
            'resource_class' => SecuredDummy::class,
        ]));
    }

    public function testNormalizeNoResolverData(): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection);

        $propertyMetadata = (new ApiProperty())->withWritable(true)->withReadable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadata);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, UrlGeneratorInterface::ABS_URL, Argument::any(), Argument::type('array'))->willReturn('/dummies/1');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', ItemNormalizer::FORMAT, Argument::type('array'))->willReturn('hello');

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'name' => 'hello',
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy, ItemNormalizer::FORMAT, [
            'resources' => [],
            'no_resolver_data' => true,
        ]));
    }

    public function testDenormalize(): void
    {
        $context = ['resource_class' => Dummy::class, 'api_allow_update' => true];

        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection)->shouldBeCalled();

        $propertyMetadata = (new ApiProperty())->withWritable(true)->withReadable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertInstanceOf(Dummy::class, $normalizer->denormalize(['name' => 'hello'], Dummy::class, ItemNormalizer::FORMAT, $context));
    }
}
