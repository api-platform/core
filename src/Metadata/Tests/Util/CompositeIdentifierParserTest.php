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

namespace ApiPlatform\Metadata\Tests\Util;

use ApiPlatform\Metadata\Util\CompositeIdentifierParser;
use PHPUnit\Framework\TestCase;

class CompositeIdentifierParserTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('variousIdentifiers')]
    public function testNormalizeCompositeCorrectly(array $identifiers): void
    {
        foreach ($identifiers as $string => $expected) {
            $this->assertSame(CompositeIdentifierParser::parse($string), $expected);
        }
    }

    public static function variousIdentifiers(): array
    {
        return [[[
            'a=bd;dc=d' => ['a' => 'bd', 'dc' => 'd'],
            'a=b;c=d foo;d23i=e' => ['a' => 'b', 'c' => 'd foo', 'd23i' => 'e'],
            'a=1;c=2;d=10-30-24' => ['a' => '1', 'c' => '2', 'd' => '10-30-24'],
            'a=test;b=bar;foo;c=123' => ['a' => 'test', 'b' => 'bar;foo', 'c' => '123'],
            'a=test;b=bar ;foo;c=123;459;barz=123asgfjasdg4;' => ['a' => 'test', 'b' => 'bar ;foo', 'c' => '123;459', 'barz' => '123asgfjasdg4'],
            'foo=test=bar;;bar=bazzz;' => ['foo' => 'test=bar;', 'bar' => 'bazzz'],
            'foo=test=bar;bar=;test=foo' => ['foo' => 'test=bar', 'bar' => '', 'test' => 'foo'],
            'foo=test=bar;bar=' => ['foo' => 'test=bar', 'bar' => ''],
        ]]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('compositeIdentifiers')]
    public function testStringify(array $identifiers): void
    {
        foreach ($identifiers as $string => $arr) {
            $this->assertSame(CompositeIdentifierParser::stringify($arr), $string);
        }
    }

    public static function compositeIdentifiers(): array
    {
        return [[[
            'a=bd;dc=d' => ['a' => 'bd', 'dc' => 'd'],
            'a=b;c=d foo;d23i=e' => ['a' => 'b', 'c' => 'd foo', 'd23i' => 'e'],
            'a=1;c=2;d=10-30-24' => ['a' => '1', 'c' => '2', 'd' => '10-30-24'],
            'a=test;b=bar;foo;c=123' => ['a' => 'test', 'b' => 'bar;foo', 'c' => '123'],
            'foo=test=bar;;bar=bazzz' => ['foo' => 'test=bar;', 'bar' => 'bazzz'],
            'foo=test=bar;bar=;test=foo' => ['foo' => 'test=bar', 'bar' => '', 'test' => 'foo'],
            'foo=test=bar;bar=' => ['foo' => 'test=bar', 'bar' => ''],
        ]]];
    }
}
