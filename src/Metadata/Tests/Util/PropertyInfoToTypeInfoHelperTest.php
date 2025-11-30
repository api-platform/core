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
    public function testConvertLegacyTypesToType(): void
    {
        if (!class_exists(LegacyType::class)) {
            $this->markTestSkipped();
        }

        $type = Type::collection(Type::builtin(TypeIdentifier::ARRAY), Type::int(), Type::string()); // @phpstan-ignore-line

        $tests = [
            [null, null],
            [Type::null(), [new LegacyType('null')]],
            // [Type::void(), [new LegacyType('void')]],
            [Type::int(), [new LegacyType('int')]],
            [Type::object(\stdClass::class), [new LegacyType('object', false, \stdClass::class)]],
            [
                Type::generic(Type::object(\stdClass::class), Type::string(), Type::int()),
                [new LegacyType('object', false, 'stdClass', false, [new LegacyType('string')], new LegacyType('int'))],
            ],
            [Type::nullable(Type::int()), [new LegacyType('int', true)]],
            [Type::union(Type::int(), Type::string()), [new LegacyType('int'), new LegacyType('string')]],
            [
                Type::union(Type::int(), Type::string(), Type::null()),
                [new LegacyType('int', true), new LegacyType('string', true)],
            ],
            [$type, [new LegacyType('array', false, null, true, [new LegacyType('string')], new LegacyType('int'))]],
        ];

        foreach ($tests as [$expected, $legacyTypes]) {
            $this->assertEquals($expected, PropertyInfoToTypeInfoHelper::convertLegacyTypesToType($legacyTypes));
        }
    }

    public function testConvertTypeToLegacyTypes(): void
    {
        if (!class_exists(LegacyType::class)) {
            $this->markTestSkipped();
        }

        $tests = [
            [null, null],
            [null, Type::mixed()],
            [null, Type::never()],
            [[new LegacyType('null')], Type::null()],
            [[new LegacyType('null')], Type::void()],
            [[new LegacyType('int')], Type::int()],
            [[new LegacyType('object', false, \stdClass::class)], Type::object(\stdClass::class)],
            [
                [new LegacyType('object', false, \Traversable::class, true, null, new LegacyType('int'))],
                Type::generic(Type::object(\Traversable::class), Type::int()),
            ],
            [
                [new LegacyType('array', false, null, true, new LegacyType('int'), new LegacyType('string'))],
                Type::generic(Type::builtin(TypeIdentifier::ARRAY), Type::int(), Type::string()), // @phpstan-ignore-line
            ],
            [
                [new LegacyType('array', false, null, true, new LegacyType('int'), new LegacyType('string'))],
                Type::collection(Type::builtin(TypeIdentifier::ARRAY), Type::string(), Type::int()), // @phpstan-ignore-line
            ],
            [[new LegacyType('int', true)], Type::nullable(Type::int())],
            [[new LegacyType('int'), new LegacyType('string')], Type::union(Type::int(), Type::string())],
            [
                [new LegacyType('int', true), new LegacyType('string', true)],
                Type::union(Type::int(), Type::string(), Type::null()),
            ],
            [[new LegacyType('object', false, \Stringable::class), new LegacyType('object', false, \Traversable::class)], Type::intersection(Type::object(\Traversable::class), Type::object(\Stringable::class))],
        ];

        foreach ($tests as [$expected, $type]) {
            $this->assertEquals($expected, PropertyInfoToTypeInfoHelper::convertTypeToLegacyTypes($type));
        }
    }
}
