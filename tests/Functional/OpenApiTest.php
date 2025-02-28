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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Crud;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\CrudOpenApiApiPlatformTag;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class OpenApiTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Crud::class, CrudOpenApiApiPlatformTag::class];
    }

    public function testErrorsAreDocumented(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $res = $response->toArray();
        $this->assertTrue(isset($res['paths']['/cruds/{id}']['patch']['responses']));
        $responses = $res['paths']['/cruds/{id}']['patch']['responses'];

        foreach ($responses as $status => $response) {
            if ($status < 400) {
                continue;
            }

            $this->assertArrayHasKey('application/problem+json', $response['content']);
            $this->assertArrayHasKey('application/ld+json', $response['content']);
            $this->assertArrayHasKey('application/vnd.api+json', $response['content']);

            match ($status) {
                422 => $this->assertStringStartsWith('#/components/schemas/ConstraintViolation', $response['content']['application/problem+json']['schema']['$ref']),
                default => $this->assertStringStartsWith('#/components/schemas/Error', $response['content']['application/problem+json']['schema']['$ref']),
            };
        }

        // problem detail https://datatracker.ietf.org/doc/html/rfc7807#section-3.1
        foreach (['title', 'detail', 'instance', 'type', 'status'] as $key) {
            $this->assertArrayHasKey($key, $res['components']['schemas']['Error']['properties']);
        }

        foreach (['title', 'detail', 'instance', 'type', 'status', '@id', '@type', '@context'] as $key) {
            $this->assertSame(['allOf' => [
                ['$ref' => '#/components/schemas/HydraItemBaseSchema'],
                ['$ref' => '#/components/schemas/Error'],
            ], 'description' => 'A representation of common errors.'], $res['components']['schemas']['Error.jsonld']);
        }

        foreach (['id', 'title', 'detail', 'instance', 'type', 'status', 'meta', 'source'] as $key) {
            $this->assertSame(['allOf' => [
                ['$ref' => '#/components/schemas/Error'],
                ['type' => 'object', 'properties' => [
                    'source' => [
                        'type' => 'object',
                    ],
                    'status' => [
                        'type' => 'string',
                    ],
                ]],
            ]], $res['components']['schemas']['Error.jsonapi']['properties']['errors']['items']);
        }
    }

    public function testFilterExtensionTags(): void
    {
        $response = self::createClient()->request('GET', '/docs?filter_tags[]=internal', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $res = $response->toArray();
        $this->assertArrayNotHasKey('CrudOpenApiApiPlatformTag', $res['components']['schemas']);
        $this->assertArrayNotHasKey('/cruds/{id}', $res['paths']);
        $this->assertArrayHasKey('/cruds', $res['paths']);
        $this->assertArrayHasKey('post', $res['paths']['/cruds']);
        $this->assertArrayHasKey('get', $res['paths']['/cruds']);
        $this->assertEquals([['name' => 'Crud', 'description' => 'A resource used for OpenAPI tests.']], $res['tags']);

        $response = self::createClient()->request('GET', '/docs?filter_tags[]=anotherone', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $res = $response->toArray();
        $this->assertArrayHasKey('CrudOpenApiApiPlatformTag', $res['components']['schemas']);
        $this->assertArrayHasKey('Crud', $res['components']['schemas']);
        $this->assertArrayNotHasKey('/cruds/{id}', $res['paths']);
        $this->assertArrayHasKey('/cruds', $res['paths']);
        $this->assertArrayNotHasKey('post', $res['paths']['/cruds']);
        $this->assertArrayHasKey('get', $res['paths']['/cruds']);
        $this->assertArrayHasKey('/crud_open_api_api_platform_tags/{id}', $res['paths']);
        $this->assertEquals([['name' => 'Crud', 'description' => 'A resource used for OpenAPI tests.'], ['name' => 'CrudOpenApiApiPlatformTag', 'description' => 'Something nice']], $res['tags']);
    }

    public function testHasSchemasForMultipleFormats(): void
    {
        $response = self::createClient()->request('GET', '/docs?filter_tags[]=internal', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $res = $response->toArray();
        $this->assertArrayHasKey('Crud.jsonld', $res['components']['schemas']);
        $this->assertSame(['allOf' => [
            ['$ref' => '#/components/schemas/HydraItemBaseSchema'],
            ['$ref' => '#/components/schemas/Crud'],
        ], 'description' => 'A resource used for OpenAPI tests.'], $res['components']['schemas']['Crud.jsonld']);
    }
}
