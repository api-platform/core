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

namespace ApiPlatform\Core\Tests\JsonApi\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\JsonApi\Serializer\ItemNormalizer;
use ApiPlatform\Core\JsonApi\Serializer\ReservedAttributeNameConverter;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ItemNormalizerTest extends TestCase
{
    public function testSupportDenormalization()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(\stdClass::class)->willReturn(false)->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );

        $this->assertTrue($normalizer->supportsDenormalization(null, Dummy::class, ItemNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsDenormalization(null, \stdClass::class, ItemNormalizer::FORMAT));
    }

    public function testSupportNormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy)->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass($std)->willThrow(new InvalidArgumentException())->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization($dummy, ItemNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($dummy, 'xml'));
        $this->assertFalse($normalizer->supportsNormalization($std, ItemNormalizer::FORMAT));
    }

    public function testNormalize()
    {
        $dummy = new Dummy();
        $dummy->setId(10);
        $dummy->setName('hello');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['id', 'name', 'inherited', '\bad_property']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(new PropertyMetadata(null, null, true))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id', [])->willReturn(new PropertyMetadata(null, null, true, null, null, null, null, true))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'inherited', [])->willReturn(new PropertyMetadata(null, null, true, null, null, null, null, null, null, 'foo'))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, '\bad_property', [])->willReturn(new PropertyMetadata(null, null, true))->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'id')->willReturn(10)->shouldBeCalled();
        $propertyAccessorProphecy->getValue($dummy, 'name')->willReturn('hello')->shouldBeCalled();
        $propertyAccessorProphecy->getValue($dummy, 'inherited')->willThrow(new NoSuchPropertyException())->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', 'A dummy', '/dummy', null, null, ['id', 'name']))->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello')->shouldBeCalled();
        $serializerProphecy->normalize(10, null, Argument::type('array'))->willReturn(10)->shouldBeCalled();
        $serializerProphecy->normalize(null, null, Argument::type('array'))->willReturn(null)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/10')->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            $resourceMetadataFactoryProphecy->reveal()
        );

        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertEquals(
            [
                'data' => [
                    'type' => 'Dummy',
                    'id' => '/dummies/10',
                    'attributes' => [
                        '_id' => 10,
                        'name' => 'hello',
                        'inherited' => null,
                    ],
                ],
            ],
            $normalizer->normalize($dummy)
        );
    }

    public function testNormalizeIsNotAnArray()
    {
        $object = new \stdClass();
        $object->object = $object;

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($object, null, true)->willReturn(\stdClass::class)->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            new ReservedAttributeNameConverter(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );

        $normalizer->setSerializer(new Serializer([$normalizer]));
        $normalizer->setCircularReferenceLimit(2);
        $normalizer->setCircularReferenceHandler(function () {
            return 'object';
        });

        $this->assertEquals('object', $normalizer->normalize(
            $object,
            ItemNormalizer::FORMAT,
            [
                'circular_reference_limit' => [spl_object_hash($object) => 2],
                'cache_error' => function () {},
            ]
        ));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testNormalizeThrowsNoSuchPropertyException()
    {
        $foo = new \stdClass();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(\stdClass::class, [])->willReturn(new PropertyNameCollection(['bar']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(\stdClass::class, 'bar', [])->willReturn(new PropertyMetadata(null, null, true))->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($foo, null, true)->willReturn(\stdClass::class)->shouldBeCalled();

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($foo, 'bar')->willThrow(new NoSuchPropertyException());

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );

        $normalizer->normalize($foo, ItemNormalizer::FORMAT);
    }

    public function testDenormalize()
    {
        $relatedDummy1 = new RelatedDummy();
        $relatedDummy1->setId(1);
        $relatedDummy2 = new RelatedDummy();
        $relatedDummy2->setId(2);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['name', 'ghost', 'relatedDummy', 'relatedDummies'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', false, true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'ghost', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', false, true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            new PropertyMetadata(
                new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class),
                '',
                false,
                true,
                false,
                false
            )
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(
            new PropertyMetadata(
                new Type(Type::BUILTIN_TYPE_OBJECT,
                    false,
                    ArrayCollection::class,
                    true,
                    new Type(Type::BUILTIN_TYPE_INT),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)
                ),
                '',
                false,
                true,
                false,
                false
            )
        )->shouldBeCalled();

        $getItemFromIriSecondArgCallback = function ($arg) {
            return is_array($arg) && isset($arg['fetch_data']) && true === $arg['fetch_data'];
        };

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('/related_dummies/1', Argument::that($getItemFromIriSecondArgCallback))->willReturn($relatedDummy1)->shouldBeCalled();
        $iriConverterProphecy->getItemFromIri('/related_dummies/2', Argument::that($getItemFromIriSecondArgCallback))->willReturn($relatedDummy2)->shouldBeCalled();

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'name', 'foo')->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'ghost', 'invisible')->willThrow(new NoSuchPropertyException())->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'relatedDummy', $relatedDummy1)->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'relatedDummies', [$relatedDummy2])->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertInstanceOf(
            Dummy::class,
            $normalizer->denormalize(
                [
                    'data' => [
                        'type' => 'dummy',
                        'attributes' => [
                            'name' => 'foo',
                            'ghost' => 'invisible',
                        ],
                        'relationships' => [
                            'relatedDummy' => [
                                'data' => [
                                    'type' => 'related-dummy',
                                    'id' => '/related_dummies/1',
                                ],
                            ],
                            'relatedDummies' => [
                                'data' => [
                                    [
                                        'type' => 'related-dummy',
                                        'id' => '/related_dummies/2',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                Dummy::class,
                ItemNormalizer::FORMAT
            )
        );
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Update is not allowed for this operation.
     */
    public function testDenormalizeUpdateOperationNotAllowed()
    {
        $normalizer = new ItemNormalizer(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            null,
            null,
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );

        $normalizer->denormalize(
            [
                'data' => [
                    'id' => 1,
                    'type' => 'dummy',
                ],
            ],
            Dummy::class,
            ItemNormalizer::FORMAT,
            [
                'api_allow_update' => false,
            ]
        );
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The type of the "relatedDummies" attribute must be "array", "string" given.
     */
    public function testDenormalizeCollectionIsNotArray()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummies']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(
            new PropertyMetadata(
                new Type(Type::BUILTIN_TYPE_OBJECT,
                    false,
                    ArrayCollection::class,
                    true,
                    new Type(Type::BUILTIN_TYPE_INT),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)
                ),
                '',
                false,
                true,
                false,
                false
            )
        )->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            new ReservedAttributeNameConverter(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );

        $normalizer->denormalize(
            [
                'data' => [
                    'type' => 'dummy',
                    'relationships' => [
                        'relatedDummies' => [
                            'data' => 'foo',
                        ],
                    ],
                ],
            ],
            Dummy::class,
            ItemNormalizer::FORMAT
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The type of the key "0" must be "string", "integer" given.
     */
    public function testDenormalizeCollectionWithInvalidKey()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummies']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(
            new PropertyMetadata(
                new Type(Type::BUILTIN_TYPE_OBJECT,
                    false,
                    ArrayCollection::class,
                    true,
                    new Type(Type::BUILTIN_TYPE_STRING),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)
                ),
                '',
                false,
                true,
                false,
                false
            )
        )->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            new ReservedAttributeNameConverter(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );

        $normalizer->denormalize(
            [
                'data' => [
                    'type' => 'dummy',
                    'relationships' => [
                        'relatedDummies' => [
                            'data' => [
                                [
                                    'type' => 'related-dummy',
                                    'id' => '2',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            Dummy::class,
            ItemNormalizer::FORMAT
        );
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Only resource linkage supported currently, see: http://jsonapi.org/format/#document-resource-object-linkage.
     */
    public function testDenormalizeRelationIsNotResourceLinkage()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummy']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            new PropertyMetadata(
                new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class),
                '',
                false,
                true,
                false,
                false
            )
        )->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true)->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            new ReservedAttributeNameConverter(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );

        $normalizer->denormalize(
            [
                'data' => [
                    'type' => 'dummy',
                    'relationships' => [
                        'relatedDummy' => [
                            'data' => 'foo',
                        ],
                    ],
                ],
            ],
            Dummy::class,
            ItemNormalizer::FORMAT
        );
    }
}
