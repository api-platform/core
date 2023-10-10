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

namespace ApiPlatform\GraphQl\Tests\Util;

use ApiPlatform\GraphQl\Util\ArrayTrait;
use PHPUnit\Framework\TestCase;

class ArrayTraitTest extends TestCase
{
    private object $arrayTraitClass;

    protected function setUp(): void
    {
        $this->arrayTraitClass = (new class() {
            use ArrayTrait;
        });
    }

    public function testIsSequentialArrayWithEmptyArray(): void
    {
        self::assertFalse($this->arrayTraitClass->isSequentialArray([]));
    }

    public function testIsSequentialArrayWithNonNumericIndex(): void
    {
        self::assertFalse($this->arrayTraitClass->isSequentialArray(['foo' => 'bar']));
    }

    public function testIsSequentialArrayWithNumericNonSequentialIndex(): void
    {
        self::assertFalse($this->arrayTraitClass->isSequentialArray([1 => 'bar', 3 => 'foo']));
    }

    public function testIsSequentialArrayWithNumericSequentialIndex(): void
    {
        self::assertTrue($this->arrayTraitClass->isSequentialArray([0 => 'bar', 1 => 'foo']));
    }

    public function testArrayContainsOnlyWithDifferentTypes(): void
    {
        self::assertFalse($this->arrayTraitClass->arrayContainsOnly([1, 'foo'], 'string'));
    }

    public function testArrayContainsOnlyWithSameType(): void
    {
        self::assertTrue($this->arrayTraitClass->arrayContainsOnly(['foo', 'bar'], 'string'));
    }

    public function testIsSequentialArrayOfArrays(): void
    {
        self::assertFalse($this->arrayTraitClass->isSequentialArrayOfArrays([]));
        self::assertTrue($this->arrayTraitClass->isSequentialArrayOfArrays([['foo'], ['bar']]));
    }
}
