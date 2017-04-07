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
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritance;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceChild;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\Argument;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AbstractItemNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportNormalizationAndSupportDenormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy)->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass($std)->willThrow(new InvalidArgumentException())->shouldBeCalled();
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
    }

    public function testNormalize()
    {
        $relatedDummy = new RelatedDummy();

        $dummy = new Dummy();
        $dummy->setName('foo');
        $dummy->setRelatedDummy($relatedDummy);
        $dummy->relatedDummies->add(new RelatedDummy());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(
            new PropertyNameCollection(['name', 'relatedDummy', 'relatedDummies'])
        )->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(
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
        $iriConverterProphecy->getIriFromItem($relatedDummy)->willReturn('/dummies/2')->shouldBeCalled();

        $propertyAccesorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccesorProphecy->getValue($dummy, 'name')->willReturn('foo')->shouldBeCalled();
        $propertyAccesorProphecy->getValue($dummy, 'relatedDummy')->willReturn($relatedDummy)->shouldBeCalled();
        $propertyAccesorProphecy->getValue($dummy, 'relatedDummies')->willReturn(
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
            $propertyAccesorProphecy->reveal(),
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertEquals([
            'name' => 'foo',
            'relatedDummy' => '/dummies/2',
            'relatedDummies' => ['/dummies/2'],
        ], $normalizer->normalize($dummy));
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
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertEquals([
            'relatedDummy' => ['foo' => 'hello'],
            'relatedDummies' => [['foo' => 'hello']],
        ], $normalizer->normalize($dummy));
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
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize([
            'name' => 'foo',
            'relatedDummy' => '/dummies/1',
            'relatedDummies' => ['/dummies/2'],
        ], Dummy::class);
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
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize([
            'name' => 'foo',
            'relatedDummy' => ['foo' => 'bar'],
            'relatedDummies' => [['bar' => 'baz']],
        ], Dummy::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Expected IRI or nested document for attribute "relatedDummy", "integer" given.
     */
    public function testBadRelationType()
    {
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
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['relatedDummy' => 22], Dummy::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Nested documents for attribute "relatedDummy" are not allowed. Use IRIs instead.
     */
    public function testInnerDocumentNotAllowed()
    {
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
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['relatedDummy' => ['foo' => 'bar']], Dummy::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The type of the "foo" attribute must be "float", "integer" given.
     */
    public function testBadType()
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
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['foo' => 42], Dummy::class);
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
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize(['foo' => 42], Dummy::class, 'jsonfoo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The type of the key "a" must be "int", "string" given.
     */
    public function testDenormalizeBadKeyType()
    {
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

        $propertyAccesorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccesorProphecy->getValue($dummy, 'name')->willReturn('foo')->shouldBeCalled();
        $propertyAccesorProphecy->getValue($dummy, 'nickname')->willThrow(new NoSuchPropertyException())->shouldBeCalled();

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
            $propertyAccesorProphecy->reveal(),
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertEquals([
            'name' => 'foo',
            'nickname' => null,
        ], $normalizer->normalize($dummy, null, ['resource_class' => DummyTableInheritance::class]));
    }
}
