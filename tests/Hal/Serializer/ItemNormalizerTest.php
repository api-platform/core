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

namespace ApiPlatform\Core\Tests\Hal\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Hal\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\MaxDepthDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemNormalizerTest extends TestCase
{
    public function testDoesNotSupportDenormalization()
    {
        $this->expectException(RuntimeException::class);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $nameConverter = $this->prophesize(NameConverterInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            $nameConverter->reveal()
        );

        $this->assertFalse($normalizer->supportsDenormalization('foo', ItemNormalizer::FORMAT));
        $normalizer->denormalize(['foo'], 'Foo');
    }

    public function testSupportsNormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();
        $dummy->setDescription('hello');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(\stdClass::class)->willReturn(false)->shouldBeCalled();

        $nameConverter = $this->prophesize(NameConverterInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            $nameConverter->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization($dummy, 'jsonhal'));
        $this->assertFalse($normalizer->supportsNormalization($dummy, 'xml'));
        $this->assertFalse($normalizer->supportsNormalization($std, 'jsonhal'));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize()
    {
        $relatedDummy = new RelatedDummy();
        $dummy = new Dummy();
        $dummy->setName('hello');
        $dummy->setRelatedDummy($relatedDummy);

        $propertyNameCollection = new PropertyNameCollection(['name', 'relatedDummy']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class), '', true, false, false)
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($relatedDummy)->willReturn('/related-dummies/2')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class, true)->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello')->shouldBeCalled();

        $nameConverter = $this->prophesize(NameConverterInterface::class);
        $nameConverter->normalize('name', Argument::any(), Argument::any(), Argument::any())->shouldBeCalled()->willReturn('name');
        $nameConverter->normalize('relatedDummy', Argument::any(), Argument::any(), Argument::any())->shouldBeCalled()->willReturn('related_dummy');

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            $nameConverter->reveal()
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            '_links' => [
                'self' => [
                    'href' => '/dummies/1',
                ],
                'related_dummy' => [
                    'href' => '/related-dummies/2',
                ],
            ],
            'name' => 'hello',
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy));
    }

    public function testNormalizeWithoutCache()
    {
        $relatedDummy = new RelatedDummy();
        $dummy = new Dummy();
        $dummy->setName('hello');
        $dummy->setRelatedDummy($relatedDummy);

        $propertyNameCollection = new PropertyNameCollection(['name', 'relatedDummy']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class), '', true, false, false)
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($relatedDummy)->willReturn('/related-dummies/2')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class, true)->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello')->shouldBeCalled();

        $nameConverter = $this->prophesize(NameConverterInterface::class);
        $nameConverter->normalize('name', Argument::any(), Argument::any(), Argument::any())->shouldBeCalled()->willReturn('name');
        $nameConverter->normalize('relatedDummy', Argument::any(), Argument::any(), Argument::any())->shouldBeCalled()->willReturn('related_dummy');

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            $nameConverter->reveal()
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            '_links' => [
                'self' => [
                    'href' => '/dummies/1',
                ],
                'related_dummy' => [
                    'href' => '/related-dummies/2',
                ],
            ],
            'name' => 'hello',
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy, null, ['not_serializable' => function () {}]));
    }

    public function testMaxDepth()
    {
        $setId = function (MaxDepthDummy $dummy, int $id) {
            $prop = new \ReflectionProperty($dummy, 'id');
            $prop->setAccessible(true);
            $prop->setValue($dummy, $id);
        };

        $level1 = new MaxDepthDummy();
        $setId($level1, 1);
        $level1->name = 'level 1';

        $level2 = new MaxDepthDummy();
        $setId($level2, 2);
        $level2->name = 'level 2';
        $level1->child = $level2;

        $level3 = new MaxDepthDummy();
        $setId($level3, 3);
        $level3->name = 'level 3';
        $level2->child = $level3;

        $propertyNameCollection = new PropertyNameCollection(['id', 'name', 'child']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(MaxDepthDummy::class, [])->willReturn($propertyNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(MaxDepthDummy::class, 'id', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), '', true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(MaxDepthDummy::class, 'name', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), '', true)
        )->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(MaxDepthDummy::class, 'child', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, MaxDepthDummy::class), '', true, false, true)
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($level1)->willReturn('/max_depth_dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($level2)->willReturn('/max_depth_dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($level3)->willReturn('/max_depth_dummies/3')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($level1, null, true)->willReturn(MaxDepthDummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass($level1, MaxDepthDummy::class, true)->willReturn(MaxDepthDummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass($level2, MaxDepthDummy::class, true)->willReturn(MaxDepthDummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass($level3, MaxDepthDummy::class, true)->willReturn(MaxDepthDummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(MaxDepthDummy::class)->willReturn(true)->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()))
        );
        $serializer = new Serializer([$normalizer]);
        $normalizer->setSerializer($serializer);

        $expected = [
            '_links' => [
                'self' => [
                    'href' => '/max_depth_dummies/1',
                ],
                'child' => [
                    'href' => '/max_depth_dummies/2',
                ],
            ],
            '_embedded' => [
                'child' => [
                    '_links' => [
                        'self' => [
                            'href' => '/max_depth_dummies/2',
                        ],
                        'child' => [
                            'href' => '/max_depth_dummies/3',
                        ],
                    ],
                    '_embedded' => [
                        'child' => [
                            '_links' => [
                                'self' => [
                                    'href' => '/max_depth_dummies/3',
                                ],
                            ],
                            'id' => 3,
                            'name' => 'level 3',
                        ],
                    ],
                    'id' => 2,
                    'name' => 'level 2',
                ],
            ],
            'id' => 1,
            'name' => 'level 1',
        ];

        $this->assertEquals($expected, $normalizer->normalize($level1, ItemNormalizer::FORMAT));
        $this->assertEquals($expected, $normalizer->normalize($level1, ItemNormalizer::FORMAT, [ObjectNormalizer::ENABLE_MAX_DEPTH => false]));

        $expected = [
            '_links' => [
                'self' => [
                    'href' => '/max_depth_dummies/1',
                ],
                'child' => [
                    'href' => '/max_depth_dummies/2',
                ],
            ],
            '_embedded' => [
                'child' => [
                    '_links' => [
                        'self' => [
                            'href' => '/max_depth_dummies/2',
                        ],
                    ],
                    'id' => 2,
                    'name' => 'level 2',
                ],
            ],
            'id' => 1,
            'name' => 'level 1',
        ];

        $this->assertEquals($expected, $normalizer->normalize($level1, ItemNormalizer::FORMAT, [ObjectNormalizer::ENABLE_MAX_DEPTH => true]));
    }
}
