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

use ApiPlatform\Hal\Serializer\ItemNormalizer;
use ApiPlatform\Hal\Tests\Fixtures\ApiResource\Issue5452\ActivableInterface;
use ApiPlatform\Hal\Tests\Fixtures\ApiResource\Issue5452\Author;
use ApiPlatform\Hal\Tests\Fixtures\ApiResource\Issue5452\Book;
use ApiPlatform\Hal\Tests\Fixtures\ApiResource\Issue5452\Library;
use ApiPlatform\Hal\Tests\Fixtures\ApiResource\Issue5452\TimestampableInterface;
use ApiPlatform\Hal\Tests\Fixtures\Dummy;
use ApiPlatform\Hal\Tests\Fixtures\MaxDepthDummy;
use ApiPlatform\Hal\Tests\Fixtures\RelatedDummy;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemNormalizerTest extends TestCase
{
    public function testDoesNotSupportDenormalization(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('jsonhal is a read-only format.');

        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $iriConverterMock = $this->createMock(IriConverterInterface::class);
        $resourceClassResolverMock = $this->createMock(ResourceClassResolverInterface::class);
        $nameConverter = $this->createMock(NameConverterInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryMock,
            $propertyMetadataFactoryMock,
            $iriConverterMock,
            $resourceClassResolverMock,
            null,
            $nameConverter
        );

        $this->assertFalse($normalizer->supportsDenormalization('foo', ItemNormalizer::FORMAT));
        $normalizer->denormalize(['foo'], 'Foo');
    }

    #[\PHPUnit\Framework\Attributes\Group('legacy')]
    public function testSupportsNormalization(): void
    {
        $std = new \stdClass();
        $dummy = new Dummy();

        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $iriConverterMock = $this->createMock(IriConverterInterface::class);

        $resourceClassResolverMock = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolverMock->method('isResourceClass')->willReturnMap([
            [Dummy::class, true],
            [\stdClass::class, false],
        ]);

        $nameConverter = $this->createMock(NameConverterInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryMock,
            $propertyMetadataFactoryMock,
            $iriConverterMock,
            $resourceClassResolverMock,
            null,
            $nameConverter
        );

        $this->assertTrue($normalizer->supportsNormalization($dummy, $normalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($dummy, 'xml'));
        $this->assertFalse($normalizer->supportsNormalization($std, $normalizer::FORMAT));
        $this->assertEmpty($normalizer->getSupportedTypes('xml'));
        $this->assertSame(['object' => true], $normalizer->getSupportedTypes($normalizer::FORMAT));
    }

    public function testNormalize(): void
    {
        $relatedDummy = new RelatedDummy();
        $dummy = new Dummy();
        $dummy->setName('hello');
        $dummy->setRelatedDummy($relatedDummy);

        $propertyNameCollection = new PropertyNameCollection(['name', 'relatedDummy']);
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryMock->method('create')->with(Dummy::class, ['api_allow_update' => false])->willReturn($propertyNameCollection);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryMock->method('create')->willReturnMap([
            [Dummy::class, 'name', ['api_allow_update' => false], (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)],
            [Dummy::class, 'relatedDummy', ['api_allow_update' => false], (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)])->withDescription('')->withReadable(true)->withWritable(false)->withWritableLink(false)],
        ]);

        $iriConverterMock = $this->createMock(IriConverterInterface::class);
        $iriConverterMock->method('getIriFromResource')->willReturnCallback(
            function ($resource) use ($dummy, $relatedDummy) {
                if ($resource === $dummy) {
                    return '/dummies/1';
                }
                if ($resource === $relatedDummy) {
                    return '/related-dummies/2';
                }

                return null;
            }
        );

        $resourceClassResolverMock = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolverMock->method('isResourceClass')->willReturn(true);
        $resourceClassResolverMock->method('getResourceClass')->willReturnMap([
            [$dummy, null, Dummy::class],
            [null, Dummy::class, Dummy::class],
            [$dummy, Dummy::class, Dummy::class],
            [$relatedDummy, RelatedDummy::class, RelatedDummy::class],
        ]);

        $serializerMock = $this->createMock(Serializer::class);
        $serializerMock->method('normalize')->with('hello', null, $this->isType('array'))->willReturn('hello');

        $nameConverter = $this->createMock(NameConverterInterface::class);
        $nameConverter->method('normalize')->willReturnCallback(
            static function (string $propertyName): string {
                if ('relatedDummy' === $propertyName) {
                    return 'related_dummy';
                }

                return $propertyName;
            }
        );

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryMock,
            $propertyMetadataFactoryMock,
            $iriConverterMock,
            $resourceClassResolverMock,
            null,
            $nameConverter
        );
        $normalizer->setSerializer($serializerMock);

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
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryMock->method('create')->with(Book::class, ['api_allow_update' => false])->willReturn($propertyNameCollection);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryMock->method('create')->willReturnMap([
            [Book::class, 'author', ['api_allow_update' => false], (new ApiProperty())->withBuiltinTypes([
                new Type(Type::BUILTIN_TYPE_OBJECT, false, ActivableInterface::class),
                new Type(Type::BUILTIN_TYPE_OBJECT, false, TimestampableInterface::class),
            ])->withReadable(true)],
            [Book::class, 'library', ['api_allow_update' => false], (new ApiProperty())->withBuiltinTypes([
                new Type(Type::BUILTIN_TYPE_OBJECT, false, ActivableInterface::class),
                new Type(Type::BUILTIN_TYPE_OBJECT, false, TimestampableInterface::class),
            ])->withReadable(true)],
        ]);

        $iriConverterMock = $this->createMock(IriConverterInterface::class);
        $iriConverterMock->method('getIriFromResource')->with($book)->willReturn('/books/1');

        $resourceClassResolverMock = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolverMock->method('isResourceClass')->willReturnMap([
            [Book::class, true],
            [ActivableInterface::class, false],
            [TimestampableInterface::class, false],
        ]);
        $resourceClassResolverMock->method('getResourceClass')->willReturnMap([
            [$book, null, Book::class],
            [null, Book::class, Book::class],
        ]);

        $serializerMock = $this->createMock(Serializer::class);

        $nameConverter = $this->createMock(NameConverterInterface::class);
        $nameConverter->method('normalize')->willReturnCallback(
            static fn (string $propertyName): string => $propertyName
        );

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryMock,
            $propertyMetadataFactoryMock,
            $iriConverterMock,
            $resourceClassResolverMock,
            null,
            $nameConverter
        );
        $normalizer->setSerializer($serializerMock);

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
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryMock->method('create')->with(Dummy::class, ['api_allow_update' => false])->willReturn($propertyNameCollection);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryMock->method('create')->willReturnMap([
            [Dummy::class, 'name', ['api_allow_update' => false], (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)],
            [Dummy::class, 'relatedDummy', ['api_allow_update' => false], (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)])->withDescription('')->withReadable(true)->withWritable(false)->withReadableLink(false)],
        ]);

        $iriConverterMock = $this->createMock(IriConverterInterface::class);
        $iriConverterMock->method('getIriFromResource')->willReturnCallback(
            function ($resource) use ($dummy, $relatedDummy) {
                if ($resource === $dummy) {
                    return '/dummies/1';
                }
                if ($resource === $relatedDummy) {
                    return '/related-dummies/2';
                }

                return null;
            }
        );

        $resourceClassResolverMock = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolverMock->method('getResourceClass')->willReturnMap([
            [$dummy, null, Dummy::class],
            [$dummy, Dummy::class, Dummy::class],
            [null, Dummy::class, Dummy::class],
            [$relatedDummy, RelatedDummy::class, RelatedDummy::class],
        ]);
        $resourceClassResolverMock->method('isResourceClass')->willReturn(true);

        $serializerMock = $this->createMock(Serializer::class);
        $serializerMock->method('normalize')->with('hello', null, $this->isType('array'))->willReturn('hello');

        $nameConverter = $this->createMock(NameConverterInterface::class);
        $nameConverter->method('normalize')->willReturnCallback(
            static function (string $propertyName): string {
                if ('relatedDummy' === $propertyName) {
                    return 'related_dummy';
                }

                return $propertyName;
            }
        );

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryMock,
            $propertyMetadataFactoryMock,
            $iriConverterMock,
            $resourceClassResolverMock,
            null,
            $nameConverter
        );
        $normalizer->setSerializer($serializerMock);

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
        $this->assertEquals($expected, $normalizer->normalize($dummy, null, ['not_serializable' => fn () => null]));
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
        $propertyNameCollectionFactoryMock = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryMock->method('create')->with(MaxDepthDummy::class, ['api_allow_update' => false])->willReturn($propertyNameCollection);

        $propertyMetadataFactoryMock = $this->createMock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryMock->method('create')->willReturnMap([
            [MaxDepthDummy::class, 'id', ['api_allow_update' => false], (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])->withDescription('')->withReadable(true)],
            [MaxDepthDummy::class, 'name', ['api_allow_update' => false], (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('')->withReadable(true)],
            [MaxDepthDummy::class, 'child', ['api_allow_update' => false], (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, MaxDepthDummy::class)])->withDescription('')->withReadable(true)->withWritable(false)->withReadableLink(true)],
        ]);

        $iriConverterMock = $this->createMock(IriConverterInterface::class);
        $iriConverterMock->method('getIriFromResource')->willReturnCallback(
            function ($resource) use ($level1, $level2, $level3) {
                if ($resource === $level1) {
                    return '/max_depth_dummies/1';
                }
                if ($resource === $level2) {
                    return '/max_depth_dummies/2';
                }
                if ($resource === $level3) {
                    return '/max_depth_dummies/3';
                }

                return null;
            }
        );

        $resourceClassResolverMock = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolverMock->method('getResourceClass')->willReturnMap([
            [$level1, null, MaxDepthDummy::class],
            [$level1, MaxDepthDummy::class, MaxDepthDummy::class],
            [$level2, MaxDepthDummy::class, MaxDepthDummy::class],
            [$level3, MaxDepthDummy::class, MaxDepthDummy::class],
            [null, MaxDepthDummy::class, MaxDepthDummy::class],
        ]);
        $resourceClassResolverMock->method('isResourceClass')->with(MaxDepthDummy::class)->willReturn(true);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryMock,
            $propertyMetadataFactoryMock,
            $iriConverterMock,
            $resourceClassResolverMock,
            null,
            null,
            new ClassMetadataFactory(new AttributeLoader())
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
