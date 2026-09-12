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

namespace ApiPlatform\Tests\Functional\JsonApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\AbsoluteUrlDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\AbsoluteUrlRelationDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class AbsoluteUrlTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [AbsoluteUrlDummy::class, AbsoluteUrlRelationDummy::class];
    }

    public function testCollectionUsesAbsoluteUrls(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/jsonapi_absolute_url_dummies', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame('http://example.com/jsonapi_absolute_url_dummies', $body['links']['self']);
        $this->assertSame('http://example.com/jsonapi_absolute_url_dummies/1', $body['data'][0]['id']);
        $this->assertSame('JsonApiAbsoluteUrlDummy', $body['data'][0]['type']);
        $this->assertSame(
            'http://example.com/jsonapi_absolute_url_relation_dummies/1',
            $body['data'][0]['relationships']['absoluteUrlRelationDummy']['data']['id'],
        );
    }

    public function testItemUsesAbsoluteUrls(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/jsonapi_absolute_url_dummies/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('http://example.com/jsonapi_absolute_url_dummies/1', $body['data']['id']);
        $this->assertSame('JsonApiAbsoluteUrlDummy', $body['data']['type']);
        $this->assertSame(
            'http://example.com/jsonapi_absolute_url_relation_dummies/1',
            $body['data']['relationships']['absoluteUrlRelationDummy']['data']['id'],
        );
    }

    public function testPostReturnsAbsoluteUrl(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('POST', '/jsonapi_absolute_url_relation_dummies', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => ['data' => ['type' => 'JsonApiAbsoluteUrlRelationDummy']],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('http://example.com/jsonapi_absolute_url_relation_dummies/2', $body['data']['id']);
        $this->assertSame('JsonApiAbsoluteUrlRelationDummy', $body['data']['type']);
    }

    public function testSubresourceCollectionUsesAbsoluteUrls(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/jsonapi_absolute_url_relation_dummies/1/absolute_url_dummies', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame(
            'http://example.com/jsonapi_absolute_url_relation_dummies/1/absolute_url_dummies',
            $body['links']['self'],
        );
        $this->assertSame('http://example.com/jsonapi_absolute_url_dummies/1', $body['data'][0]['id']);
    }
}
