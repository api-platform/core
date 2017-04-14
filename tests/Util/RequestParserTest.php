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

use ApiPlatform\Core\Util\RequestParser;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class RequestParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParseAndDupplicateRequest()
    {
        $request = new Request(['toto=tata'], [], [], [], [], [], '{"gerard":"toto"}');
        $value = RequestParser::parseAndDuplicateRequest($request);
        $this->assertNotNull($value);
    }

    /**
     * @dataProvider parseRequestParamsProvider
     */
    public function testParseRequestParams($source, $expected)
    {
        $actual = RequestParser::parseRequestParams($source);
        $this->assertEquals($expected, $actual);
    }

    public function parseRequestParamsProvider()
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
