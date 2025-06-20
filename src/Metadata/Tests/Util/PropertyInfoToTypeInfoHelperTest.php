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

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Metadata\Tests\Util;

use ApiPlatform\Metadata\Util\PropertyInfoToTypeInfoHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

class PropertyInfoToTypeInfoHelperTest extends TestCase
{
    /**
     * @param list<LegacyType>|null $legacyTypes
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('convertLegacyTypesToTypeDataProvider')]
    public function testConvertLegacyTypesToType(?Type $type, ?array $legacyTypes): void
    {
        $this->assertEquals($type, PropertyInfoToTypeInfoHelper::convertLegacyTypesToType($legacyTypes));
    }

    /**
     * @return iterable<array{0: ?Type, 1: list<LegacyType>|null}>
     */
    public static function convertLegacyTypesToTypeDataProvider(): iterable
    {
        yield [null, null];
        yield [Type::null(), [new LegacyType('null')]];
        // yield [Type::void(), [new LegacyType('void')]];
        yield [Type::int(), [new LegacyType('int')]];
        yield [Type::object(\stdClass::class), [new LegacyType('object', false, \stdClass::class)]];
        yield [
            Type::generic(Type::object(\stdClass::class), Type::string(), Type::int()),
            [new LegacyType('object', false, 'stdClass', false, [new LegacyType('string')], new LegacyType('int'))],
        ];
        yield [Type::nullable(Type::int()), [new LegacyType('int', true)]];
        yield [Type::union(Type::int(), Type::string()), [new LegacyType('int'), new LegacyType('string')]];
        yield [
            Type::union(Type::int(), Type::string(), Type::null()),
            [new LegacyType('int', true), new LegacyType('string', true)],
        ];

        $type = Type::collection(Type::builtin(TypeIdentifier::ARRAY), Type::int(), Type::string()); // @phpstan-ignore-line
        yield [$type, [new LegacyType('array', false, null, true, [new LegacyType('string')], new LegacyType('int'))]];
    }

    /**
     * @param list<LegacyType>|null $legacyTypes
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('convertTypeToLegacyTypesDataProvider')]
    public function testConvertTypeToLegacyTypes(?array $legacyTypes, ?Type $type): void
    {
        $this->assertEquals($legacyTypes, PropertyInfoToTypeInfoHelper::convertTypeToLegacyTypes($type));
    }

    /**
     * @return iterable<array{0: list<LegacyType>|null, 1: ?Type, 2?: bool}>
     */
    public static function convertTypeToLegacyTypesDataProvider(): iterable
    {
        yield [null, null];
        yield [null, Type::mixed()];
        yield [null, Type::never()];
        yield [[new LegacyType('null')], Type::null()];
        yield [[new LegacyType('null')], Type::void()];
        yield [[new LegacyType('int')], Type::int()];
        yield [[new LegacyType('object', false, \stdClass::class)], Type::object(\stdClass::class)];
        yield [
            [new LegacyType('object', false, \Traversable::class, true, null, new LegacyType('int'))],
            Type::generic(Type::object(\Traversable::class), Type::int()),
        ];
        yield [
            [new LegacyType('array', false, null, true, new LegacyType('int'), new LegacyType('string'))],
            Type::generic(Type::builtin(TypeIdentifier::ARRAY), Type::int(), Type::string()), // @phpstan-ignore-line
        ];
        yield [
            [new LegacyType('array', false, null, true, new LegacyType('int'), new LegacyType('string'))],
            Type::collection(Type::builtin(TypeIdentifier::ARRAY), Type::string(), Type::int()), // @phpstan-ignore-line
        ];
        yield [[new LegacyType('int', true)], Type::nullable(Type::int())];
        yield [[new LegacyType('int'), new LegacyType('string')], Type::union(Type::int(), Type::string())];
        yield [
            [new LegacyType('int', true), new LegacyType('string', true)],
            Type::union(Type::int(), Type::string(), Type::null()),
        ];
        yield [[new LegacyType('object', false, \Stringable::class), new LegacyType('object', false, \Traversable::class)], Type::intersection(Type::object(\Traversable::class), Type::object(\Stringable::class))];
    }
}
