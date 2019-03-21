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

namespace ApiPlatform\Core\Tests\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\InputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyForAdditionalFields;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyForAdditionalFieldsInput;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritance;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceChild;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AbstractItemNormalizerTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testLegacySupportNormalizationAndSupportDenormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(\stdClass::class)->willReturn(false)->shouldBeCalled();

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
        ]);

        $this->assertTrue($normalizer->supportsNormalization($dummy));
        $this->assertFalse($normalizer->supportsNormalization($std));
        $this->assertTrue($normalizer->supportsDenormalization($dummy, Dummy::class));
        $this->assertFalse($normalizer->supportsDenormalization($std, \stdClass::class));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testSupportNormalizationAndSupportDenormalization()
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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);

        $this->assertTrue($normalizer->supportsNormalization($dummy));
        $this->assertFalse($normalizer->supportsNormalization($std));
        $this->assertTrue($normalizer->supportsDenormalization($dummy, Dummy::class));
        $this->assertFalse($normalizer->supportsDenormalization($std, \stdClass::class));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize()
    {
        $relatedDummy = new RelatedDummy();

        $dummy = new Dummy();
        $dummy->setName('foo');
        $dummy->setAlias('ignored');
        $dummy->setRelatedDummy($relatedDummy);
        $dummy->relatedDummies->add(new RelatedDummy());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['name', 'alias', 'relatedDummy', 'relatedDummies'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'alias', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class), '', true, false, false)
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
                true,
                false,
                false
            )
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($relatedDummy)->willReturn('/dummies/2')->shouldBeCalled();

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'name')->willReturn('foo')->shouldBeCalled();
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummy')->willReturn($relatedDummy)->shouldBeCalled();
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummies')->willReturn(
            new ArrayCollection([$relatedDummy])
        )->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(RelatedDummy::class)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('foo', null, Argument::type('array'))->willReturn('foo')->shouldBeCalled();
        $serializerProphecy->normalize(['/dummies/2'], null, Argument::type('array'))->willReturn(['/dummies/2'])->shouldBeCalled();

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        if (!interface_exists(AdvancedNameConverterInterface::class)) {
            $normalizer->setIgnoredAttributes(['alias']);
        }

        $this->assertEquals([
            'name' => 'foo',
            'relatedDummy' => '/dummies/2',
            'relatedDummies' => ['/dummies/2'],
        ], $normalizer->normalize($dummy, null, ['resources' => [], 'ignored_attributes' => ['alias']]));
    }

    public function testNormalizeReadableLinks()
    {
        $relatedDummy = new RelatedDummy();

        $dummy = new Dummy();
        $dummy->setRelatedDummy($relatedDummy);
        $dummy->relatedDummies->add(new RelatedDummy());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['relatedDummy', 'relatedDummies'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class), '', true, false, true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(
            new PropertyMetadata(
                new Type(
                    Type::BUILTIN_TYPE_OBJECT,
                    false,
                    ArrayCollection::class,
                    true,
                    new Type(Type::BUILTIN_TYPE_INT),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)
                ),
                '',
                true,
                false,
                true
            )
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldBeCalled();

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummy')->willReturn($relatedDummy)->shouldBeCalled();
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummies')->willReturn(new ArrayCollection([$relatedDummy]))->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(RelatedDummy::class)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize($relatedDummy, null, Argument::type('array'))->willReturn(['foo' => 'hello'])->shouldBeCalled();
        $serializerProphecy->normalize(['foo' => 'hello'], null, Argument::type('array'))->willReturn(['foo' => 'hello'])->shouldBeCalled();
        $serializerProphecy->normalize([['foo' => 'hello']], null, Argument::type('array'))->willReturn([['foo' => 'hello']])->shouldBeCalled();

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertEquals([
            'relatedDummy' => ['foo' => 'hello'],
            'relatedDummies' => [['foo' => 'hello']],
        ], $normalizer->normalize($dummy, null, ['resources' => []]));
    }

    public function testDenormalize()
    {
        $relatedDummy1 = new RelatedDummy();
        $relatedDummy2 = new RelatedDummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['name', 'relatedDummy', 'relatedDummies'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(
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

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('/dummies/1', Argument::type('array'))->willReturn($relatedDummy1)->shouldBeCalled();
        $iriConverterProphecy->getItemFromIri('/dummies/2', Argument::type('array'))->willReturn($relatedDummy2)->shouldBeCalled();

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'name', 'foo')->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'relatedDummy', $relatedDummy1)->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'relatedDummies', [$relatedDummy2])->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize([
            'name' => 'foo',
            'relatedDummy' => '/dummies/1',
            'relatedDummies' => ['/dummies/2'],
        ], Dummy::class);
    }

    public function testCanDenormalizeInputClassWithDifferentFieldsThanResourceClass()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyForAdditionalFieldsInput::class, [])->willReturn(
            new PropertyNameCollection(['dummyName'])
        );
        $propertyNameCollectionFactoryProphecy->create(DummyForAdditionalFields::class, [])->willReturn(
            new PropertyNameCollection(['id', 'name', 'slug'])
        );

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        // Create DummyForAdditionalFieldsInput mocks
        $propertyMetadataFactoryProphecy->create(DummyForAdditionalFieldsInput::class, 'dummyName', [])->willReturn(
            (new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true, false))->withInitializable(true)
        );
        // Create DummyForAdditionalFields mocks
        $propertyMetadataFactoryProphecy->create(DummyForAdditionalFields::class, 'id', [])->willReturn(
            (new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), '', true, false))->withInitializable(false)
        );
        $propertyMetadataFactoryProphecy->create(DummyForAdditionalFields::class, 'name', [])->willReturn(
            (new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true, false))->withInitializable(true)
        );
        $propertyMetadataFactoryProphecy->create(DummyForAdditionalFields::class, 'slug', [])->willReturn(
            (new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true, false))->withInitializable(true)
        );

        $normalizer = new class($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $this->prophesize(IriConverterInterface::class)->reveal(), $this->prophesize(ResourceClassResolverInterface::class)->reveal(), null, null, null, null, false, [], [], null, false) extends AbstractItemNormalizer {
        };

        /** @var DummyForAdditionalFieldsInput $res */
        $res = $normalizer->denormalize([
            'dummyName' => 'Dummy Name',
        ], DummyForAdditionalFieldsInput::class, 'json', [
            'resource_class' => DummyForAdditionalFields::class,
            'input' => ['class' => DummyForAdditionalFieldsInput::class],
            'output' => ['class' => DummyForAdditionalFields::class],
        ]);

        $this->assertInstanceOf(DummyForAdditionalFieldsInput::class, $res);
        $this->assertEquals('Dummy Name', $res->getDummyName());
    }

    public function testDenormalizeWritableLinks()
    {
        $relatedDummy1 = new RelatedDummy();
        $relatedDummy2 = new RelatedDummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['name', 'relatedDummy', 'relatedDummies'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', false, true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            new PropertyMetadata(new Type(
                Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class
            ), '', false, true, false, true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(
            new PropertyMetadata(
                new Type(
                    Type::BUILTIN_TYPE_OBJECT,
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
                true
            )
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'name', 'foo')->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'relatedDummy', $relatedDummy1)->shouldBeCalled();
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'relatedDummies', [$relatedDummy2])->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);
        $serializerProphecy->denormalize(
            ['foo' => 'bar'], RelatedDummy::class, null, Argument::type('array')
        )->willReturn($relatedDummy1)->shouldBeCalled();
        $serializerProphecy->denormalize(
            ['bar' => 'baz'], RelatedDummy::class, null, Argument::type('array')
        )->willReturn($relatedDummy2)->shouldBeCalled();

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize([
            'name' => 'foo',
            'relatedDummy' => ['foo' => 'bar'],
            'relatedDummies' => [['bar' => 'baz']],
        ], Dummy::class);
    }

    public function testBadRelationType()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected IRI or nested document for attribute "relatedDummy", "integer" given.');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['relatedDummy'])
        )->shouldBeCalled();

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

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true)->shouldBeCalled();

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['relatedDummy' => 22], Dummy::class);
    }

    public function testInnerDocumentNotAllowed()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Nested documents for attribute "relatedDummy" are not allowed. Use IRIs instead.');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['relatedDummy'])
        )->shouldBeCalled();

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

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true)->shouldBeCalled();

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['relatedDummy' => ['foo' => 'bar']], Dummy::class);
    }

    public function testBadType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The type of the "foo" attribute must be "float", "integer" given.');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['foo'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_FLOAT), '', false, true, false, false)
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['foo' => 42], Dummy::class);
    }

    public function testTypeChecksCanBeDisabled()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['foo'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_FLOAT), '', false, true, false, false)
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['foo' => 42], Dummy::class, null, ['disable_type_enforcement' => true]);
    }

    public function testJsonAllowIntAsFloat()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['foo'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_FLOAT), '', false, true, false, false)
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'foo', 42)->shouldBeCalled();
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['foo' => 42], Dummy::class, 'jsonfoo');
    }

    public function testDenormalizeBadKeyType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The type of the key "a" must be "int", "string" given.');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['relatedDummies'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(
            new PropertyMetadata(
                new Type(
                    Type::BUILTIN_TYPE_OBJECT,
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
                true
            )
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize([
            'name' => 'foo',
            'relatedDummy' => ['foo' => 'bar'],
            'relatedDummies' => ['a' => ['bar' => 'baz']],
        ], Dummy::class);
    }

    public function testNullable()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['name'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING, true), '', false, true, false, false)
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['name' => null], Dummy::class);
    }

    public function testChildInheritedProperty()
    {
        $dummy = new DummyTableInheritance();
        $dummy->setName('foo');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyTableInheritance::class, [])->willReturn(
            new PropertyNameCollection(['name', 'nickname'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyTableInheritance::class, 'name', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(DummyTableInheritance::class, 'nickname', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING, true), '', true, true, false, false, false, false, null, DummyTableInheritanceChild::class)
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldBeCalled();

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'name')->willReturn('foo')->shouldBeCalled();
        $propertyAccessorProphecy->getValue($dummy, 'nickname')->willThrow(new NoSuchPropertyException())->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, DummyTableInheritance::class, true)->willReturn(DummyTableInheritance::class)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('foo', null, Argument::type('array'))->willReturn('foo')->shouldBeCalled();
        $serializerProphecy->normalize(null, null, Argument::type('array'))->willReturn(null)->shouldBeCalled();

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertEquals([
            'name' => 'foo',
            'nickname' => null,
        ], $normalizer->normalize($dummy, null, ['resource_class' => DummyTableInheritance::class, 'resources' => []]));
    }

    public function testDenormalizeRelationWithPlainId()
    {
        $relatedDummy = new RelatedDummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['relatedDummy'])
        )->shouldBeCalled();

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

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'relatedDummy', $relatedDummy)->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem(RelatedDummy::class, 1, null, Argument::type('array'))->willReturn($relatedDummy)->shouldBeCalled();

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            $itemDataProviderProphecy->reveal(),
            true,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['relatedDummy' => 1], Dummy::class, 'jsonld');
    }

    public function testDenormalizeRelationWithPlainIdNotFound()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('Item not found for "1".');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['relatedDummy'])
        )->shouldBeCalled();

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

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem(RelatedDummy::class, 1, null, Argument::type('array'))->willReturn(null)->shouldBeCalled();

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            $itemDataProviderProphecy->reveal(),
            true,
            [],
            [],
            null,
            true,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['relatedDummy' => 1], Dummy::class, 'jsonld');
    }

    public function testDoNotDenormalizeRelationWithPlainIdWhenPlainIdentifiersAreNotAllowed()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected IRI or nested document for attribute "relatedDummy", "integer" given.');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['relatedDummy'])
        )->shouldBeCalled();

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

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem(RelatedDummy::class, 1, null, Argument::type('array'))->shouldNotBeCalled();

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            $itemDataProviderProphecy->reveal(),
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['relatedDummy' => 1], Dummy::class, 'jsonld');
    }

    /**
     * Test case:
     * 1. Request `PUT {InputDto} /recover_password`
     * 2. The `AbstractItemNormalizer` denormalizes the json representation of `{InputDto}` in a `RecoverPasswordInput`
     * 3. The `DataTransformer` transforms this `InputDto` in a `Dummy`
     * 4. Messenger is used, we send the `Dummy`
     * 5. The handler receives a `{Dummy}` json representation and tries to denormalize it
     * 6. Because it has an `input`, the `AbstractItemNormalizer` tries to denormalize it as a `InputDto` which is wrong, it's a `{Dummy}`.
     */
    public function testNormalizationWithDataTransformer()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(InputDto::class, Argument::any())->willReturn(
            new PropertyNameCollection()
        )->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->willReturn(
            new PropertyNameCollection(['name'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::any())->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', false, true)
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->setValue(Argument::type(Dummy::class), 'name', 'Dummy')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);
        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(
            'dummy', '', '', null, null, ['input' => ['class' => InputDto::class]]
        ));

        $jsonInput = ['foo' => 'f', 'bar' => 'b'];
        $transformed = new Dummy();
        $requestContext = [
            'operation_type' => 'collection',
            'collection_operation_name' => 'post',
            'resource_class' => Dummy::class,
            'input' => [
                'class' => InputDto::class,
                'name' => 'InputDto',
            ],
            'output' => ['class' => 'null'],
            'api_denormalize' => true, // this is added by the normalizer
        ];

        $secondJsonInput = ['name' => 'Dummy'];
        $secondContext = ['api_denormalize' => true, 'resource_class' => Dummy::class];
        $secondTransformed = new Dummy();
        $secondTransformed->setName('Dummy');

        $dataTransformerProphecy = $this->prophesize(DataTransformerInterface::class);
        $dataTransformerProphecy->supportsTransformation($jsonInput, Dummy::class, $requestContext)->shouldBeCalled()->willReturn(true);
        $dataTransformerProphecy->supportsTransformation($secondJsonInput, Dummy::class, $secondContext)->shouldBeCalled()->willReturn(false);
        $dataTransformerProphecy->transform(Argument::that(function ($arg) {
            return $arg instanceof InputDto;
        }), Dummy::class, $requestContext)->shouldBeCalled()->willReturn($transformed);

        $secondDataTransformerProphecy = $this->prophesize(DataTransformerInterface::class);
        $secondDataTransformerProphecy->supportsTransformation(Argument::any(), Dummy::class, Argument::any())->shouldBeCalled()->willReturn(false);

        $normalizer = $this->getMockForAbstractClass(AbstractItemNormalizer::class, [
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            null,
            null,
            $itemDataProviderProphecy->reveal(),
            false,
            [],
            [$dataTransformerProphecy->reveal(), $secondDataTransformerProphecy->reveal()],
            $resourceMetadataFactoryProphecy->reveal(),
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        // This is step 1-3, {InputDto} to Dummy
        $this->assertEquals($transformed, $normalizer->denormalize($jsonInput, Dummy::class, 'jsonld', $requestContext));
        // Messenger sends {InputDto}
        $this->assertInstanceOf(Dummy::class, $normalizer->denormalize($secondJsonInput, Dummy::class, 'jsonld'));
    }
}
