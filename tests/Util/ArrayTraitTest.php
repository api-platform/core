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

namespace ApiPlatform\Core\Tests\Util;

use ApiPlatform\Core\Util\ArrayTrait;
use PHPUnit\Framework\TestCase;

class ArrayTraitTest extends TestCase
{
    private $arrayTraitClass;

    public function setUp(): void
    {
        $this->arrayTraitClass = (new class {
            use ArrayTrait;
        });
    }

    public function testIsSequentialArrayWithEmptyArray()
    {
        self::assertFalse($this->arrayTraitClass->isSequentialArray([]));
    }

    public function testIsSequentialArrayWithNonNumericIndex()
    {
        self::assertFalse($this->arrayTraitClass->isSequentialArray(["foo" => "bar"]));
    }

    public function testIsSequentialArrayWithNumericNonContinuousIndex()
    {
        self::assertFalse($this->arrayTraitClass->isSequentialArray([1 => "bar", 3 => 'foo']));
    }

    public function testIsSequentialArrayWithNumericContinuousIndex()
    {
        self::assertTrue($this->arrayTraitClass->isSequentialArray([0 => "bar", 1 => 'foo']));
    }

    public function testArrayContainsOnlyWithDifferentTypes()
    {
        self::assertFalse($this->arrayTraitClass->arrayContainsOnly([1, "foo"], gettype("")));
    }

    public function testArrayContainsOnlyWithSameType()
    {
        self::assertTrue($this->arrayTraitClass->arrayContainsOnly(["foo", "bar"], gettype("")));
    }

    public function testIsNumericIndexArrayOfArray()
    {
        self::assertFalse($this->arrayTraitClass->isNumericIndexArrayOfArray([]));
        self::assertTrue($this->arrayTraitClass->isNumericIndexArrayOfArray([["foo"], ["bar"]]));
    }
}
