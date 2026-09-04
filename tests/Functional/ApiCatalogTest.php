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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ApiCatalogResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * @see https://www.rfc-editor.org/rfc/rfc9727.html
 */
final class ApiCatalogTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ApiCatalogResource::class];
    }

    public function testTheCatalogIsALinksetDocument(): void
    {
        $response = self::createClient()->request('GET', '/.well-known/api-catalog');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/linkset+json; profile="https://www.rfc-editor.org/info/rfc9727"');

        $linkset = json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR)['linkset'];
        $contexts = array_column($linkset, null, 'anchor');

        $this->assertArrayHasKey('http://localhost/.well-known/api-catalog', $contexts);
        $this->assertSame([['href' => 'http://localhost/']], $contexts['http://localhost/.well-known/api-catalog']['item']);

        $api = $contexts['http://localhost/'];
        $this->assertContains(['href' => 'http://localhost/api_catalog_resources'], $api['item']);
        $this->assertContains(['href' => 'http://localhost/docs.jsonopenapi', 'type' => 'application/vnd.openapi+json'], $api['service-desc']);
        $this->assertContains(['href' => 'http://localhost/docs', 'type' => 'text/html'], $api['service-doc']);
        $this->assertContains(['href' => 'http://localhost/docs.jsonld', 'type' => 'application/ld+json'], $api['service-meta']);
    }

    public function testTheEntrypointAdvertisesTheCatalog(): void
    {
        $response = self::createClient()->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('<http://localhost/.well-known/api-catalog>; rel="api-catalog"', $response->getHeaders()['link'][0] ?? '');
    }

    public function testTheCatalogAdvertisesItselfWithTheApiCatalogRelation(): void
    {
        $response = self::createClient()->request('HEAD', '/.well-known/api-catalog');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('<http://localhost/.well-known/api-catalog>; rel="api-catalog"', $response->getHeaders()['link'][0] ?? null);
    }
}
