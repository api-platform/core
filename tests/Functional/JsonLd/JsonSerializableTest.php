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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\JsonSerializableResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class JsonSerializableTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [JsonSerializableResource::class];
    }

    public function testCreateJsonSerializableResource(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_json_serializables', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'contentType' => 'homepage',
                'fieldValues' => ['title' => 'Sample title'],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([
            '@context' => '/contexts/JsonLdJsonSerializable',
            '@id' => '/jsonld_json_serializables/1',
            '@type' => 'JsonLdJsonSerializable',
            'id' => 1,
            'contentType' => 'homepage',
            'fieldValues' => ['title' => 'Sample title'],
            'status' => ['key' => 'DRAFT', 'value' => 'draft'],
        ], $response->toArray());
    }

    public function testGetJsonSerializableResource(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_json_serializables/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/contexts/JsonLdJsonSerializable', $body['@context']);
        $this->assertSame('/jsonld_json_serializables/1', $body['@id']);
        $this->assertSame('JsonLdJsonSerializable', $body['@type']);
        $this->assertSame(['key' => 'DRAFT', 'value' => 'draft'], $body['status']);
    }
}
