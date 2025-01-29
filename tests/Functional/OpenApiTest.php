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
use ApiPlatform\Tests\SetupClassResourcesTrait;

class OpenApiTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Crud::class];
    }

    public function testErrorsAreDocumented(): void
    {
        $container = static::getContainer();
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
            $this->assertArrayHasKey($key, $res['components']['schemas']['Error.jsonld-jsonproblem']['properties']);
        }

        foreach (['title', 'detail', 'instance', 'type', 'status', '@id', '@type', '@context'] as $key) {
            $this->assertArrayHasKey($key, $res['components']['schemas']['Error.jsonld-jsonld']['properties']);
        }
        foreach (['id', 'title', 'detail', 'instance', 'type', 'status', 'meta', 'source'] as $key) {
            $this->assertArrayHasKey($key, $res['components']['schemas']['Error.jsonapi-jsonapi']['properties']['errors']['properties']);
        }
    }
}
