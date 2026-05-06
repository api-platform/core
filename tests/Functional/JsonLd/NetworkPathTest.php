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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\NetworkPathParent;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\NetworkPathResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NetworkPathTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [NetworkPathResource::class, NetworkPathParent::class];
    }

    public function testCollectionUsesNetworkPaths(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_network_path_children', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('//localhost/contexts/JsonLdNetworkPathChild', $body['@context']);
        $this->assertSame('//localhost/jsonld_network_path_children', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertSame('//localhost/jsonld_network_path_children/1', $body['hydra:member'][0]['@id']);
    }

    public function testItemUsesNetworkPaths(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_network_path_children/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('//localhost/contexts/JsonLdNetworkPathChild', $body['@context']);
        $this->assertSame('//localhost/jsonld_network_path_children/1', $body['@id']);
        $this->assertSame('JsonLdNetworkPathChild', $body['@type']);
        $this->assertSame('//localhost/jsonld_network_path_parents/1', $body['parent']);
    }

    public function testPostReturnsNetworkPath(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_network_path_parents', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/json',
            ],
            'json' => new \stdClass(),
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('//localhost/jsonld_network_path_parents/2', $body['@id']);
    }

    public function testSubresourceCollectionUsesNetworkPaths(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_network_path_parents/1/children', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('//localhost/contexts/JsonLdNetworkPathChild', $body['@context']);
        $this->assertSame('//localhost/jsonld_network_path_parents/1/children', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
    }
}
