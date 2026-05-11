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

namespace ApiPlatform\Tests\Functional\Hal;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\AbsoluteUrlChild;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\AbsoluteUrlParent;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class AbsoluteUrlTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [AbsoluteUrlChild::class, AbsoluteUrlParent::class];
    }

    public function testCollectionLinksUseAbsoluteUrls(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/hal_absolute_url_children', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('http://example.com/hal_absolute_url_children', $body['_links']['self']['href']);
        $this->assertSame('http://example.com/hal_absolute_url_children/1', $body['_links']['item'][0]['href']);
        $this->assertSame('http://example.com/hal_absolute_url_children/1', $body['_embedded']['item'][0]['_links']['self']['href']);
        $this->assertSame('http://example.com/hal_absolute_url_parents/1', $body['_embedded']['item'][0]['_links']['parent']['href']);
    }

    public function testItemLinksUseAbsoluteUrls(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/hal_absolute_url_children/1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('http://example.com/hal_absolute_url_children/1', $body['_links']['self']['href']);
        $this->assertSame('http://example.com/hal_absolute_url_parents/1', $body['_links']['parent']['href']);
    }

    public function testPostAcceptsAbsoluteUrlInPayload(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('POST', '/hal_absolute_url_children', [
            'headers' => [
                'Accept' => 'application/hal+json',
                'Content-Type' => 'application/json',
            ],
            'json' => ['parent' => 'http://example.com/hal_absolute_url_parents/1'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('http://example.com/hal_absolute_url_children/2', $body['_links']['self']['href']);
        $this->assertSame('http://example.com/hal_absolute_url_parents/1', $body['_links']['parent']['href']);
    }

    public function testSubresourceCollectionUsesAbsoluteUrls(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/hal_absolute_url_parents/1/children', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('http://example.com/hal_absolute_url_parents/1/children', $body['_links']['self']['href']);
        $this->assertSame('http://example.com/hal_absolute_url_children/1', $body['_links']['item'][0]['href']);
    }
}
