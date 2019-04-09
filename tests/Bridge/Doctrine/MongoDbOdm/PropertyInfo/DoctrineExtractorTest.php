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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\PropertyInfo;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\PropertyInfo\DoctrineExtractor;
use ApiPlatform\Core\Test\DoctrineMongoDbOdmSetup;
use ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\PropertyInfo\Fixtures\DoctrineDummy;
use ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\PropertyInfo\Fixtures\DoctrineEmbeddable;
use ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\PropertyInfo\Fixtures\DoctrineFooType;
use ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\PropertyInfo\Fixtures\DoctrineRelation;
use ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\PropertyInfo\Fixtures\DoctrineWithEmbedded;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @group mongodb
 *
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
                'float',
                'bool',
                'boolean',
                'customFoo',
                'int',
                'integer',
                'string',
                'key',
                'file',
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
            ],
            $this->createExtractor()->getProperties(DoctrineWithEmbedded::class)
        );
    }

    /**
     * @dataProvider typesProvider
     */
    public function testExtract(string $property, array $type = null): void
    {
        $this->assertEquals($type, $this->createExtractor()->getTypes(DoctrineDummy::class, $property));
    }

    public function testExtractWithEmbedOne(): void
    {
        $expectedTypes = [
            new Type(
                Type::BUILTIN_TYPE_OBJECT,
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

    public function testExtractWithEmbedMany(): void
    {
        $expectedTypes = [
            new Type(
                Type::BUILTIN_TYPE_OBJECT,
                false,
                Collection::class,
                true,
                new Type(Type::BUILTIN_TYPE_INT),
                new Type(Type::BUILTIN_TYPE_OBJECT, false, DoctrineEmbeddable::class)
            ),
        ];

        $actualTypes = $this->createExtractor()->getTypes(
            DoctrineWithEmbedded::class,
            'embedMany'
        );

        $this->assertEquals($expectedTypes, $actualTypes);
    }

    public function typesProvider(): array
    {
        return [
            ['id', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['bin', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['binByteArray', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['binCustom', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['binFunc', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['binMd5', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['binUuid', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['binUuidRfc4122', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['timestamp', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['date', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime')]],
            ['float', [new Type(Type::BUILTIN_TYPE_FLOAT)]],
            ['bool', [new Type(Type::BUILTIN_TYPE_BOOL)]],
            ['boolean', [new Type(Type::BUILTIN_TYPE_BOOL)]],
            ['int', [new Type(Type::BUILTIN_TYPE_INT)]],
            ['integer', [new Type(Type::BUILTIN_TYPE_INT)]],
            ['string', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['key', [new Type(Type::BUILTIN_TYPE_INT)]],
            ['file', null],
            ['hash', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true)]],
            ['collection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(TYPE::BUILTIN_TYPE_INT))]],
            ['objectId', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['raw', null],
            ['foo', [new Type(Type::BUILTIN_TYPE_OBJECT, false, DoctrineRelation::class)]],
            ['bar',
                [
                    new Type(
                        Type::BUILTIN_TYPE_OBJECT,
                        false,
                        Collection::class,
                        true,
                        new Type(Type::BUILTIN_TYPE_INT),
                        new Type(Type::BUILTIN_TYPE_OBJECT, false, DoctrineRelation::class)
                    ),
                ],
            ],
            ['indexedFoo',
                [
                    new Type(
                        Type::BUILTIN_TYPE_OBJECT,
                        false,
                        Collection::class,
                        true,
                        new Type(Type::BUILTIN_TYPE_INT),
                        new Type(Type::BUILTIN_TYPE_OBJECT, false, DoctrineRelation::class)
                    ),
                ],
            ],
            ['customFoo', null],
            ['notMapped', null],
        ];
    }

    public function testGetPropertiesCatchException(): void
    {
        $this->assertNull($this->createExtractor()->getProperties('Not\Exist'));
    }

    public function testGetTypesCatchException(): void
    {
        $this->assertNull($this->createExtractor()->getTypes('Not\Exist', 'baz'));
    }

    private function createExtractor(): DoctrineExtractor
    {
        $config = DoctrineMongoDbOdmSetup::createAnnotationMetadataConfiguration([__DIR__.\DIRECTORY_SEPARATOR.'Fixtures'], true);
        $documentManager = DocumentManager::create(null, $config);

        if (!MongoDbType::hasType('foo')) {
            MongoDbType::addType('foo', DoctrineFooType::class);
        }

        return new DoctrineExtractor($documentManager);
    }
}
