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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\JsonLdContextDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\JsonLdContextRelation;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ContextTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [JsonLdContextDummy::class, JsonLdContextRelation::class];
    }

    public function testEntrypointContextListsResources(): void
    {
        $response = self::createClient()->request('GET', '/contexts/Entrypoint');
        $this->assertResponseIsSuccessful();
        $this->assertSame('application/ld+json; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $body = $response->toArray();
        $this->assertSame('http://localhost/docs.jsonld#', $body['@context']['@vocab']);
        $this->assertSame('http://www.w3.org/ns/hydra/core#', $body['@context']['hydra']);
        $this->assertSame(['@id' => 'Entrypoint/jsonLdContextDummy', '@type' => '@id'], $body['@context']['jsonLdContextDummy']);
        $this->assertSame(['@id' => 'Entrypoint/jsonLdContextRelation', '@type' => '@id'], $body['@context']['jsonLdContextRelation']);
    }

    public function testResourceContextExposesPropertyMappings(): void
    {
        $response = self::createClient()->request('GET', '/contexts/JsonLdContextDummy');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('http://localhost/docs.jsonld#', $body['@context']['@vocab']);
        $this->assertSame('http://www.w3.org/ns/hydra/core#', $body['@context']['hydra']);
        $this->assertSame('https://schema.org/name', $body['@context']['name']);
        $this->assertSame('https://schema.org/alternateName', $body['@context']['alias']);
        $this->assertSame([
            '@id' => 'https://example.com/id',
            '@type' => '@id',
            'foo' => 'bar',
        ], $body['@context']['person']);
    }

    public function testRelatedResourceMappingHasIdReference(): void
    {
        $response = self::createClient()->request('GET', '/contexts/JsonLdContextDummy');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame([
            '@id' => 'JsonLdContextDummy/related',
            '@type' => '@id',
        ], $body['@context']['related']);
    }

    public function testRelatedCollectionMappingHasIdReference(): void
    {
        $response = self::createClient()->request('GET', '/contexts/JsonLdContextDummy');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame([
            '@id' => 'JsonLdContextDummy/relatedCollection',
            '@type' => '@id',
        ], $body['@context']['relatedCollection']);
    }

    public function testDateTimePropertyExposesSchemaOrgDateTime(): void
    {
        $response = self::createClient()->request('GET', '/contexts/JsonLdContextDummy');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('https://schema.org/DateTime', $body['@context']['dummyDate']);
    }

    public function testNameConvertedPropertyKeyIsNormalized(): void
    {
        $response = self::createClient()->request('GET', '/contexts/JsonLdContextDummy');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertArrayHasKey('name_converted', $body['@context']);
        $this->assertArrayNotHasKey('nameConverted', $body['@context']);
    }

    public function testJsonAndArrayDataAreExposed(): void
    {
        $response = self::createClient()->request('GET', '/contexts/JsonLdContextDummy');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertArrayHasKey('jsonData', $body['@context']);
        $this->assertArrayHasKey('arrayData', $body['@context']);
    }

    public function testEmbeddedRelationMappingIsPlainString(): void
    {
        $response = self::createClient()->request('GET', '/contexts/JsonLdContextDummy');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('JsonLdContextDummy/embedded', $body['@context']['embedded']);
    }
}
