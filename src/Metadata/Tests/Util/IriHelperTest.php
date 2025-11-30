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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\IriHelper;
use PHPUnit\Framework\TestCase;
use Uri\Rfc3986\Uri;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class IriHelperTest extends TestCase
{
    public function testHelpers(): void
    {
        if (PHP_VERSION_ID >= 80500 && class_exists(Uri::class)) {
            self::markTestSkipped('Parsing url with former "parse_url()" method is not available after PHP8.5 and ext-uri');
        }

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

        $this->assertSame($parsed, IriHelper::parseIri('/hello.json?foo=bar&page=2&bar=3', 'page'));
        $this->assertSame('/hello.json?foo=bar&bar=3&page=2', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2.));
    }

    public function testHelpersWithNetworkPath(): void
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

        $this->assertSame('//foo:bar@localhost:8080/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2., UrlGeneratorInterface::NET_PATH));

        unset($parsed['parts']['scheme']);

        $this->assertSame('//foo:bar@localhost:8080/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2., UrlGeneratorInterface::NET_PATH));

        $parsed['parts']['port'] = 443;

        $this->assertSame('//foo:bar@localhost:443/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['parts'], $parsed['parameters'], 'page', 2., UrlGeneratorInterface::NET_PATH));
    }

    public function testHelpersWithRFC3986(): void
    {
        if (PHP_VERSION_ID < 80500 || !class_exists(Uri::class)) {
            self::markTestSkipped('RFC 3986 URI parser needs PHP 8.5 or higher and php-uri extension.');
        }

        $parsed = [
            'uri' => new Uri('/hello.json?foo=bar&page=2&bar=3'),
            'parameters' => [
                'foo' => 'bar',
                'bar' => '3',
            ],
        ];


        $this->assertEquals($parsed, IriHelper::parseIri('/hello.json?foo=bar&page=2&bar=3', 'page'));
        $this->assertSame('/hello.json?foo=bar&bar=3&page=2', IriHelper::createIri($parsed['uri'], $parsed['parameters'], 'page', 2.));
    }

    public function testHelpersWithNetworkPathAndRFC3986(): void
    {
        if (PHP_VERSION_ID < 80500 || !class_exists(Uri::class)) {
            self::markTestSkipped('RFC 3986 URI parser needs PHP 8.5 or higher and php-uri extension.');
        }

        $parsed = [
            'uri' => $uri = new Uri('/hello.json')
                ->withQuery('foo=bar&page=2&bar=3')
                ->withScheme('http')
                ->withHost('localhost')
                ->withUserInfo('foo:bar')
                ->withPort(8080)
                ->withFragment('foo'),
            'parameters' => [
                'foo' => 'bar',
                'bar' => '3',
            ],
        ];

        $this->assertSame('//foo:bar@localhost:8080/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['uri'], $parsed['parameters'], 'page', 2., UrlGeneratorInterface::NET_PATH));

        $parsed['uri'] = $uri->withScheme(null);

        $this->assertSame('//foo:bar@localhost:8080/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['uri'], $parsed['parameters'], 'page', 2., UrlGeneratorInterface::NET_PATH));

        $parsed['uri'] = $uri->withPort(443);

        $this->assertSame('//foo:bar@localhost:443/hello.json?foo=bar&bar=3&page=2#foo', IriHelper::createIri($parsed['uri'], $parsed['parameters'], 'page', 2., UrlGeneratorInterface::NET_PATH));
    }

    public function testParseIriWithInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The request URI "http:///" is malformed.');

        IriHelper::parseIri('http:///', 'page');
    }
}
