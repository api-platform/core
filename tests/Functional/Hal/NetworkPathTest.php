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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\NetworkPathParent;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\NetworkPathResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NetworkPathTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [NetworkPathResource::class, NetworkPathParent::class];
    }

    public function testCollectionLinksUseNetworkPaths(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/hal_network_path_children', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('//example.com/hal_network_path_children', $body['_links']['self']['href']);
        $this->assertSame('//example.com/hal_network_path_children/1', $body['_links']['item'][0]['href']);
        $this->assertSame('//example.com/hal_network_path_parents/1', $body['_embedded']['item'][0]['_links']['parent']['href']);
    }

    public function testItemLinksUseNetworkPaths(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/hal_network_path_children/1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('//example.com/hal_network_path_children/1', $body['_links']['self']['href']);
        $this->assertSame('//example.com/hal_network_path_parents/1', $body['_links']['parent']['href']);
    }

    public function testPostAcceptsNetworkPathInPayload(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('POST', '/hal_network_path_children', [
            'headers' => [
                'Accept' => 'application/hal+json',
                'Content-Type' => 'application/json',
            ],
            'json' => ['parent' => '//example.com/hal_network_path_parents/1'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('//example.com/hal_network_path_children/2', $body['_links']['self']['href']);
        $this->assertSame('//example.com/hal_network_path_parents/1', $body['_links']['parent']['href']);
    }

    public function testSubresourceCollectionUsesNetworkPaths(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/hal_network_path_parents/1/children', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('//example.com/hal_network_path_parents/1/children', $body['_links']['self']['href']);
        $this->assertSame('//example.com/hal_network_path_children/1', $body['_links']['item'][0]['href']);
    }
}
