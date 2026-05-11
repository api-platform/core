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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\HalRelatedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\HalThirdLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\RelationEmbedder;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class HalTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [RelationEmbedder::class, HalRelatedResource::class, HalThirdLevel::class];
    }

    public function testEntrypointListsResourcesAsHalLinks(): void
    {
        $response = self::createClient()->request('GET', '/', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/hal+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame('/', $body['_links']['self']['href']);
        $hrefs = array_column($body['_links'], 'href');
        $this->assertContains('/hal_relation_embedders', $hrefs);
    }

    public function testGetEmbedsRelatedResourceAndItsRelation(): void
    {
        $response = self::createClient()->request('GET', '/hal_relation_embedders/1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/hal+json; charset=utf-8');
        $body = $response->toArray();

        $this->assertSame('/hal_relation_embedders/1', $body['_links']['self']['href']);
        $this->assertSame('/hal_related_resources/1', $body['_links']['related']['href']);
        $this->assertSame('Krondstadt', $body['krondstadt']);

        $related = $body['_embedded']['related'];
        $this->assertSame('/hal_related_resources/1', $related['_links']['self']['href']);
        $this->assertSame('/hal_third_levels/1', $related['_links']['thirdLevel']['href']);
        $this->assertSame('symfony', $related['symfony']);

        $thirdLevel = $related['_embedded']['thirdLevel'];
        $this->assertSame('/hal_third_levels/1', $thirdLevel['_links']['self']['href']);
        $this->assertSame(3, $thirdLevel['level']);
    }

    public function testPostAcceptsIriRelationAndReturnsHalPayload(): void
    {
        $response = self::createClient()->request('POST', '/hal_relation_embedders', [
            'headers' => [
                'Accept' => 'application/hal+json',
                'Content-Type' => 'application/json',
            ],
            'json' => ['related' => '/hal_related_resources/1'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('/hal_relation_embedders/1', $body['_links']['self']['href']);
        $this->assertSame('/hal_related_resources/1', $body['_links']['related']['href']);
        $this->assertSame('Krondstadt', $body['krondstadt']);
    }

    public function testPutReturnsHalPayloadAndKeepsPreviousRelation(): void
    {
        $response = self::createClient()->request('PUT', '/hal_relation_embedders/1', [
            'headers' => [
                'Accept' => 'application/hal+json',
                'Content-Type' => 'application/json',
            ],
            'json' => ['krondstadt' => 'Updated'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/hal+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame('/hal_relation_embedders/1', $body['_links']['self']['href']);
        $this->assertSame('/hal_related_resources/1', $body['_links']['related']['href']);
        $this->assertSame('Updated', $body['krondstadt']);
        $this->assertSame('/hal_related_resources/1', $body['_embedded']['related']['_links']['self']['href']);
    }

    public function testPatchReturnsHalPayloadWithMergePatch(): void
    {
        $response = self::createClient()->request('PATCH', '/hal_relation_embedders/1', [
            'headers' => [
                'Accept' => 'application/hal+json',
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => ['krondstadt' => 'Patched'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/hal+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame('/hal_relation_embedders/1', $body['_links']['self']['href']);
        $this->assertSame('/hal_related_resources/1', $body['_links']['related']['href']);
        $this->assertSame('Patched', $body['krondstadt']);
        $this->assertSame('/hal_related_resources/1', $body['_embedded']['related']['_links']['self']['href']);
    }
}
