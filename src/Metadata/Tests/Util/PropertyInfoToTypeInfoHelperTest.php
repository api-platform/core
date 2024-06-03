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

/**
 * @group legacy
 */
class PropertyInfoToTypeInfoHelperTest extends TestCase
{
    /**
     * @dataProvider convertLegacyTypesToTypeDataProvider
     *
     * @param list<LegacyType>|null $legacyTypes
     */
    public function testConvertLegacyTypesToType(?Type $type, ?array $legacyTypes): void
    {
        if (!class_exists(Type::class)) {
            $this->markTestSkipped('symfony/type-info requires PHP > 8.2');
        }

        $this->assertEquals($type, PropertyInfoToTypeInfoHelper::convertLegacyTypesToType($legacyTypes));
    }

    /**
     * @return iterable<array{0: ?Type, 1: list<LegacyType>|null}>
     */
    public function convertLegacyTypesToTypeDataProvider(): iterable
    {
        if (!class_exists(Type::class)) {
            return;
        }

        yield [null, null];
        yield [Type::null(), [new LegacyType('null')]];
        // yield [Type::void(), [new LegacyType('void')]];
        yield [Type::int(), [new LegacyType('int')]];
        yield [Type::object(\stdClass::class), [new LegacyType('object', false, \stdClass::class)]];
        yield [
            Type::generic(Type::object('Foo'), Type::string(), Type::int()), // @phpstan-ignore-line
            [new LegacyType('object', false, 'Foo', false, [new LegacyType('string')], new LegacyType('int'))],
        ];
        yield [Type::nullable(Type::int()), [new LegacyType('int', true)]]; // @phpstan-ignore-line
        yield [Type::union(Type::int(), Type::string()), [new LegacyType('int'), new LegacyType('string')]];
        yield [
            Type::union(Type::int(), Type::string(), Type::null()),
            [new LegacyType('int', true), new LegacyType('string', true)],
        ];

        $type = Type::collection(Type::builtin(TypeIdentifier::ARRAY), Type::int(), Type::string()); // @phpstan-ignore-line
        yield [$type, [new LegacyType('array', false, null, true, [new LegacyType('string')], new LegacyType('int'))]];
    }
}
