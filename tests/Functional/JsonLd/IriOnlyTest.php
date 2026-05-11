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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\IriOnlyResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class IriOnlyTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [IriOnlyResource::class];
    }

    #[DataProvider('contextUris')]
    public function testContextEndpointReturnsIriOnlyContext(string $uri): void
    {
        $response = self::createClient()->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $this->assertSame('application/ld+json; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $this->assertSame([
            '@context' => [
                '@vocab' => 'http://localhost/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'hydra:member' => ['@type' => '@id'],
            ],
        ], $response->toArray());
    }

    public static function contextUris(): array
    {
        return [
            ['/contexts/JsonLdIriOnlyResource'],
            ['/contexts/JsonLdIriOnlyResource.jsonld'],
        ];
    }

    public function testContextEndpointWithJsonExtensionReturns404(): void
    {
        self::createClient()->request('GET', '/contexts/JsonLdIriOnlyResource.json');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCollectionReturnsIriOnlyMembers(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_iri_only_resources');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame([
            '@vocab' => 'http://localhost/docs.jsonld#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'hydra:member' => ['@type' => '@id'],
        ], $body['@context']);
        $this->assertSame('/jsonld_iri_only_resources', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertSame([
            '/jsonld_iri_only_resources/1',
            '/jsonld_iri_only_resources/2',
            '/jsonld_iri_only_resources/3',
        ], $body['hydra:member']);
        $this->assertSame(3, $body['hydra:totalItems']);
    }
}
