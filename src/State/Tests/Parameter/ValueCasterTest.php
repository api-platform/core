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

namespace ApiPlatform\State\Tests\Parameter;

use ApiPlatform\Metadata\Exception\BadRequestException;
use ApiPlatform\State\Parameter\ValueCaster;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValueCasterTest extends TestCase
{
    #[DataProvider('boolProvider')]
    public function testToBool(mixed $value, mixed $expected): void
    {
        $this->assertSame($expected, ValueCaster::toBool($value));
    }

    public static function boolProvider(): \Generator
    {
        yield 'true string' => ['true', true];
        yield 'numeric 1' => ['1', true];
        yield 'false string' => ['false', false];
        yield 'numeric 0' => ['0', false];
        // Unrecognized values (including "null") are returned untouched so constraint validation
        // rejects them.
        yield 'invalid string' => ['string', 'string'];
        yield 'null string is not cast' => ['null', 'null'];
        yield 'non-string passthrough' => [true, true];
    }

    #[DataProvider('intProvider')]
    public function testToInt(mixed $value, mixed $expected): void
    {
        $this->assertSame($expected, ValueCaster::toInt($value));
    }

    public static function intProvider(): \Generator
    {
        yield 'integer string' => ['10', 10];
        yield 'invalid string' => ['string', 'string'];
        yield 'null string is not cast' => ['null', 'null'];
        yield 'int passthrough' => [10, 10];
    }

    #[DataProvider('floatProvider')]
    public function testToFloat(mixed $value, mixed $expected): void
    {
        $this->assertSame($expected, ValueCaster::toFloat($value));
    }

    public static function floatProvider(): \Generator
    {
        yield 'float string' => ['1.5', 1.5];
        yield 'invalid string' => ['string', 'string'];
        yield 'null string is not cast' => ['null', 'null'];
        yield 'float passthrough' => [1.5, 1.5];
    }

    /**
     * An empty string cannot represent a scalar native type, so the caster rejects it with a
     * Bad Request rather than leaving a raw value for the filter.
     */
    #[DataProvider('emptyCasterProvider')]
    public function testEmptyValueThrowsBadRequest(callable $caster): void
    {
        $this->expectException(BadRequestException::class);
        $caster('');
    }

    public static function emptyCasterProvider(): \Generator
    {
        yield 'toBool' => [ValueCaster::toBool(...)];
        yield 'toInt' => [ValueCaster::toInt(...)];
        yield 'toFloat' => [ValueCaster::toFloat(...)];
    }
}
