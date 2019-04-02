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
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\JsonApi\Serializer\ItemNormalizer;
use ApiPlatform\Core\JsonApi\Serializer\ReservedAttributeNameConverter;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CircularReference;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ItemNormalizerTest extends TestCase
{
    /**
     * @group legacy
     */
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
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    /**
     * @group legacy
     */
    public function testSupportNormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();

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
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['id', 'name', 'inherited', '\bad_property']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(new PropertyMetadata(null, null, true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id', [])->willReturn(new PropertyMetadata(null, null, true, null, null, null, null, true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'inherited', [])->willReturn(new PropertyMetadata(null, null, true, null, null, null, null, null, null, 'foo'));
        $propertyMetadataFactoryProphecy->create(Dummy::class, '\bad_property', [])->willReturn(new PropertyMetadata(null, null, true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/10');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class, true)->willReturn(Dummy::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'id')->willReturn(10);
        $propertyAccessorProphecy->getValue($dummy, 'name')->willReturn('hello');
        $propertyAccessorProphecy->getValue($dummy, 'inherited')->willThrow(new NoSuchPropertyException());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', 'A dummy', '/dummy', null, null, ['id', 'name']));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', ItemNormalizer::FORMAT, Argument::type('array'))->willReturn('hello');
        $serializerProphecy->normalize(10, ItemNormalizer::FORMAT, Argument::type('array'))->willReturn(10);
        $serializerProphecy->normalize(null, ItemNormalizer::FORMAT, Argument::type('array'))->willReturn(null);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            $resourceMetadataFactoryProphecy->reveal(),
            [],
            []
        );

        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'data' => [
                'type' => 'Dummy',
                'id' => '/dummies/10',
                'attributes' => [
                    '_id' => 10,
                    'name' => 'hello',
                    'inherited' => null,
                ],
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($dummy, ItemNormalizer::FORMAT));
    }

    public function testNormalizeCircularReference()
    {
        $circularReferenceEntity = new CircularReference();
        $circularReferenceEntity->id = 1;
        $circularReferenceEntity->parent = $circularReferenceEntity;

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($circularReferenceEntity)->willReturn('/circular_references/1');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($circularReferenceEntity, null, true)->willReturn(CircularReference::class);
        $resourceClassResolverProphecy->getResourceClass($circularReferenceEntity, CircularReference::class, true)->willReturn(CircularReference::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(CircularReference::class)->willReturn(new ResourceMetadata('CircularReference'));

        $normalizer = new ItemNormalizer(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            new ReservedAttributeNameConverter(),
            $resourceMetadataFactoryProphecy->reveal(),
            [],
            []
        );

        $normalizer->setSerializer($this->prophesize(SerializerInterface::class)->reveal());

        $circularReferenceLimit = 2;
        if (interface_exists(AdvancedNameConverterInterface::class)) {
            $context = [
                'circular_reference_limit' => $circularReferenceLimit,
                'circular_reference_limit_counters' => [spl_object_hash($circularReferenceEntity) => 2],
                'cache_error' => function () {},
            ];
        } else {
            $normalizer->setCircularReferenceLimit($circularReferenceLimit);

            $context = [
                'circular_reference_limit' => [spl_object_hash($circularReferenceEntity) => 2],
                'cache_error' => function () {},
            ];
        }

        $this->assertEquals('/circular_references/1', $normalizer->normalize($circularReferenceEntity, ItemNormalizer::FORMAT, $context));
    }

    public function testNormalizeNonExistentProperty()
    {
        $this->expectException(NoSuchPropertyException::class);

        $dummy = new Dummy();
        $dummy->setId(1);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['bar']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'bar', [])->willReturn(new PropertyMetadata(null, null, true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class, true)->willReturn(Dummy::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'bar')->willThrow(new NoSuchPropertyException());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy'));

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            $resourceMetadataFactoryProphecy->reveal(),
            [],
            []
        );

        $normalizer->normalize($dummy, ItemNormalizer::FORMAT);
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
            return \is_array($arg) && isset($arg['fetch_data']) && true === $arg['fetch_data'];
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

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willThrow(ResourceClassNotFoundException::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            $resourceMetadataFactory->reveal(),
            [],
            []
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

    public function testDenormalizeUpdateOperationNotAllowed()
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('Update is not allowed for this operation.');

        $normalizer = new ItemNormalizer(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            null,
            null,
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            [],
            []
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

    public function testDenormalizeCollectionIsNotArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The type of the "relatedDummies" attribute must be "array", "string" given.');

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

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willThrow(ResourceClassNotFoundException::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            new ReservedAttributeNameConverter(),
            $resourceMetadataFactory->reveal(),
            [],
            []
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

    public function testDenormalizeCollectionWithInvalidKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The type of the key "0" must be "string", "integer" given.');

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

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willThrow(ResourceClassNotFoundException::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            new ReservedAttributeNameConverter(),
            $resourceMetadataFactory->reveal(),
            [],
            []
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

    public function testDenormalizeRelationIsNotResourceLinkage()
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('Only resource linkage supported currently, see: http://jsonapi.org/format/#document-resource-object-linkage.');

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

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willThrow(ResourceClassNotFoundException::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            new ReservedAttributeNameConverter(),
            $resourceMetadataFactory->reveal(),
            [],
            []
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
