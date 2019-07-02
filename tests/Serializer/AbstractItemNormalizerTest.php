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

        $relatedDummies = new ArrayCollection([$relatedDummy]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['name', 'alias', 'relatedDummy', 'relatedDummies']));

        $relatedDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);
        $relatedDummiesType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'alias', [])->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(new PropertyMetadata($relatedDummyType, '', true, false, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(new PropertyMetadata($relatedDummiesType, '', true, false, false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1');
        $iriConverterProphecy->getIriFromItem($relatedDummy)->willReturn('/dummies/2');

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'name')->willReturn('foo');
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummy')->willReturn($relatedDummy);
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummies')->willReturn($relatedDummies);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummy, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummies, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(RelatedDummy::class);

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

        $expected = [
            'name' => 'foo',
            'relatedDummy' => '/dummies/2',
            'relatedDummies' => ['/dummies/2'],
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy, null, [
            'resources' => [],
            'ignored_attributes' => ['alias'],
        ]));
    }

    public function testNormalizeReadableLinks()
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
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(new PropertyMetadata($relatedDummyType, '', true, false, true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(new PropertyMetadata($relatedDummiesType, '', true, false, true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1');

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummy')->willReturn($relatedDummy);
        $propertyAccessorProphecy->getValue($dummy, 'relatedDummies')->willReturn($relatedDummies);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummy, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummies, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(RelatedDummy::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize($relatedDummy, null, Argument::type('array'))->willReturn(['foo' => 'hello']);
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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            'relatedDummy' => ['foo' => 'hello'],
            'relatedDummies' => [['foo' => 'hello']],
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy, null, [
            'resources' => [],
        ]));
    }

    public function testDenormalize()
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
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', false, true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(new PropertyMetadata($relatedDummyType, '', false, true, false, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(new PropertyMetadata($relatedDummiesType, '', false, true, false, false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('/dummies/1', Argument::type('array'))->willReturn($relatedDummy1);
        $iriConverterProphecy->getItemFromIri('/dummies/2', Argument::type('array'))->willReturn($relatedDummy2);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

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

        $actual = $normalizer->denormalize($data, Dummy::class);

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'name', 'foo')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'relatedDummy', $relatedDummy1)->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'relatedDummies', [$relatedDummy2])->shouldHaveBeenCalled();
    }

    public function testCanDenormalizeInputClassWithDifferentFieldsThanResourceClass()
    {
        $data = [
            'dummyName' => 'Dummy Name',
        ];

        $context = [
            'resource_class' => DummyForAdditionalFields::class,
            'input' => ['class' => DummyForAdditionalFieldsInput::class],
            'output' => ['class' => DummyForAdditionalFields::class],
        ];
        $augmentedContext = $context + ['api_denormalize' => true];
        $cleanedContext = array_diff_key($augmentedContext, [
            'input' => null,
            'resource_class' => null,
        ]);

        $dummyInputDto = new DummyForAdditionalFieldsInput('Dummy Name');
        $dummy = new DummyForAdditionalFields('Dummy Name', 'dummy-name');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, DummyForAdditionalFields::class)->willReturn(DummyForAdditionalFields::class);

        $inputDataTransformerProphecy = $this->prophesize(DataTransformerInterface::class);
        $inputDataTransformerProphecy->supportsTransformation($data, DummyForAdditionalFields::class, $augmentedContext)->willReturn(true);
        $inputDataTransformerProphecy->transform($dummyInputDto, DummyForAdditionalFields::class, $augmentedContext)->willReturn($dummy);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);
        $serializerProphecy->denormalize($data, DummyForAdditionalFieldsInput::class, 'json', $cleanedContext)->willReturn($dummyInputDto);

        $normalizer = new class($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), null, null, null, null, false, [], [$inputDataTransformerProphecy->reveal()], null) extends AbstractItemNormalizer {
        };
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, DummyForAdditionalFields::class, 'json', $context);

        $this->assertInstanceOf(DummyForAdditionalFields::class, $actual);
        $this->assertEquals('Dummy Name', $actual->getName());
    }

    public function testDenormalizeWritableLinks()
    {
        $data = [
            'name' => 'foo',
            'relatedDummy' => ['foo' => 'bar'],
            'relatedDummies' => [['bar' => 'baz']],
        ];

        $relatedDummy1 = new RelatedDummy();
        $relatedDummy2 = new RelatedDummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['name', 'relatedDummy', 'relatedDummies']));

        $relatedDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);
        $relatedDummiesType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', false, true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(new PropertyMetadata($relatedDummyType, '', false, true, false, true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(new PropertyMetadata($relatedDummiesType, '', false, true, false, true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);
        $serializerProphecy->denormalize(['foo' => 'bar'], RelatedDummy::class, null, Argument::type('array'))->willReturn($relatedDummy1);
        $serializerProphecy->denormalize(['bar' => 'baz'], RelatedDummy::class, null, Argument::type('array'))->willReturn($relatedDummy2);

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

        $actual = $normalizer->denormalize($data, Dummy::class);

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'name', 'foo')->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'relatedDummy', $relatedDummy1)->shouldHaveBeenCalled();
        $propertyAccessorProphecy->setValue($actual, 'relatedDummies', [$relatedDummy2])->shouldHaveBeenCalled();
    }

    public function testBadRelationType()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected IRI or nested document for attribute "relatedDummy", "integer" given.');

        $data = [
            'relatedDummy' => 22,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummy']));

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
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize($data, Dummy::class);
    }

    public function testInnerDocumentNotAllowed()
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
            new PropertyMetadata(
                new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class),
                '',
                false,
                true,
                false,
                false
            )
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize($data, Dummy::class);
    }

    public function testBadType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The type of the "foo" attribute must be "float", "integer" given.');

        $data = [
            'foo' => 42,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['foo']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', [])->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_FLOAT), '', false, true, false, false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize($data, Dummy::class);
    }

    public function testTypeChecksCanBeDisabled()
    {
        $data = [
            'foo' => 42,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['foo']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', [])->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_FLOAT), '', false, true, false, false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, Dummy::class, null, ['disable_type_enforcement' => true]);

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'foo', 42)->shouldHaveBeenCalled();
    }

    public function testJsonAllowIntAsFloat()
    {
        $data = [
            'foo' => 42,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['foo']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', [])->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_FLOAT), '', false, true, false, false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, Dummy::class, 'jsonfoo');

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'foo', 42)->shouldHaveBeenCalled();
    }

    public function testDenormalizeBadKeyType()
    {
        $this->expectException(InvalidArgumentException::class);
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
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummies']));

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
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $normalizer->denormalize($data, Dummy::class);
    }

    public function testNullable()
    {
        $data = [
            'name' => null,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['name']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING, true), '', false, true, false, false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);

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
            null,
            false,
            [],
            [],
            null,
            false,
        ]);
        $normalizer->setSerializer($serializerProphecy->reveal());

        $actual = $normalizer->denormalize($data, Dummy::class);

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'name', null)->shouldHaveBeenCalled();
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
        $resourceClassResolverProphecy->getResourceClass($dummy, DummyTableInheritance::class)->willReturn(DummyTableInheritance::class)->shouldBeCalled();

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
        $data = [
            'relatedDummy' => 1,
        ];

        $relatedDummy = new RelatedDummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummy']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class), '', false, true, false, false));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem(RelatedDummy::class, 1, null, Argument::type('array'))->willReturn($relatedDummy);

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

        $actual = $normalizer->denormalize($data, Dummy::class, 'jsonld');

        $this->assertInstanceOf(Dummy::class, $actual);

        $propertyAccessorProphecy->setValue($actual, 'relatedDummy', $relatedDummy)->shouldHaveBeenCalled();
    }

    public function testDenormalizeRelationWithPlainIdNotFound()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Item not found for resource "%s" with id "1".', RelatedDummy::class));

        $data = [
            'relatedDummy' => 1,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummy']));

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
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem(RelatedDummy::class, 1, null, Argument::type('array'))->willReturn(null);

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

        $normalizer->denormalize($data, Dummy::class, 'jsonld');
    }

    public function testDoNotDenormalizeRelationWithPlainIdWhenPlainIdentifiersAreNotAllowed()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected IRI or nested document for attribute "relatedDummy", "integer" given.');

        $data = [
            'relatedDummy' => 1,
        ];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['relatedDummy']));

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
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

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

        $normalizer->denormalize($data, Dummy::class, 'jsonld');
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
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->willReturn(new PropertyNameCollection(['name']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::any())->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', false, true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $jsonInput = ['foo' => 'f', 'bar' => 8];
        $inputDto = new InputDto();
        $inputDto->foo = 'f';
        $inputDto->bar = 8;
        $transformed = new Dummy();
        $context = [
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
        $cleanedContext = array_diff_key($context, [
            'input' => null,
            'resource_class' => null,
        ]);

        $secondJsonInput = ['name' => 'Dummy'];
        $secondContext = ['api_denormalize' => true, 'resource_class' => Dummy::class];
        $secondTransformed = new Dummy();
        $secondTransformed->setName('Dummy');

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);
        $serializerProphecy->denormalize($jsonInput, InputDto::class, 'jsonld', $cleanedContext)->willReturn($inputDto);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('dummy', '', '', null, null, ['input' => ['class' => InputDto::class]]));

        $dataTransformerProphecy = $this->prophesize(DataTransformerInterface::class);
        $dataTransformerProphecy->supportsTransformation($jsonInput, Dummy::class, $context)->willReturn(true);
        $dataTransformerProphecy->supportsTransformation($secondJsonInput, Dummy::class, $secondContext)->willReturn(false);
        $dataTransformerProphecy->transform($inputDto, Dummy::class, $context)->willReturn($transformed);

        $secondDataTransformerProphecy = $this->prophesize(DataTransformerInterface::class);
        $secondDataTransformerProphecy->supportsTransformation(Argument::any(), Dummy::class, Argument::any())->willReturn(false);

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
        $this->assertEquals($transformed, $normalizer->denormalize($jsonInput, Dummy::class, 'jsonld', $context));

        // Messenger sends {InputDto}
        $actualDummy = $normalizer->denormalize($secondJsonInput, Dummy::class, 'jsonld');

        $this->assertInstanceOf(Dummy::class, $actualDummy);

        $propertyAccessorProphecy->setValue($actualDummy, 'name', 'Dummy')->shouldHaveBeenCalled();
    }
}
