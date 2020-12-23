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

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Util\IriHelper;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class IriHelperTest extends TestCase
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

    /**
     * @group legacy
     * @expectedDeprecation Passing a bool as 5th parameter to "ApiPlatform\Core\Util\IriHelper::createIri()" is deprecated since API Platform 2.6. Pass an "ApiPlatform\Core\Api\UrlGeneratorInterface" constant (int) instead.
     */
    public function testLegacyHelpers()
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
        $this->assertEquals('/hello.json?foo=bar&bar=3&page=2', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2., false));
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing a bool as 5th parameter to "ApiPlatform\Core\Util\IriHelper::createIri()" is deprecated since API Platform 2.6. Pass an "ApiPlatform\Core\Api\UrlGeneratorInterface" constant (int) instead.
     */
    public function testLegacyHelpersWithAbsoluteUrl()
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

    public function testHelpersWithNetworkPath()
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

        $this->assertEquals('//foo:bar@localhost:8080/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2., UrlGeneratorInterface::NET_PATH));

        unset($parsed['parts']['scheme']);

        $this->assertEquals('//foo:bar@localhost:8080/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2., UrlGeneratorInterface::NET_PATH));

        $parsed['parts']['port'] = 443;

        $this->assertEquals('//foo:bar@localhost:443/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2., UrlGeneratorInterface::NET_PATH));
    }

    public function testParseIriWithInvalidUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The request URI "http:///" is malformed.');

        IriHelper::parseIri('http:///', 'page');
    }
}
