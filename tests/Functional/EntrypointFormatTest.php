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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MultipleResourceBook;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * The entrypoint must only expose hypermedia formats that have a dedicated
 * EntrypointNormalizer (jsonld, jsonhal, jsonapi). Documentation formats
 * (openapi, html) have no such normalizer and must not be served, otherwise
 * the Symfony ObjectNormalizer fallback dumps the public ResourceNameCollection,
 * leaking internal PHP FQCNs.
 *
 * @see https://github.com/api-platform/core/issues/8361
 */
final class EntrypointFormatTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [MultipleResourceBook::class];
    }

    public function testEntrypointRejectsOpenApiAcceptHeader(): void
    {
        $response = self::createClient()->request('GET', '/', [
            'headers' => ['accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseStatusCodeSame(406);
    }

    /**
     * The ".jsonopenapi"/".yamlopenapi" URL suffixes are resolved by routing before
     * content negotiation runs, so an unsupported route format yields a 404
     * (consistent with any other resource requested with an unsupported format
     * suffix), not a 406.
     */
    public function testEntrypointRejectsOpenApiFormatSuffix(): void
    {
        $response = self::createClient()->request('GET', '/index.jsonopenapi', [
            'headers' => ['accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEntrypointRejectsYamlOpenApiFormatSuffix(): void
    {
        $response = self::createClient()->request('GET', '/index.yamlopenapi', [
            'headers' => ['accept' => 'application/vnd.openapi+yaml'],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEntrypointStillServesHtmlAsSwaggerUi(): void
    {
        $response = self::createClient()->request('GET', '/index.html', [
            'headers' => ['accept' => 'text/html'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('swagger-ui', $response->getContent());
    }

    public function testEntrypointStillServesJsonLd(): void
    {
        $response = self::createClient()->request('GET', '/index.jsonld', [
            'headers' => ['accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertArrayHasKey('multipleResourceBook2', $data);
        $this->assertEquals('/multi_route_books', $data['multipleResourceBook2']);
        $this->assertStringNotContainsString('resourceNameCollection', $response->getContent());
    }

    public function testEntrypointStillServesJsonHal(): void
    {
        $response = self::createClient()->request('GET', '/index.jsonhal', [
            'headers' => ['accept' => 'application/hal+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/hal+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertArrayHasKey('_links', $data);
        $this->assertArrayHasKey('multipleResourceBook2', $data['_links']);
    }

    public function testEntrypointStillServesJsonApi(): void
    {
        $response = self::createClient()->request('GET', '/index.jsonapi', [
            'headers' => ['accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertArrayHasKey('links', $data);
        $this->assertArrayHasKey('multipleResourceBook2', $data['links']);
    }
}
