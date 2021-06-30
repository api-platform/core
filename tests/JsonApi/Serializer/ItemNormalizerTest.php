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

namespace ApiPlatform\Tests\JsonApi\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\JsonApi\Serializer\ItemNormalizer;
use ApiPlatform\JsonApi\Serializer\ReservedAttributeNameConverter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CircularReference;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ItemNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testNormalize(): void
    {
        $dummy = new Dummy();
        $dummy->setId(10);
        $dummy->setName('hello');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->willReturn(new PropertyNameCollection(['id', 'name', '\bad_property']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::any())->willReturn((new ApiProperty())->withReadable(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id', Argument::any())->willReturn((new ApiProperty())->withReadable(true)->withIdentifier(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, '\bad_property', Argument::any())->willReturn((new ApiProperty())->withReadable(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, Argument::cetera())->willReturn('/dummies/10');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->isReadable($dummy, 'id')->willReturn(true);
        $propertyAccessorProphecy->isReadable($dummy, 'name')->willReturn(true);
        $propertyAccessorProphecy->getValue($dummy, 'id')->willReturn(10);
        $propertyAccessorProphecy->getValue($dummy, 'name')->willReturn('hello');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [
            (new ApiResource())
                ->withShortName('Dummy')
                ->withDescription('A dummy')
                ->withUriTemplate('/dummy')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('Dummy')])),
        ]));

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
            null,
            [],
            $resourceMetadataCollectionFactoryProphecy->reveal(),
        );

        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'data' => [
                'type' => 'Dummy',
                'id' => '/dummies/10',
                'attributes' => [
                    '_id' => 10,
                    'name' => 'hello',
                ],
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($dummy, ItemNormalizer::FORMAT));
    }

    public function testNormalizeCircularReference(): void
    {
        $circularReferenceEntity = new CircularReference();
        $circularReferenceEntity->id = 1;
        $circularReferenceEntity->parent = $circularReferenceEntity;

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($circularReferenceEntity, Argument::cetera())->willReturn('/circular_references/1');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(CircularReference::class)->willReturn(true);
        $resourceClassResolverProphecy->getResourceClass($circularReferenceEntity, null)->willReturn(CircularReference::class);
        $resourceClassResolverProphecy->getResourceClass($circularReferenceEntity, CircularReference::class)->willReturn(CircularReference::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(CircularReference::class)->willReturn(new ResourceMetadataCollection('CircularReference'));

        $normalizer = new ItemNormalizer(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            new ReservedAttributeNameConverter(),
            null,
            [],
            $resourceMetadataCollectionFactoryProphecy->reveal(),
        );

        $normalizer->setSerializer($this->prophesize(SerializerInterface::class)->reveal());

        $circularReferenceLimit = 2;
        if (!interface_exists(AdvancedNameConverterInterface::class) && method_exists($normalizer, 'setCircularReferenceLimit')) {
            $normalizer->setCircularReferenceLimit($circularReferenceLimit);

            $context = [
                'circular_reference_limit' => [spl_object_hash($circularReferenceEntity) => 2],
                'cache_error' => function (): void {},
            ];
        } else {
            $context = [
                'circular_reference_limit' => $circularReferenceLimit,
                'circular_reference_limit_counters' => [spl_object_hash($circularReferenceEntity) => 2],
                'cache_error' => function (): void {},
            ];
        }

        $this->assertSame('/circular_references/1', $normalizer->normalize($circularReferenceEntity, ItemNormalizer::FORMAT, $context));
    }

    public function testNormalizeNonExistentProperty(): void
    {
        $this->expectException(NoSuchPropertyException::class);

        $dummy = new Dummy();
        $dummy->setId(1);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->willReturn(new PropertyNameCollection(['bar']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'bar', Argument::any())->willReturn((new ApiProperty())->withReadable(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, Argument::cetera())->willReturn('/dummies/1');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->isReadable($dummy, 'bar')->willReturn(true);
        $propertyAccessorProphecy->getValue($dummy, 'bar')->willThrow(new NoSuchPropertyException());

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [
            (new ApiResource())
                ->withShortName('Dummy')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('Dummy')])),
        ]));

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            null,
            [],
            $resourceMetadataCollectionFactoryProphecy->reveal(),
        );

        $normalizer->normalize($dummy, ItemNormalizer::FORMAT);
    }

    public function testDenormalize(): void
    {
        $data = [
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
        ];

        $relatedDummy1 = new RelatedDummy();
        $relatedDummy1->setId(1);
        $relatedDummy2 = new RelatedDummy();
        $relatedDummy2->setId(2);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->willReturn(new PropertyNameCollection(['name', 'ghost', 'relatedDummy', 'relatedDummies']));

        $relatedDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);
        $relatedDummiesType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::any())->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'ghost', Argument::any())->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', Argument::any())->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummyType])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', Argument::any())->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummiesType])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false));

        $getItemFromIriSecondArgCallback = fn ($arg): bool => \is_array($arg) && isset($arg['fetch_data']) && true === $arg['fetch_data'];

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getResourceFromIri('/related_dummies/1', Argument::that($getItemFromIriSecondArgCallback))->willReturn($relatedDummy1);
        $iriConverterProphecy->getResourceFromIri('/related_dummies/2', Argument::that($getItemFromIriSecondArgCallback))->willReturn($relatedDummy2);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'name', 'foo')->will(function (): void {});
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'ghost', 'invisible')->willThrow(new NoSuchPropertyException());
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'relatedDummy', $relatedDummy1)->will(function (): void {});
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'relatedDummies', [$relatedDummy2])->will(function (): void {});

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([new Get(name: 'get')])),
        ]
        ));
        $resourceMetadataCollectionFactory->create(RelatedDummy::class)->willReturn(new ResourceMetadataCollection(RelatedDummy::class, [
            (new ApiResource())->withOperations(new Operations([new Get(name: 'get')])),
        ]
        ));

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            null,
            [],
            $resourceMetadataCollectionFactory->reveal(),
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertInstanceOf(Dummy::class, $normalizer->denormalize($data, Dummy::class, ItemNormalizer::FORMAT));
    }

    public function testDenormalizeUpdateOperationNotAllowed(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('Update is not allowed for this operation.');

        $normalizer = new ItemNormalizer(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
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

    public function testDenormalizeCollectionIsNotArray(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('The type of the "relatedDummies" attribute must be "array", "string" given.');

        $data = [
            'data' => [
                'type' => 'dummy',
                'relationships' => [
                    'relatedDummies' => [
                        'data' => 'foo',
                    ],
                ],
            ],
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummies']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn((new ApiProperty())
                ->withBuiltinTypes([$type])
                ->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            null,
            []
        );

        $normalizer->denormalize($data, Dummy::class, ItemNormalizer::FORMAT);
    }

    public function testDenormalizeCollectionWithInvalidKey(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('The type of the key "0" must be "string", "integer" given.');

        $data = [
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
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummies']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_STRING), new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([$type])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            null,
            []
        );

        $normalizer->denormalize($data, Dummy::class, ItemNormalizer::FORMAT);
    }

    public function testDenormalizeRelationIsNotResourceLinkage(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Only resource linkage supported currently, see: http://jsonapi.org/format/#document-resource-object-linkage.');

        $data = [
            'data' => [
                'type' => 'dummy',
                'relationships' => [
                    'relatedDummy' => [
                        'data' => 'foo',
                    ],
                ],
            ],
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummy']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([
                new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class), ])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            new ReservedAttributeNameConverter(),
            null,
            []
        );

        $normalizer->denormalize($data, Dummy::class, ItemNormalizer::FORMAT);
    }
}
