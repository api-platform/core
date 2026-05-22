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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6384\AcceptHtml;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\WildcardAcceptFormat\WildcardAcceptFormat;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class FormatTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [AcceptHtml::class, WildcardAcceptFormat::class];
    }

    public function testShouldReturnHtml(): void
    {
        $r = self::createClient()->request('GET', '/accept_html', ['headers' => ['Accept' => 'text/html']]);
        $this->assertResponseIsSuccessful();
        $this->assertEquals($r->getContent(), '<h1>hello</h1>');
    }

    /**
     * @see https://github.com/api-platform/core/issues/1532
     */
    public function testWildcardAcceptHeaderPicksFirstConfiguredFormat(): void
    {
        $response = self::createClient()->request('GET', '/wildcard_accept_format/1', ['headers' => ['Accept' => '*/*']]);
        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith('application/ld+json', $response->getHeaders()['content-type'][0]);
    }

    /**
     * @see https://github.com/api-platform/core/issues/1532
     */
    public function testWildcardAcceptHeaderWithParametersPicksFirstConfiguredFormat(): void
    {
        $response = self::createClient()->request('GET', '/wildcard_accept_format/1', ['headers' => ['Accept' => '*/*; charset=utf-8']]);
        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith('application/ld+json', $response->getHeaders()['content-type'][0]);
    }

    public function testWildcardAcceptHeaderRespectsQualityOfConcreteType(): void
    {
        $response = self::createClient()->request('GET', '/wildcard_accept_format/1', ['headers' => ['Accept' => '*/*; charset=utf-8; q=0.1, text/html; q=0.9']]);
        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith('text/html', $response->getHeaders()['content-type'][0]);
    }

    public function testConcreteAcceptHeaderWithUnsupportedTypeReturnsNotAcceptable(): void
    {
        self::createClient()->request('GET', '/wildcard_accept_format/1', ['headers' => ['Accept' => 'application/xml']]);
        $this->assertResponseStatusCodeSame(406);
    }
}
