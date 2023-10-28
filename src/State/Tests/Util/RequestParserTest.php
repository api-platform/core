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

namespace ApiPlatform\State\Tests\Util;

use ApiPlatform\State\Util\RequestParser;
use PHPUnit\Framework\TestCase;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class RequestParserTest extends TestCase
{
    /**
     * @dataProvider parseRequestParamsProvider
     */
    public function testParseRequestParams(string $source, array $expected): void
    {
        $actual = RequestParser::parseRequestParams($source);
        $this->assertSame($expected, $actual);
    }

    public static function parseRequestParamsProvider(): array
    {
        return [
            ['gerard.name=dargent', ['gerard.name' => 'dargent']],

            // urlencoded + (plus) in query string.
            ['date=2000-01-01T00%3A00%3A00%2B00%3A00', ['date' => '2000-01-01T00:00:00+00:00']],

            // urlencoded % (percent sign) in query string.
            ['%2525=%2525', ['%25' => '%25']],

            // urlencoded [] (square brackets) in query string.
            ['a%5B1%5D=%2525', ['a' => ['1' => '%25']]],
        ];
    }
}
