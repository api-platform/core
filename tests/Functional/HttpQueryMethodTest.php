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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\QueryMethodDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * The HTTP QUERY method (RFC 10008): a safe collection operation reading its
 * parameters from the request body, fed to the Parameter API.
 */
final class HttpQueryMethodTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [QueryMethodDummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([QueryMethodDummy::class]);
        $this->createDummy('foo');
        $this->createDummy('bar');
    }

    private function createDummy(string $name): void
    {
        self::createClient()->request('POST', '/query_method_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => $name],
        ]);
    }

    public function testEmptyBodyReturnsFullCollection(): void
    {
        $response = self::createClient()->request('QUERY', '/query_method_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame('hydra:Collection', $data['@type']);
        $this->assertSame(2, $data['hydra:totalItems']);
    }

    public function testFiltersFromFormUrlencodedBody(): void
    {
        $response = self::createClient()->request('QUERY', '/query_method_dummies', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => 'name=foo',
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame(1, $data['hydra:totalItems']);
        $this->assertSame('foo', $data['hydra:member'][0]['name']);
    }

    public function testFiltersFromJsonBody(): void
    {
        $response = self::createClient()->request('QUERY', '/query_method_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
            'json' => ['name' => 'foo'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame(1, $data['hydra:totalItems']);
        $this->assertSame('foo', $data['hydra:member'][0]['name']);
    }

    public function testUnsupportedBodyContentTypeIsRejected(): void
    {
        self::createClient()->request('QUERY', '/query_method_dummies', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'text/plain',
            ],
            'body' => 'name=foo',
        ]);

        $this->assertResponseStatusCodeSame(415);
        $this->assertResponseHasHeader('Accept-Query');
    }

    public function testMalformedJsonBodyIsRejected(): void
    {
        self::createClient()->request('QUERY', '/query_method_dummies', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/json',
            ],
            'body' => '{"name":',
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUnknownParameterIsRejectedByStrictValidation(): void
    {
        self::createClient()->request('QUERY', '/query_method_dummies', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => 'unknown=foo',
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testQueryOperationIsDocumentedInOpenApi(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();

        $this->assertArrayHasKey('query', $json['paths']['/query_method_dummies'], 'The QUERY operation must be emitted under the OpenAPI 3.2 "query" path item field.');
        $query = $json['paths']['/query_method_dummies']['query'];

        // RFC 10008: parameters are carried in the request body, not the URI query string.
        $this->assertArrayHasKey('requestBody', $query);
        $content = $query['requestBody']['content'];
        $this->assertArrayHasKey('application/x-www-form-urlencoded', $content);
        $this->assertArrayHasKey('application/json', $content);

        $schema = $content['application/x-www-form-urlencoded']['schema'];
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertSame(['type' => 'string'], $schema['properties']['name']);

        // The filter must not leak into the URI query string as an "in: query" parameter.
        foreach ($query['parameters'] ?? [] as $parameter) {
            $this->assertNotSame('name', $parameter['name'] ?? null, 'QUERY filters must live in the request body, not as query parameters.');
        }

        // It is a safe collection read: a 200 collection response is documented.
        $this->assertArrayHasKey('200', $query['responses']);
    }
}
