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

namespace ApiPlatform\Tests\Util;

use ApiPlatform\Util\RequestParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

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

    /**
     * @dataProvider getQueryStringProvider
     */
    public function testGetQueryString(Request $request, ?string $expected): void
    {
        $actual = RequestParser::getQueryString($request);
        $this->assertSame($expected, $actual);
    }

    public function parseRequestParamsProvider(): array
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

    public function getQueryStringProvider(): array
    {
        return [
            'No query string' => [new Request(), null],
            'With missing param' => [new Request(server: ['QUERY_STRING' => 'foo=bar&']), 'foo=bar'],
            'With no param key' => [new Request(server: ['QUERY_STRING' => 'foo=bar&=baz']), 'foo=bar'],
            'With space' => [new Request(server: ['QUERY_STRING' => 'foo=bar baz&key=value']), 'foo=bar%20baz&key=value'],
            'With urlencoded + (plus)' => [new Request(server: ['QUERY_STRING' => 'date=2000-01-01T00%3A00%3A00%2B00%3A00&key=value']), 'date=2000-01-01T00%3A00%3A00%2B00%3A00&key=value'],
            'With not urlencoded + (plus)' => [new Request(server: ['QUERY_STRING' => 'date=2000-01-01T00:00:00+00:00&key=value']), 'date=2000-01-01T00%3A00%3A00%2B00%3A00&key=value'],
        ];
    }
}
