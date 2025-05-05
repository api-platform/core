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

namespace ApiPlatform\Doctrine\Odm\Tests\PropertyInfo;

use ApiPlatform\Doctrine\Odm\PropertyInfo\DoctrineExtractor;
use ApiPlatform\Doctrine\Odm\Tests\DoctrineMongoDbOdmSetup;
use ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures\DoctrineDummy;
use ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures\DoctrineEmbeddable;
use ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures\DoctrineEnum;
use ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures\DoctrineFooType;
use ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures\DoctrineGeneratedValue;
use ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures\DoctrineRelation;
use ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures\DoctrineWithEmbedded;
use ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures\EnumInt;
use ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures\EnumString;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DoctrineExtractorTest extends TestCase
{
    public function testGetProperties(): void
    {
        $this->assertEquals(
            [
                'id',
                'foo',
                'bar',
                'indexedFoo',
                'bin',
                'binByteArray',
                'binCustom',
                'binFunc',
                'binMd5',
                'binUuid',
                'binUuidRfc4122',
                'timestamp',
                'date',
                'dateImmutable',
                'float',
                'bool',
                'customFoo',
                'int',
                'string',
                'key',
                'hash',
                'collection',
                'objectId',
                'raw',
            ],
            $this->createExtractor()->getProperties(DoctrineDummy::class)
        );
    }

    public function testTestGetPropertiesWithEmbedded(): void
    {
        $this->assertEquals(
            [
                'id',
                'embedOne',
                'embedMany',
                'embedManyOmittingTargetDocument',
            ],
            $this->createExtractor()->getProperties(DoctrineWithEmbedded::class)
        );
    }

    #[IgnoreDeprecations]
    #[\PHPUnit\Framework\Attributes\DataProvider('legacyTypesProvider')]
    public function testExtractLegacy(string $property, ?array $type = null): void
    {
        $this->assertEquals($type, $this->createExtractor()->getTypes(DoctrineDummy::class, $property));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('typesProvider')]
    public function testExtract(string $property, ?Type $type): void
    {
        $this->assertEquals($type, $this->createExtractor()->getType(DoctrineDummy::class, $property));
    }

    #[IgnoreDeprecations]
    public function testExtractWithEmbedOneLegacy(): void
    {
        $expectedTypes = [
            new LegacyType(
                LegacyType::BUILTIN_TYPE_OBJECT,
                false,
                DoctrineEmbeddable::class
            ),
        ];

        $actualTypes = $this->createExtractor()->getTypes(
            DoctrineWithEmbedded::class,
            'embedOne'
        );

        $this->assertEquals($expectedTypes, $actualTypes);
    }

    public function testExtractWithEmbedOne(): void
    {
        $this->assertEquals(
            Type::object(DoctrineEmbeddable::class),
            $this->createExtractor()->getType(DoctrineWithEmbedded::class, 'embedOne'),
        );
    }

    #[IgnoreDeprecations]
    public function testExtractWithEmbedManyLegacy(): void
    {
        $expectedTypes = [
            new LegacyType(
                LegacyType::BUILTIN_TYPE_OBJECT,
                false,
                Collection::class,
                true,
                new LegacyType(LegacyType::BUILTIN_TYPE_INT),
                new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, false, DoctrineEmbeddable::class)
            ),
        ];

        $actualTypes = $this->createExtractor()->getTypes(
            DoctrineWithEmbedded::class,
            'embedMany'
        );

        $this->assertEquals($expectedTypes, $actualTypes);
    }

    public function testExtractWithEmbedMany(): void
    {
        $this->assertEquals(
            Type::collection(Type::object(Collection::class), Type::object(DoctrineEmbeddable::class), Type::int()),
            $this->createExtractor()->getType(DoctrineWithEmbedded::class, 'embedMany'),
        );
    }

    #[IgnoreDeprecations]
    public function testExtractEnumLegacy(): void
    {
        $this->assertEquals([new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, false, EnumString::class)], $this->createExtractor()->getTypes(DoctrineEnum::class, 'enumString'));
        $this->assertEquals([new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, false, EnumInt::class)], $this->createExtractor()->getTypes(DoctrineEnum::class, 'enumInt'));
        $this->assertNull($this->createExtractor()->getTypes(DoctrineEnum::class, 'enumCustom'));
    }

    public function testExtractEnum(): void
    {
        $this->assertEquals(Type::enum(EnumString::class), $this->createExtractor()->getType(DoctrineEnum::class, 'enumString'));
        $this->assertEquals(Type::enum(EnumInt::class), $this->createExtractor()->getType(DoctrineEnum::class, 'enumInt'));
        $this->assertNull($this->createExtractor()->getType(DoctrineEnum::class, 'enumCustom'));
    }

    #[IgnoreDeprecations]
    public static function legacyTypesProvider(): array
    {
        return [
            ['id', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['bin', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['binByteArray', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['binCustom', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['binFunc', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['binMd5', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['binUuid', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['binUuidRfc4122', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['timestamp', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['date', [new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, false, \DateTime::class)]],
            ['dateImmutable', [new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, false, \DateTimeImmutable::class)]],
            ['float', [new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT)]],
            ['bool', [new LegacyType(LegacyType::BUILTIN_TYPE_BOOL)]],
            ['int', [new LegacyType(LegacyType::BUILTIN_TYPE_INT)]],
            ['string', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['key', [new LegacyType(LegacyType::BUILTIN_TYPE_INT)]],
            ['hash', [new LegacyType(LegacyType::BUILTIN_TYPE_ARRAY, false, null, true)]],
            ['collection', [new LegacyType(LegacyType::BUILTIN_TYPE_ARRAY, false, null, true, new LegacyType(LegacyType::BUILTIN_TYPE_INT))]],
            ['objectId', [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]],
            ['raw', null],
            ['foo', [new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, false, DoctrineRelation::class)]],
            ['bar',
                [
                    new LegacyType(
                        LegacyType::BUILTIN_TYPE_OBJECT,
                        false,
                        Collection::class,
                        true,
                        new LegacyType(LegacyType::BUILTIN_TYPE_INT),
                        new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, false, DoctrineRelation::class)
                    ),
                ],
            ],
            ['indexedFoo',
                [
                    new LegacyType(
                        LegacyType::BUILTIN_TYPE_OBJECT,
                        false,
                        Collection::class,
                        true,
                        new LegacyType(LegacyType::BUILTIN_TYPE_INT),
                        new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, false, DoctrineRelation::class)
                    ),
                ],
            ],
            ['customFoo', null],
            ['notMapped', null],
        ];
    }

    /**
     * @return iterable<array{0: string, 1: ?Type}>
     */
    public static function typesProvider(): iterable
    {
        yield ['id', Type::string()];
        yield ['bin', Type::string()];
        yield ['binByteArray', Type::string()];
        yield ['binCustom', Type::string()];
        yield ['binFunc', Type::string()];
        yield ['binMd5', Type::string()];
        yield ['binUuid', Type::string()];
        yield ['binUuidRfc4122', Type::string()];
        yield ['timestamp', Type::string()];
        yield ['date', Type::object(\DateTime::class)];
        yield ['dateImmutable', Type::object(\DateTimeImmutable::class)];
        yield ['float', Type::float()];
        yield ['bool', Type::bool()];
        yield ['int', Type::int()];
        yield ['string', Type::string()];
        yield ['key', Type::int()];
        yield ['hash', Type::array()];
        yield ['collection', Type::list()];
        yield ['objectId', Type::string()];
        yield ['raw', null];
        yield ['foo', Type::object(DoctrineRelation::class)];
        yield ['bar', Type::collection(Type::object(Collection::class), Type::object(DoctrineRelation::class), Type::int())];
        yield ['indexedFoo', Type::collection(Type::object(Collection::class), Type::object(DoctrineRelation::class), Type::int())];
        yield ['customFoo', null];
        yield ['notMapped', null];
    }

    public function testGetPropertiesCatchException(): void
    {
        $this->assertNull($this->createExtractor()->getProperties('Not\Exist'));
    }

    #[IgnoreDeprecations]
    public function testGetTypesCatchExceptionLegacy(): void
    {
        $this->assertNull($this->createExtractor()->getTypes('Not\Exist', 'baz'));
    }

    public function testGetTypesCatchException(): void
    {
        $this->assertNull($this->createExtractor()->getType('Not\Exist', 'baz'));
    }

    public function testGeneratedValueNotWritable(): void
    {
        $extractor = $this->createExtractor();
        $this->assertFalse($extractor->isWritable(DoctrineGeneratedValue::class, 'id'));
        $this->assertNull($extractor->isReadable(DoctrineGeneratedValue::class, 'id'));
        $this->assertNull($extractor->isWritable(DoctrineGeneratedValue::class, 'foo'));
        $this->assertNull($extractor->isReadable(DoctrineGeneratedValue::class, 'foo'));
    }

    #[IgnoreDeprecations]
    public function testGetTypesWithEmbedManyOmittingTargetDocumentLegacy(): void
    {
        $actualTypes = $this->createExtractor()->getTypes(
            DoctrineWithEmbedded::class,
            'embedManyOmittingTargetDocument'
        );

        self::assertNull($actualTypes);
    }

    public function testGetTypesWithEmbedManyOmittingTargetDocument(): void
    {
        $this->assertNull($this->createExtractor()->getType(DoctrineWithEmbedded::class, 'embedManyOmittingTargetDocument'));
    }

    private function createExtractor(): DoctrineExtractor
    {
        $config = DoctrineMongoDbOdmSetup::createAttributeMetadataConfiguration([__DIR__.\DIRECTORY_SEPARATOR], true);
        $documentManager = DocumentManager::create(null, $config);

        if (!MongoDbType::hasType('custom_foo')) {
            MongoDbType::addType('custom_foo', DoctrineFooType::class);
        }

        return new DoctrineExtractor($documentManager);
    }
}
