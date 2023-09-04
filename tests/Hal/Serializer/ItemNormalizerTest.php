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

namespace ApiPlatform\Tests\Hal\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Hal\Serializer\ItemNormalizer;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\ActivableInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\Author;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\Library;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\TimestampableInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MaxDepthDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\LogicException;
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
    use ProphecyTrait;

    public function testDoesNotSupportDenormalization(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('jsonhal is a read-only format.');

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

    /**
     * @group legacy
     */
    public function testSupportsNormalization(): void
    {
        $std = new \stdClass();
        $dummy = new Dummy();
        $dummy->setDescription('hello');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(\stdClass::class)->willReturn(false);

        $nameConverter = $this->prophesize(NameConverterInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            $nameConverter->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization($dummy, $normalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($dummy, 'xml'));
        $this->assertFalse($normalizer->supportsNormalization($std, $normalizer::FORMAT));
        $this->assertEmpty($normalizer->getSupportedTypes('xml'));
        $this->assertSame(['object' => true], $normalizer->getSupportedTypes($normalizer::FORMAT));

        if (!method_exists(Serializer::class, 'getSupportedTypes')) {
            $this->assertTrue($normalizer->hasCacheableSupportsMethod());
        }
    }

    public function testNormalize(): void
    {
        $relatedDummy = new RelatedDummy();
        $dummy = new Dummy();
        $dummy->setName('hello');
        $dummy->setRelatedDummy($relatedDummy);

        $propertyNameCollection = new PropertyNameCollection(['name', 'relatedDummy']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)])->withDescription('')->withReadable(true)->withWritable(false)->withWritableLink(false)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, Argument::cetera())->willReturn('/dummies/1');
        $iriConverterProphecy->getIriFromResource($relatedDummy)->willReturn('/related-dummies/2');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummy, RelatedDummy::class)->willReturn(RelatedDummy::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello');

        $nameConverter = $this->prophesize(NameConverterInterface::class);
        $nameConverter->normalize('name', Argument::any(), Argument::any(), Argument::any())->willReturn('name');
        $nameConverter->normalize('relatedDummy', Argument::any(), Argument::any(), Argument::any())->willReturn('related_dummy');

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

    public function testNormalizeWithUnionIntersectTypes(): void
    {
        $author = new Author(id: 2, name: 'Isaac Asimov');
        $library = new Library(id: 3, name: 'Le Bâteau Livre');
        $book = new Book();
        $book->author = $author;
        $book->library = $library;

        $propertyNameCollection = new PropertyNameCollection(['author', 'library']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Book::class, [])->willReturn($propertyNameCollection);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Book::class, 'author', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([
                new Type(Type::BUILTIN_TYPE_OBJECT, false, ActivableInterface::class),
                new Type(Type::BUILTIN_TYPE_OBJECT, false, TimestampableInterface::class),
            ])->withReadable(true)
        );
        $propertyMetadataFactoryProphecy->create(Book::class, 'library', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([
                new Type(Type::BUILTIN_TYPE_OBJECT, false, ActivableInterface::class),
                new Type(Type::BUILTIN_TYPE_OBJECT, false, TimestampableInterface::class),
            ])->withReadable(true)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($book, Argument::cetera())->willReturn('/books/1');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Book::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(ActivableInterface::class)->willReturn(false);
        $resourceClassResolverProphecy->isResourceClass(TimestampableInterface::class)->willReturn(false);
        $resourceClassResolverProphecy->getResourceClass($book, null)->willReturn(Book::class);
        $resourceClassResolverProphecy->getResourceClass(null, Book::class)->willReturn(Book::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);

        $nameConverter = $this->prophesize(NameConverterInterface::class);
        $nameConverter->normalize('author', Argument::any(), Argument::any(), Argument::any())->willReturn('author');
        $nameConverter->normalize('library', Argument::any(), Argument::any(), Argument::any())->willReturn('library');

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
                    'href' => '/books/1',
                ],
            ],
            'author' => null,
            'library' => null,
        ];
        $this->assertEquals($expected, $normalizer->normalize($book));
    }

    public function testNormalizeWithoutCache(): void
    {
        $relatedDummy = new RelatedDummy();
        $dummy = new Dummy();
        $dummy->setName('hello');
        $dummy->setRelatedDummy($relatedDummy);

        $propertyNameCollection = new PropertyNameCollection(['name', 'relatedDummy']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)])->withDescription('')->withReadable(true)->withWritable(false)->withReadableLink(false)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, Argument::cetera())->willReturn('/dummies/1');
        $iriConverterProphecy->getIriFromResource($relatedDummy)->willReturn('/related-dummies/2');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($relatedDummy, RelatedDummy::class)->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello');

        $nameConverter = $this->prophesize(NameConverterInterface::class);
        $nameConverter->normalize('name', Argument::any(), Argument::any(), Argument::any())->willReturn('name');
        $nameConverter->normalize('relatedDummy', Argument::any(), Argument::any(), Argument::any())->willReturn('related_dummy');

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
        $this->assertEquals($expected, $normalizer->normalize($dummy, null, ['not_serializable' => function (): void {}]));
    }

    public function testMaxDepth(): void
    {
        $setId = function (MaxDepthDummy $dummy, int $id): void {
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
        $propertyNameCollectionFactoryProphecy->create(MaxDepthDummy::class, [])->willReturn($propertyNameCollection);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(MaxDepthDummy::class, 'id', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])->withDescription('')->withReadable(true)
        );
        $propertyMetadataFactoryProphecy->create(MaxDepthDummy::class, 'name', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)
        );
        $propertyMetadataFactoryProphecy->create(MaxDepthDummy::class, 'child', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, MaxDepthDummy::class)])->withDescription('')->withReadable(true)->withWritable(false)->withReadableLink(true)
        );

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($level1, Argument::cetera())->willReturn('/max_depth_dummies/1');
        $iriConverterProphecy->getIriFromResource($level2, Argument::cetera())->willReturn('/max_depth_dummies/2');
        $iriConverterProphecy->getIriFromResource($level3, Argument::cetera())->willReturn('/max_depth_dummies/3');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($level1, null)->willReturn(MaxDepthDummy::class);
        $resourceClassResolverProphecy->getResourceClass($level1, MaxDepthDummy::class)->willReturn(MaxDepthDummy::class);
        $resourceClassResolverProphecy->getResourceClass($level2, MaxDepthDummy::class)->willReturn(MaxDepthDummy::class);
        $resourceClassResolverProphecy->getResourceClass($level3, MaxDepthDummy::class)->willReturn(MaxDepthDummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, MaxDepthDummy::class)->willReturn(MaxDepthDummy::class);
        $resourceClassResolverProphecy->isResourceClass(MaxDepthDummy::class)->willReturn(true);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            new ClassMetadataFactory(new AnnotationLoader())
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
