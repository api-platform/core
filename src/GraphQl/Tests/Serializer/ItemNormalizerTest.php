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
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\MercureSubscriptionChildDummy;
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\SecuredDummy;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\Pagination\Pagination;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\TypeInfo\Type;

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
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::type('array'))->willReturn($propertyNameCollection);

        $propertyMetadata = (new ApiProperty())->withReadable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::type('array'))->willReturn($propertyMetadata);

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
        $propertyNameCollectionFactoryProphecy->create(SecuredDummy::class, Argument::type('array'))->willReturn($propertyNameCollection);

        $unsecuredPropertyMetadata = (new ApiProperty())->withReadable(true);
        $securedPropertyMetadata = (new ApiProperty())->withReadable(true)->withSecurity('object == null or object.getOwner() == user');
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'title', Argument::type('array'))->willReturn($unsecuredPropertyMetadata);
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'ownerOnlyProperty', Argument::type('array'))->willReturn($securedPropertyMetadata);

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
        )->will(static function (array $args) {
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
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::type('array'))->willReturn($propertyNameCollection);

        $propertyMetadata = (new ApiProperty())->withWritable(true)->withReadable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::type('array'))->willReturn($propertyMetadata);

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

    public static function subscriptionOperations(): iterable
    {
        foreach ([new Subscription(name: 'renamed_event'), new SubscriptionCollection(name: 'renamed_event')] as $operation) {
            foreach ([
                'default limit' => [null, [], ['alpha', 'beta', 'gamma'], 30, false, 1],
                'application limit' => [null, ['items_per_page' => 2], ['alpha', 'beta'], 2, true, 2],
                'operation override' => [1, ['items_per_page' => 2], ['alpha'], 1, true, 3],
                'exact limit' => [3, [], ['alpha', 'beta', 'gamma'], 3, false, 1],
                'zero limit' => [0, [], [], 0, true, 1],
            ] as $name => $case) {
                yield $operation::class.' '.$name => [$operation, ...$case];
            }
        }
    }

    #[DataProvider('subscriptionOperations')]
    public function testNormalizeMercureSubscriptionNestedCollectionRelations(Subscription $operation, ?int $operationLimit, array $paginationOptions, array $expectedNames, int $expectedLimit, bool $hasMore, int $lastPage): void
    {
        $firstChild = new MercureSubscriptionChildDummy();
        $firstChild->setName('alpha');
        $secondChild = new MercureSubscriptionChildDummy();
        $secondChild->setName('beta');
        $thirdChild = new MercureSubscriptionChildDummy();
        $thirdChild->setName('gamma');

        $children = new ArrayCollection([$firstChild, $secondChild, $thirdChild]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(MercureSubscriptionChildDummy::class, Argument::type('array'))->willReturn(new PropertyNameCollection(['name']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(MercureSubscriptionChildDummy::class, 'name', Argument::type('array'))->willReturn(
            (new ApiProperty())
                ->withNativeType(Type::string())
                ->withReadable(true)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(MercureSubscriptionChildDummy::class)->willReturn(new ResourceMetadataCollection(MercureSubscriptionChildDummy::class, [
            (new ApiResource())->withGraphQlOperations([
                'collection_query' => (new QueryCollection(paginationItemsPerPage: $operationLimit))->withName('collection_query')->withClass(MercureSubscriptionChildDummy::class),
            ]),
        ]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, MercureSubscriptionChildDummy::class)->willReturn(MercureSubscriptionChildDummy::class);
        $resourceClassResolverProphecy->getResourceClass($firstChild, null)->willReturn(MercureSubscriptionChildDummy::class);
        $resourceClassResolverProphecy->getResourceClass($secondChild, null)->willReturn(MercureSubscriptionChildDummy::class);
        $resourceClassResolverProphecy->getResourceClass($thirdChild, null)->willReturn(MercureSubscriptionChildDummy::class);
        $resourceClassResolverProphecy->isResourceClass(MercureSubscriptionChildDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        foreach (['alpha', 'beta', 'gamma'] as $name) {
            $prediction = $serializerProphecy->normalize($name, ItemNormalizer::FORMAT, Argument::type('array'));
            if (\in_array($name, $expectedNames, true)) {
                $prediction->willReturn($name)->shouldBeCalled();
            } else {
                $prediction->shouldNotBeCalled();
            }
        }

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
            $resourceMetadataCollectionFactoryProphecy->reveal(),
            pagination: new Pagination($paginationOptions)
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $relationProperty = (new ApiProperty())
            ->withNativeType(Type::collection(Type::object(ArrayCollection::class), Type::object(MercureSubscriptionChildDummy::class), Type::int()))
            ->withReadable(true)
            ->withWritable(false)
            ->withReadableLink(true);

        $normalizeCollectionOfRelations = \Closure::bind(
            static fn (ItemNormalizer $normalizer, ApiProperty $property, iterable $attributeValue, string $resourceClass, ?string $format, array $context): array => $normalizer->normalizeCollectionOfRelations($property, $attributeValue, $resourceClass, $format, $context),
            null,
            ItemNormalizer::class
        );

        $this->assertSame([
            'collection' => array_map(static fn (string $name): array => ['name' => $name], $expectedNames),
            'paginationInfo' => [
                'hasNextPage' => $hasMore,
                'itemsPerPage' => $expectedLimit,
                'lastPage' => $lastPage,
                'totalCount' => 3,
            ],
        ], $normalizeCollectionOfRelations(
            $normalizer,
            $relationProperty,
            $children,
            MercureSubscriptionChildDummy::class,
            ItemNormalizer::FORMAT,
            [
                'root_operation' => $operation,
                'attributes' => [
                    'collection' => ['name' => true],
                    'paginationInfo' => [
                        'hasNextPage' => true,
                        'itemsPerPage' => true,
                        'lastPage' => true,
                        'totalCount' => true,
                    ],
                ],
                'pagination' => ['itemsPerPage' => 99],
                'filters' => ['page' => 2, 'first' => 1, 'after' => base64_encode('1')],
                'no_resolver_data' => true,
            ]
        ));
    }

    public function testDenormalize(): void
    {
        $context = ['resource_class' => Dummy::class, 'api_allow_update' => true];

        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::type('array'))->willReturn($propertyNameCollection)->shouldBeCalled();

        $propertyMetadata = (new ApiProperty())->withWritable(true)->withReadable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::type('array'))->willReturn($propertyMetadata)->shouldBeCalled();

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
