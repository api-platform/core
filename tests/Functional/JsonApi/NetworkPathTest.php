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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\NetworkPathDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\NetworkPathRelationDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NetworkPathTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [NetworkPathDummy::class, NetworkPathRelationDummy::class];
    }

    public function testCollectionUsesNetworkPaths(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/jsonapi_network_path_dummies', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('//example.com/jsonapi_network_path_dummies', $body['links']['self']);
        $this->assertSame('//example.com/jsonapi_network_path_dummies/1', $body['data'][0]['id']);
        $this->assertSame('JsonApiNetworkPathDummy', $body['data'][0]['type']);
        $this->assertSame(
            '//example.com/jsonapi_network_path_relation_dummies/1',
            $body['data'][0]['relationships']['networkPathRelationDummy']['data']['id'],
        );
    }

    public function testItemUsesNetworkPaths(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/jsonapi_network_path_dummies/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('//example.com/jsonapi_network_path_dummies/1', $body['data']['id']);
        $this->assertSame('JsonApiNetworkPathDummy', $body['data']['type']);
        $this->assertSame(
            '//example.com/jsonapi_network_path_relation_dummies/1',
            $body['data']['relationships']['networkPathRelationDummy']['data']['id'],
        );
    }

    public function testPostReturnsNetworkPath(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('POST', '/jsonapi_network_path_relation_dummies', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => ['data' => ['type' => 'JsonApiNetworkPathRelationDummy']],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('//example.com/jsonapi_network_path_relation_dummies/2', $body['data']['id']);
    }

    public function testSubresourceCollectionUsesNetworkPaths(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/jsonapi_network_path_relation_dummies/1/network_path_dummies', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame(
            '//example.com/jsonapi_network_path_relation_dummies/1/network_path_dummies',
            $body['links']['self'],
        );
        $this->assertSame('//example.com/jsonapi_network_path_dummies/1', $body['data'][0]['id']);
    }
}
