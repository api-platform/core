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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\AbsoluteUrlChild;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\AbsoluteUrlParent;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class AbsoluteUrlTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [AbsoluteUrlChild::class, AbsoluteUrlParent::class];
    }

    public function testCollectionUsesAbsoluteUrls(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/jsonld_absolute_url_children', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('http://example.com/contexts/JsonLdAbsoluteUrlChild', $body['@context']);
        $this->assertSame('http://example.com/jsonld_absolute_url_children', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertSame('http://example.com/jsonld_absolute_url_children/1', $body['hydra:member'][0]['@id']);
    }

    public function testItemUsesAbsoluteUrls(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/jsonld_absolute_url_children/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('http://example.com/contexts/JsonLdAbsoluteUrlChild', $body['@context']);
        $this->assertSame('http://example.com/jsonld_absolute_url_children/1', $body['@id']);
        $this->assertSame('JsonLdAbsoluteUrlChild', $body['@type']);
        $this->assertSame('http://example.com/jsonld_absolute_url_parents/1', $body['parent']);
    }

    public function testPostAcceptsAbsoluteUrlInPayload(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('POST', '/jsonld_absolute_url_children', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['parent' => 'http://example.com/jsonld_absolute_url_parents/1'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('http://example.com/jsonld_absolute_url_children/2', $body['@id']);
        $this->assertSame('JsonLdAbsoluteUrlChild', $body['@type']);
        $this->assertSame('http://example.com/jsonld_absolute_url_parents/1', $body['parent']);
    }

    public function testSubresourceCollectionUsesAbsoluteUrls(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/jsonld_absolute_url_parents/1/children', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('http://example.com/contexts/JsonLdAbsoluteUrlChild', $body['@context']);
        $this->assertSame('http://example.com/jsonld_absolute_url_parents/1/children', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
    }
}
