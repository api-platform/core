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

namespace ApiPlatform\Serializer\Tests;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\DtoWithNullValue;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\DummyTableInheritance;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\DummyTableInheritanceChild;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\DummyTableInheritanceRelated;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\NonCloneableDummy;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\PropertyCollectionIriOnly;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\PropertyCollectionIriOnlyRelation;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\RelatedDummy;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\SecuredDummy;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AbstractItemNormalizerTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    /**
     * @group legacy
     */
    public function testSupportNormalizationAndSupportDenormalization(): void
    {
        $std = new \stdClass();
        $dummy = new Dummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(\stdClass::class)->willReturn(false);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);

        $this->assertTrue($normalizer->supportsNormalization($dummy));
        $this->assertFalse($normalizer->supportsNormalization($std));
        $this->assertTrue($normalizer->supportsDenormalization($dummy, Dummy::class));
        $this->assertFalse($normalizer->supportsDenormalization($std, \stdClass::class));
        $this->assertFalse($normalizer->supportsNormalization([]));
        $this->assertSame(['object' => true], $normalizer->getSupportedTypes('any'));

        if (!method_exists(Serializer::class, 'getSupportedTypes')) {
            $this->assertTrue($normalizer->hasCacheableSupportsMethod());
        }
    }

    public function testNormalize(): void
    {
        $relatedDummy = new RelatedDummy();

        $dummy = new Dummy();
        $dummy->setName('foo');
        $dummy->setAlias('ignored');
        $dummy->setRelatedDummy($relatedDummy);
        $dummy->relatedDummies->add(new RelatedDummy());

        $relatedDummies = new ArrayCollection([$relatedDummy]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['name', 'alias', 'relatedDummy', 'relatedDummies']));

        $relatedDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);
        $relatedDummiesType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'alias', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummyType])->withDescription('')->withReadable(true)->withWritable(false)->withReadableLink(false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummiesType])->withReadable(true)->withWritable(false)->withReadableLink(false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, Argument::cetera())->willReturn('/dummies/1');
        $iriConverterProphecy->getIriFromResource($relatedDummy, Argument::cetera())->willReturn('/dummies/2');

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'name')->willReturn('foo');
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummy')->willReturn($relatedDummy);
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummies')->willReturn($relatedDummies);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummy, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummies, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('foo', null, Argument::type('array'))->willReturn('foo');
        $serializerProphecy->normalize(['/dummies/2'], null, Argument::type('array'))->willReturn(['/dummies/2']);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'name' => 'foo',
            'relatedDummy' => '/dummies/2',
            'relatedDummies' => ['/dummies/2'],
        ];
        $this->assertSame($expected, $normalizer->normalize($dummy, null, [
            'resources' => [],
            'ignored_attributes' => ['alias'],
        ]));
    }

    public function testNormalizeWithSecuredProperty(): void
    {
        $dummy = new SecuredDummy();
        $dummy->setTitle('myPublicTitle');
        $dummy->setAdminOnlyProperty('secret');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(SecuredDummy::class, [])->willReturn(new PropertyNameCollection(['title', 'adminOnlyProperty']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'title', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true));
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'adminOnlyProperty', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)->withSecurity('is_granted(\'ROLE_ADMIN\')'));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, Argument::cetera())->willReturn('/secured_dummies/1');

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'title')->willReturn('myPublicTitle');
        $propertyAccessorProphecy->getValue($dummy, 'adminOnlyProperty')->willReturn('secret');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, SecuredDummy::class)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->isResourceClass(SecuredDummy::class)->willReturn(true);

        $resourceAccessChecker = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->isGranted(
            SecuredDummy::class,
            'is_granted(\'ROLE_ADMIN\')',
            ['object' => $dummy]
        )->willReturn(false);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('myPublicTitle', null, Argument::type('array'))->willReturn('myPublicTitle');

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            $resourceAccessChecker->reveal(),
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'title' => 'myPublicTitle',
        ];
        $this->assertSame($expected, $normalizer->normalize($dummy, null, [
            'resources' => [],
        ]));
    }

    public function testNormalizePropertyAsIriWithUriTemplate(): void
    {
        $propertyCollectionIriOnlyRelation = new PropertyCollectionIriOnlyRelation();
        $propertyCollectionIriOnlyRelation->name = 'My Relation';

        $propertyCollectionIriOnly = new PropertyCollectionIriOnly();
        $propertyCollectionIriOnly->addPropertyCollectionIriOnlyRelation($propertyCollectionIriOnlyRelation);

        $collectionOperation = new GetCollection('/property-collection-relations');
        $getIterableOperation = new GetCollection('/parent/{parentId}/another-collection-operations');
        $getToOneOperation = new Get('/parent/{parentId}/another-collection-operations/{id}');

        $resourceRelationMetadataCollection = new ResourceMetadataCollection(PropertyCollectionIriOnlyRelation::class, [
            (new ApiResource())->withOperations(new Operations([$collectionOperation, $getIterableOperation, $getToOneOperation])),
        ]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(PropertyCollectionIriOnlyRelation::class)->willReturn($resourceRelationMetadataCollection);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(PropertyCollectionIriOnly::class, ['normalization_groups' => null, 'denormalization_groups' => null, 'operation_name' => null])->willReturn(
            new PropertyNameCollection(['propertyCollectionIriOnlyRelation', 'iterableIri', 'toOneRelation'])
        );

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(PropertyCollectionIriOnly::class, 'propertyCollectionIriOnlyRelation', ['normalization_groups' => null, 'denormalization_groups' => null, 'operation_name' => null])->willReturn(
            (new ApiProperty())->withReadable(true)->withUriTemplate('/property-collection-relations')->withBuiltinTypes([
                new Type('iterable', false, null, true, new Type('int', false, null, false), new Type('object', false, PropertyCollectionIriOnlyRelation::class, false)),
            ])
        );

        $propertyMetadataFactoryProphecy->create(PropertyCollectionIriOnly::class, 'iterableIri', ['normalization_groups' => null, 'denormalization_groups' => null, 'operation_name' => null])->willReturn(
            (new ApiProperty())->withReadable(true)->withUriTemplate('/parent/{parentId}/another-collection-operations')->withBuiltinTypes([
                new Type('iterable', false, null, true, new Type('int', false, null, false), new Type('object', false, PropertyCollectionIriOnlyRelation::class, false)),
            ])
        );

        $propertyMetadataFactoryProphecy->create(PropertyCollectionIriOnly::class, 'toOneRelation', ['normalization_groups' => null, 'denormalization_groups' => null, 'operation_name' => null])->willReturn(
            (new ApiProperty())->withReadable(true)->withUriTemplate('/parent/{parentId}/another-collection-operations/{id}')->withBuiltinTypes([
                new Type('object', false, PropertyCollectionIriOnlyRelation::class, false),
            ])
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($propertyCollectionIriOnly, UrlGeneratorInterface::ABS_URL, null, Argument::any())->willReturn('/property-collection-relations', '/parent/42/another-collection-operations');
        $iriConverterProphecy->getIriFromResource($propertyCollectionIriOnly, UrlGeneratorInterface::ABS_PATH, Argument::type(GetCollection::class), Argument::any())->willReturn('/property-collection-relations', '/parent/42/another-collection-operations');
        $iriConverterProphecy->getIriFromResource($propertyCollectionIriOnly, UrlGeneratorInterface::ABS_PATH, Argument::type(Get::class), Argument::any())->willReturn('/parent/42/another-collection-operations/24');

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($propertyCollectionIriOnly, 'propertyCollectionIriOnlyRelation')->willReturn([$propertyCollectionIriOnlyRelation]);
        $propertyAccessorProphecy->getValue($propertyCollectionIriOnly, 'iterableIri')->willReturn($propertyCollectionIriOnlyRelation);
        $propertyAccessorProphecy->getValue($propertyCollectionIriOnly, 'toOneRelation')->willReturn($propertyCollectionIriOnlyRelation);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $resourceClassResolverProphecy->isResourceClass(PropertyCollectionIriOnly::class)->willReturn(true);
        $resourceClassResolverProphecy->getResourceClass(null, PropertyCollectionIriOnly::class)->willReturn(PropertyCollectionIriOnly::class);

        $resourceClassResolverProphecy->isResourceClass(PropertyCollectionIriOnlyRelation::class)->willReturn(true);
        $resourceClassResolverProphecy->getResourceClass([$propertyCollectionIriOnlyRelation], PropertyCollectionIriOnlyRelation::class)->willReturn(PropertyCollectionIriOnlyRelation::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            new PropertyAccessor(), // $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            $resourceMetadataCollectionFactoryProphecy->reveal(),
            null,
        ]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'propertyCollectionIriOnlyRelation' => '/property-collection-relations',
            'iterableIri' => '/parent/42/another-collection-operations',
            'toOneRelation' => '/parent/42/another-collection-operations/24',
        ];

        $this->assertSame($expected, $normalizer->normalize($propertyCollectionIriOnly, 'jsonld', [
            'resources' => [],
            'root_operation' => new GetCollection('/property_collection_iri_onlies'),
        ]));
    }

    public function testDenormalizeWithSecuredProperty(): void
    {
        $data = [
            'title' => 'foo',
            'adminOnlyProperty' => 'secret',
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(SecuredDummy::class, [])->willReturn(new PropertyNameCollection(['title', 'adminOnlyProperty']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'title', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'adminOnlyProperty', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)->withSecurity('is_granted(\'ROLE_ADMIN\')'));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, SecuredDummy::class)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->isResourceClass(SecuredDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $resourceAccessChecker = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->isGranted(
            SecuredDummy::class,
            'is_granted(\'ROLE_ADMIN\')',
            ['object' => null]
        )->willReturn(false);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            $resourceAccessChecker->reveal(),
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, SecuredDummy::class);

        $this->assertInstanceOf(SecuredDummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'title', 'foo')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'adminOnlyProperty', 'secret')->shouldNotHaveBeenCalled();
    }

    public function testDenormalizeCreateWithDeniedPostDenormalizeSecuredProperty(): void
    {
        $data = [
            'title' => 'foo',
            'ownerOnlyProperty' => 'should not be set',
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(SecuredDummy::class, [])->willReturn(new PropertyNameCollection(['title', 'ownerOnlyProperty']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'title', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'ownerOnlyProperty', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)->withWritable(true)->withSecurityPostDenormalize('false')->withDefault(''));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, SecuredDummy::class)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->isResourceClass(SecuredDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $resourceAccessChecker = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->isGranted(
            SecuredDummy::class,
            'false',
            Argument::any()
        )->willReturn(false);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            $resourceAccessChecker->reveal(),
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, SecuredDummy::class);

        $this->assertInstanceOf(SecuredDummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'title', 'foo')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'ownerOnlyProperty', 'should not be set')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'ownerOnlyProperty', '')->shouldHaveBeenCalled();
    }

    public function testDenormalizeUpdateWithSecuredProperty(): void
    {
        $dummy = new SecuredDummy();

        $data = [
            'title' => 'foo',
            'ownerOnlyProperty' => 'secret',
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(SecuredDummy::class, [])->willReturn(new PropertyNameCollection(['title', 'ownerOnlyProperty']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'title', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'ownerOnlyProperty', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)->withWritable(true)->withSecurity('true'));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, SecuredDummy::class)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, SecuredDummy::class)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->isResourceClass(SecuredDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $resourceAccessChecker = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->isGranted(
            SecuredDummy::class,
            'true',
            ['object' => null]
        )->willReturn(true);
        $resourceAccessChecker->isGranted(
            SecuredDummy::class,
            'true',
            ['object' => $dummy]
        )->willReturn(true);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            $resourceAccessChecker->reveal(),
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $context = [AbstractItemNormalizer::OBJECT_TO_POPULATE => $dummy];
        $actual = $normalizer->denormalize($data, SecuredDummy::class, null, $context);

        $this->assertInstanceOf(SecuredDummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'title', 'foo')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'ownerOnlyProperty', 'secret')->shouldHaveBeenCalled();
    }

    public function testDenormalizeUpdateWithDeniedSecuredProperty(): void
    {
        $dummy = new SecuredDummy();
        $dummy->setOwnerOnlyProperty('secret');

        $data = [
            'title' => 'foo',
            'ownerOnlyProperty' => 'should not be set',
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(SecuredDummy::class, [])->willReturn(new PropertyNameCollection(['title', 'ownerOnlyProperty']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'title', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'ownerOnlyProperty', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)->withWritable(true)->withSecurity('false'));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, SecuredDummy::class)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, SecuredDummy::class)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->isResourceClass(SecuredDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $resourceAccessChecker = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->isGranted(
            SecuredDummy::class,
            'false',
            ['object' => null]
        )->willReturn(false);
        $resourceAccessChecker->isGranted(
            SecuredDummy::class,
            'false',
            ['object' => $dummy]
        )->willReturn(false);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            $resourceAccessChecker->reveal(),
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $context = [AbstractItemNormalizer::OBJECT_TO_POPULATE => $dummy];
        $actual = $normalizer->denormalize($data, SecuredDummy::class, null, $context);

        $this->assertInstanceOf(SecuredDummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'title', 'foo')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'ownerOnlyProperty', 'should not be set')->shouldNotHaveBeenCalled();
    }

    public function testDenormalizeUpdateWithDeniedPostDenormalizeSecuredProperty(): void
    {
        $dummy = new SecuredDummy();
        $dummy->setOwnerOnlyProperty('secret');

        $data = [
            'title' => 'foo',
            'ownerOnlyProperty' => 'should not be set',
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(SecuredDummy::class, [])->willReturn(new PropertyNameCollection(['title', 'ownerOnlyProperty']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'title', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(SecuredDummy::class, 'ownerOnlyProperty', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)->withWritable(true)->withSecurityPostDenormalize('false'));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'ownerOnlyProperty')->willReturn('secret');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, SecuredDummy::class)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, SecuredDummy::class)->willReturn(SecuredDummy::class);
        $resourceClassResolverProphecy->isResourceClass(SecuredDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $resourceAccessChecker = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->isGranted(
            SecuredDummy::class,
            'false',
            ['object' => $dummy, 'previous_object' => $dummy]
        )->willReturn(false);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            $resourceAccessChecker->reveal(),
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $context = [AbstractItemNormalizer::OBJECT_TO_POPULATE => $dummy];
        $actual = $normalizer->denormalize($data, SecuredDummy::class, null, $context);

        $this->assertInstanceOf(SecuredDummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'title', 'foo')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'ownerOnlyProperty', 'should not be set')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'ownerOnlyProperty', 'secret')->shouldHaveBeenCalled();
    }

    public function testNormalizeReadableLinks(): void
    {
        $relatedDummy = new RelatedDummy();

        $dummy = new Dummy();
        $dummy->setRelatedDummy($relatedDummy);
        $dummy->relatedDummies->add(new RelatedDummy());

        $relatedDummies = new ArrayCollection([$relatedDummy]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummy', 'relatedDummies']));

        $relatedDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);
        $relatedDummiesType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummyType])->withReadable(true)->withWritable(false)->withReadableLink(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummiesType])->withReadable(true)->withWritable(false)->withReadableLink(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, Argument::cetera())->willReturn('/dummies/1');

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummy')->willReturn($relatedDummy);
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummies')->willReturn($relatedDummies);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummy, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummies, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $relatedDummyChildContext = Argument::allOf(
            Argument::type('array'),
            Argument::withEntry('resource_class', RelatedDummy::class),
            Argument::not(Argument::withKey('iri')),
            Argument::not(Argument::withKey('force_resource_class'))
        );
        $serializerProphecy->normalize($relatedDummy, null, $relatedDummyChildContext)->willReturn(['foo' => 'hello']);
        $serializerProphecy->normalize(['foo' => 'hello'], null, Argument::type('array'))->willReturn(['foo' => 'hello']);
        $serializerProphecy->normalize([['foo' => 'hello']], null, Argument::type('array'))->willReturn([['foo' => 'hello']]);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'relatedDummy' => ['foo' => 'hello'],
            'relatedDummies' => [['foo' => 'hello']],
        ];
        $this->assertSame($expected, $normalizer->normalize($dummy, null, [
            'resources' => [],
            'force_resource_class' => Dummy::class,
        ]));
    }

    public function testNormalizePolymorphicRelations(): void
    {
        $concreteDummy = new DummyTableInheritanceChild();

        $dummy = new DummyTableInheritanceRelated();
        $dummy->addChild($concreteDummy);

        $abstractDummies = new ArrayCollection([$concreteDummy]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyTableInheritanceRelated::class, [])->willReturn(new PropertyNameCollection(['children']));

        $abstractDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, DummyTableInheritance::class);
        $abstractDummiesType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $abstractDummyType);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyTableInheritanceRelated::class, 'children', [])->willReturn((new ApiProperty())->withBuiltinTypes([$abstractDummiesType])->withReadable(true)->withWritable(false)->withReadableLink(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, Argument::cetera())->willReturn('/dummies/1');

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'children')->willReturn($abstractDummies);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(DummyTableInheritanceRelated::class);
        $resourceClassResolverProphecy->getResourceClass(null, DummyTableInheritanceRelated::class)->willReturn(DummyTableInheritanceRelated::class);
        $resourceClassResolverProphecy->getResourceClass($concreteDummy, DummyTableInheritance::class)->willReturn(DummyTableInheritanceChild::class);
        $resourceClassResolverProphecy->getResourceClass($abstractDummies, DummyTableInheritance::class)->willReturn(DummyTableInheritance::class);
        $resourceClassResolverProphecy->isResourceClass(DummyTableInheritanceRelated::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(DummyTableInheritance::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $concreteDummyChildContext = Argument::allOf(
            Argument::type('array'),
            Argument::not(Argument::withKey('iri'))
        );
        $serializerProphecy->normalize($concreteDummy, null, $concreteDummyChildContext)->willReturn(['foo' => 'concrete']);
        $serializerProphecy->normalize([['foo' => 'concrete']], null, Argument::type('array'))->willReturn([['foo' => 'concrete']]);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'children' => [['foo' => 'concrete']],
        ];
        $this->assertSame($expected, $normalizer->normalize($dummy, null, [
            'resources' => [],
        ]));
    }

    public function testDenormalize(): void
    {
        $data = [
            'name' => 'foo',
            'relatedDummy' => '/dummies/1',
            'relatedDummies' => ['/dummies/2'],
        ];

        $relatedDummy1 = new RelatedDummy();
        $relatedDummy2 = new RelatedDummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['name', 'relatedDummy', 'relatedDummies']));

        $relatedDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);
        $relatedDummiesType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummyType])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummiesType])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getResourceFromIri('/dummies/1', Argument::type('array'))->willReturn($relatedDummy1);
        $iriConverterProphecy->getResourceFromIri('/dummies/2', Argument::type('array'))->willReturn($relatedDummy2);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, Dummy::class);

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'name', 'foo')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'relatedDummy', $relatedDummy1)->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'relatedDummies', [$relatedDummy2])->shouldHaveBeenCalled();
    }

    public function testCanDenormalizeInputClassWithDifferentFieldsThanResourceClass(): void
    {
        $this->markTestSkipped('TODO: check why this test has been commented');

        // $data = [
        //     'dummyName' => 'Dummy Name',
        // ];
        //
        // $context = [
        //     'resource_class' => DummyForAdditionalFields::class,
        //     'input' => ['class' => DummyForAdditionalFieldsInput::class],
        //     'output' => ['class' => DummyForAdditionalFields::class],
        // ];
        // $augmentedContext = $context + ['api_denormalize' => true];
        //
        // $preHydratedDummy = new DummyForAdditionalFieldsInput('Name Dummy');
        // $cleanedContext = array_diff_key($augmentedContext, [
        //     'input' => null,
        //     'resource_class' => null,
        // ]);
        // $cleanedContextWithObjectToPopulate = array_merge($cleanedContext, [
        //     AbstractObjectNormalizer::OBJECT_TO_POPULATE => $preHydratedDummy,
        //     AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE => true,
        // ]);
        //
        // $dummyInputDto = new DummyForAdditionalFieldsInput('Dummy Name');
        // $dummy = new DummyForAdditionalFields('Dummy Name', 'name-dummy');
        //
        // $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        //
        // $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        //
        // $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        //
        // $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        // $resourceClassResolverProphecy->getResourceClass(null, DummyForAdditionalFields::class)->willReturn(DummyForAdditionalFields::class);
        //
        // $inputDataTransformerProphecy = $this->prophesize(DataTransformerInitializerInterface::class);
        // $inputDataTransformerProphecy->willImplement(DataTransformerInitializerInterface::class);
        // $inputDataTransformerProphecy->initialize(DummyForAdditionalFieldsInput::class, $cleanedContext)->willReturn($preHydratedDummy);
        // $inputDataTransformerProphecy->supportsTransformation($data, DummyForAdditionalFields::class, $augmentedContext)->willReturn(true);
        // $inputDataTransformerProphecy->transform($dummyInputDto, DummyForAdditionalFields::class, $augmentedContext)->willReturn($dummy);
        //
        // $serializerProphecy = $this->prophesize(SerializerInterface::class);
        // $serializerProphecy->willImplement(DenormalizerInterface::class);
        // $serializerProphecy->denormalize($data, DummyForAdditionalFieldsInput::class, 'json', $cleanedContextWithObjectToPopulate)->willReturn($dummyInputDto);
        //
        // $normalizer = new class($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), null, null, null, [], null, null) extends AbstractItemNormalizer {
        // };
        // $normalizer->setSerializer($serializerProphecy->reveal());
        //
        // $actual = $normalizer->denormalize($data, DummyForAdditionalFields::class, 'json', $context);
        //
        // $this->assertInstanceOf(DummyForAdditionalFields::class, $actual);
        // $this->assertSame('Dummy Name', $actual->getName());
    }

    public function testDenormalizeWritableLinks(): void
    {
        $data = [
            'name' => 'foo',
            'relatedDummy' => ['foo' => 'bar'],
            'relatedDummies' => [['bar' => 'baz']],
            'relatedDummiesWithUnionTypes' => [0 => ['bar' => 'qux'], 1. => ['bar' => 'quux']],
        ];

        $relatedDummy1 = new RelatedDummy();
        $relatedDummy2 = new RelatedDummy();
        $relatedDummy3 = new RelatedDummy();
        $relatedDummy4 = new RelatedDummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['name', 'relatedDummy', 'relatedDummies', 'relatedDummiesWithUnionTypes']));

        $relatedDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);
        $relatedDummiesType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);
        $relatedDummiesWithUnionTypesIntType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);
        $relatedDummiesWithUnionTypesFloatType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_FLOAT), $relatedDummyType);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummyType])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummiesType])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummiesWithUnionTypes', [])->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummiesWithUnionTypesIntType, $relatedDummiesWithUnionTypesFloatType])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);
        $serializerProphecy->denormalize(['foo' => 'bar'], RelatedDummy::class, null, Argument::type('array'))->willReturn($relatedDummy1);
        $serializerProphecy->denormalize(['bar' => 'baz'], RelatedDummy::class, null, Argument::type('array'))->willReturn($relatedDummy2);
        $serializerProphecy->denormalize(['bar' => 'qux'], RelatedDummy::class, null, Argument::type('array'))->willReturn($relatedDummy3);
        $serializerProphecy->denormalize(['bar' => 'quux'], RelatedDummy::class, null, Argument::type('array'))->willReturn($relatedDummy4);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, Dummy::class);

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'name', 'foo')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'relatedDummy', $relatedDummy1)->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'relatedDummies', [$relatedDummy2])->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'relatedDummiesWithUnionTypes', [0 => $relatedDummy3, 1. => $relatedDummy4])->shouldHaveBeenCalled();
    }

    public function testBadRelationType(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('The type of the "relatedDummy" attribute must be "array" (nested document) or "string" (IRI), "integer" given.');

        $data = [
            'relatedDummy' => 22,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummy']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize($data, Dummy::class);
    }

    public function testBadRelationTypeWithExceptionToValidationErrors(): void
    {
        $data = [
            'relatedDummy' => 22,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummy']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        // 'not_normalizable_value_exceptions' is set by Serializer thanks to DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS
        $actual = $normalizer->denormalize($data, Dummy::class, null, ['not_normalizable_value_exceptions' => []]);
        $this->assertNull($actual->relatedDummy);
    }

    public function testInnerDocumentNotAllowed(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Nested documents for attribute "relatedDummy" are not allowed. Use IRIs instead.');

        $data = [
            'relatedDummy' => [
                'foo' => 'bar',
            ],
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummy']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize($data, Dummy::class);
    }

    public function testBadType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The type of the "foo" attribute must be "float", "integer" given.');

        $data = [
            'foo' => 42,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['foo']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)])->withDescription('')->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize($data, Dummy::class);
    }

    public function testTypeChecksCanBeDisabled(): void
    {
        $data = [
            'foo' => 42,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['foo']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)])->withDescription('')->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, Dummy::class, null, ['disable_type_enforcement' => true]);

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'foo', 42)->shouldHaveBeenCalled();
    }

    public function testJsonAllowIntAsFloat(): void
    {
        $data = [
            'foo' => 42,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['foo']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)])->withDescription('')->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, Dummy::class, 'jsonfoo');

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'foo', 42)->shouldHaveBeenCalled();
    }

    public function testDenormalizeBadKeyType(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('The type of the key "a" must be "int", "string" given.');

        $data = [
            'name' => 'foo',
            'relatedDummy' => [
                'foo' => 'bar',
            ],
            'relatedDummies' => [
                'a' => [
                    'bar' => 'baz',
                ],
            ],
            'relatedDummiesWithUnionTypes' => [
                'a' => [
                    'bar' => 'baz',
                ],
            ],
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummies']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)])->withDescription('')->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false));

        $type = new Type(
            Type::BUILTIN_TYPE_OBJECT,
            false,
            ArrayCollection::class,
            true,
            new Type(Type::BUILTIN_TYPE_INT),
            new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn((new ApiProperty())->withBuiltinTypes([$type])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize($data, Dummy::class);
    }

    public function testNullable(): void
    {
        $data = [
            'name' => null,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['name']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING, true)])->withDescription('')->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, Dummy::class);

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'name', null)->shouldHaveBeenCalled();
    }

    public function testDenormalizeBasicTypePropertiesFromXml(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(ObjectWithBasicProperties::class, [])->willReturn(new PropertyNameCollection([
            'boolTrue1',
            'boolFalse1',
            'boolTrue2',
            'boolFalse2',
            'int1',
            'int2',
            'float1',
            'float2',
            'float3',
            'floatNaN',
            'floatInf',
            'floatNegInf',
        ]));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'boolTrue1', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'boolFalse1', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'boolTrue2', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'boolFalse2', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'int1', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'int2', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'float1', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'float2', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'float3', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'floatNaN', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'floatInf', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)])->withDescription('')->withReadable(false)->withWritable(true));
        $propertyMetadataFactoryProphecy->create(ObjectWithBasicProperties::class, 'floatNegInf', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)])->withDescription('')->withReadable(false)->withWritable(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'boolTrue1', true)->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'boolFalse1', false)->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'boolTrue2', true)->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'boolFalse2', false)->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'int1', 4711)->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'int2', -4711)->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'float1', Argument::approximate(123.456, 0))->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'float2', Argument::approximate(-1.2344e56, 1))->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'float3', Argument::approximate(45E-6, 1))->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'floatNaN', Argument::that(static fn (float $arg) => is_nan($arg)))->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'floatInf', \INF)->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(ObjectWithBasicProperties::class), 'floatNegInf', -\INF)->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, ObjectWithBasicProperties::class)->willReturn(ObjectWithBasicProperties::class);
        $resourceClassResolverProphecy->isResourceClass(ObjectWithBasicProperties::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $objectWithBasicProperties = $normalizer->denormalize(
            [
                'boolTrue1' => 'true',
                'boolFalse1' => 'false',
                'boolTrue2' => '1',
                'boolFalse2' => '0',
                'int1' => '4711',
                'int2' => '-4711',
                'float1' => '123.456',
                'float2' => '-1.2344e56',
                'float3' => '45E-6',
                'floatNaN' => 'NaN',
                'floatInf' => 'INF',
                'floatNegInf' => '-INF',
            ],
            ObjectWithBasicProperties::class,
            'xml'
        );

        $this->assertInstanceOf(ObjectWithBasicProperties::class, $objectWithBasicProperties);
    }

    public function testDenormalizeCollectionDecodedFromXmlWithOneChild(): void
    {
        $data = [
            'relatedDummies' => [
                'name' => 'foo',
            ],
        ];

        $relatedDummy = new RelatedDummy();
        $relatedDummy->setName('foo');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummies']));

        $relatedDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);
        $relatedDummiesType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummiesType])->withReadable(false)->withWritable(true)->withReadableLink(false)->withWritableLink(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'relatedDummies', Argument::type('array'))->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);
        $serializerProphecy->denormalize(['name' => 'foo'], RelatedDummy::class, 'xml', Argument::type('array'))->willReturn($relatedDummy);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize($data, Dummy::class, 'xml');
    }

    public function testDenormalizePopulatingNonCloneableObject(): void
    {
        $dummy = new NonCloneableDummy();
        $dummy->setName('foo');

        $data = [
            'name' => 'bar',
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(NonCloneableDummy::class, [])->willReturn(new PropertyNameCollection(['name']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(NonCloneableDummy::class, 'name', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(false)->withWritable(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, NonCloneableDummy::class)->willReturn(NonCloneableDummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, NonCloneableDummy::class)->willReturn(NonCloneableDummy::class);
        $resourceClassResolverProphecy->isResourceClass(NonCloneableDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());
        $normalizer->setSerializer($serializerProphecy->reveal());

        $context = [AbstractItemNormalizer::OBJECT_TO_POPULATE => $dummy];
        $actual = $normalizer->denormalize($data, NonCloneableDummy::class, null, $context);

        $this->assertInstanceOf(NonCloneableDummy::class, $actual);
        $this->assertSame($dummy, $actual);
        $propertyAccessorProphecy->setValue($actual, 'name', 'bar')->shouldHaveBeenCalled();
    }

    public function testDenormalizeObjectWithNullDisabledTypeEnforcement(): void
    {
        $data = [
            'dummy' => null,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DtoWithNullValue::class, [])->willReturn(new PropertyNameCollection(['dummy']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DtoWithNullValue::class, 'dummy', [])->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, nullable: true)])->withDescription('')->withReadable(true)->withWritable(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, DtoWithNullValue::class)->willReturn(DtoWithNullValue::class);
        $resourceClassResolverProphecy->isResourceClass(DtoWithNullValue::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $context = [AbstractItemNormalizer::DISABLE_TYPE_ENFORCEMENT => true];
        $actual = $normalizer->denormalize($data, DtoWithNullValue::class, null, $context);

        $this->assertInstanceOf(DtoWithNullValue::class, $actual);
        $this->assertEquals(new DtoWithNullValue(), $actual);
    }

    public function testCacheKey(): void
    {
        $relatedDummy = new RelatedDummy();

        $dummy = new Dummy();
        $dummy->setName('foo');
        $dummy->setAlias('ignored');
        $dummy->setRelatedDummy($relatedDummy);
        $dummy->relatedDummies->add(new RelatedDummy());

        $relatedDummies = new ArrayCollection([$relatedDummy]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::type('array'))->willReturn(new PropertyNameCollection(['name', 'alias', 'relatedDummy', 'relatedDummies']));

        $relatedDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);
        $relatedDummiesType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'alias', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummyType])->withDescription('')->withReadable(true)->withWritable(false)->withReadableLink(false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([$relatedDummiesType])->withReadable(true)->withWritable(false)->withReadableLink(false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, Argument::cetera())->willReturn('/dummies/1');
        $iriConverterProphecy->getIriFromResource($relatedDummy, Argument::cetera())->willReturn('/dummies/2');

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'name')->willReturn('foo');
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummy')->willReturn($relatedDummy);
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummies')->willReturn($relatedDummies);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummy, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummies, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('foo', null, Argument::type('array'))->willReturn('foo');
        $serializerProphecy->normalize(['/dummies/2'], null, Argument::type('array'))->willReturn(['/dummies/2']);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            [],
            null,
            null,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'name' => 'foo',
            'relatedDummy' => '/dummies/2',
            'relatedDummies' => ['/dummies/2'],
        ];
        $this->assertSame($expected, $normalizer->normalize($dummy, null, [
            'resources' => [],
            'groups' => ['group'],
            'ignored_attributes' => ['alias'],
            'operation_name' => 'operation_name',
            'root_operation_name' => 'root_operation_name',
        ]));

        $operationCacheKey = (new \ReflectionClass($normalizer))->getProperty('localFactoryOptionsCache')->getValue($normalizer);
        $this->assertEquals(array_keys($operationCacheKey), [sprintf('%s%s%s%s', Dummy::class, 'operation_name', 'root_operation_name', 'n')]);
        $this->assertEquals(current($operationCacheKey), ['serializer_groups' => ['group']]);
    }
}

class ObjectWithBasicProperties
{
    /** @var bool */
    public $boolTrue1;

    /** @var bool */
    public $boolFalse1;

    /** @var bool */
    public $boolTrue2;

    /** @var bool */
    public $boolFalse2;

    /** @var int */
    public $int1;

    /** @var int */
    public $int2;

    /** @var float */
    public $float1;

    /** @var float */
    public $float2;

    /** @var float */
    public $float3;

    /** @var float */
    public $floatNaN;

    /** @var float */
    public $floatInf;

    /** @var float */
    public $floatNegInf;
}
