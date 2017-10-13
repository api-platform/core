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

use ApiPlatform\Core\Util\IriHelper;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class IriHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testHelpers()
    {
        $parsed = [
            'parts' => [
                'path' => '/hello.json',
                'query' => 'foo=bar&page=2&bar=3',
            ],
           'parameters' => [
                'foo' => 'bar',
                'bar' => '3',
            ],
        ];

        $this->assertEquals($parsed, IriHelper::parseIri('/hello.json?foo=bar&page=2&bar=3', 'page'));
        $this->assertEquals('/hello.json?foo=bar&bar=3&page=2', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2.));
    }

    public function testHelpersWithAbsoluteUrl()
    {
        $parsed = [
            'parts' => [
                'path' => '/hello.json',
                'query' => 'foo=bar&page=2&bar=3',
                'scheme' => 'http',
                'user' => 'foo',
                'pass' => 'bar',
                'host' => 'localhost',
                'port' => 8080,
                'fragment' => 'foo',
            ],
            'parameters' => [
                'foo' => 'bar',
                'bar' => '3',
            ],
        ];

        $this->assertEquals($parsed, IriHelper::parseIri('http://foo:bar@localhost:8080/hello.json?foo=bar&page=2&bar=3#foo', 'page'));
        $this->assertEquals('http://foo:bar@localhost:8080/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2., true));

        unset($parsed['parts']['scheme']);

        $this->assertEquals('http://foo:bar@localhost:8080/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2., true));

        $parsed['parts']['port'] = 443;

        $this->assertEquals('https://foo:bar@localhost:443/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2., true));
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The request URI "http:///" is malformed.
     */
    public function testParseIriWithInvalidUrl()
    {
        IriHelper::parseIri('http:///', 'page');
    }
}
