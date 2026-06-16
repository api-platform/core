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

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class IriHelperTest extends TestCase
{
    public function testHelpers(): void
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

    public function testHelpersPreserveNestedArrayQueryParameters(): void
    {
        $parts = [
            'path' => '/objects',
            'query' => '',
        ];
        $parameters = [
            'filters' => [
                0 => ['a' => 'foo', 'b' => 'bar'],
            ],
        ];

        $iri = IriHelper::createIri($parts, $parameters, 'page', 2.);

        $this->assertSame('/objects?filters%5B0%5D%5Ba%5D=foo&filters%5B0%5D%5Bb%5D=bar&page=2', $iri);

        $reparsed = IriHelper::parseIri($iri, 'page');
        $this->assertSame($parameters, $reparsed['parameters']);
    }

    public function testHelpersCollapseSimpleListQueryParameters(): void
    {
        $parts = [
            'path' => '/objects',
            'query' => '',
        ];
        $parameters = [
            'foo' => ['a', 'b'],
        ];

        $iri = IriHelper::createIri($parts, $parameters, 'page', 2.);

        $this->assertSame('/objects?foo%5B%5D=a&foo%5B%5D=b&page=2', $iri);
    }

    public function testParseIriWithInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The request URI "http:///" is malformed.');

        IriHelper::parseIri('http:///', 'page');
    }
}
