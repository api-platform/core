<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


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

    public function testParseRequestParams()
    {
        $value = RequestParser::parseRequestParams('gerard.name=dargent');
        $this->assertEquals($value, ['gerard.name' => 'dargent']);
    }
}
