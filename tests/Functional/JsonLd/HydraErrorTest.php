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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\HydraErrorResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class HydraErrorTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [HydraErrorResource::class];
    }

    public function testBadRequestErrorIsRfc7807AndHydraCompliant(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_hydra_errors_bad_request', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => new \stdClass(),
        ]);
        $this->assertResponseStatusCodeSame(400);
        $headers = $response->getHeaders(false);
        $this->assertSame('application/problem+json; charset=utf-8', $headers['content-type'][0]);
        $this->assertStringContainsString(
            '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"',
            implode(',', $headers['link']),
        );
        $body = $response->toArray(false);
        $this->assertArrayHasKey('@context', $body);
        $this->assertArrayHasKey('type', $body);
        $this->assertSame('An error occurred', $body['hydra:title']);
        $this->assertArrayHasKey('detail', $body);
        $this->assertArrayHasKey('hydra:description', $body);
        $this->assertArrayHasKey('trace', $body);
        $this->assertArrayHasKey('status', $body);
        $this->assertArrayNotHasKey('title', $body);
        $this->assertArrayNotHasKey('description', $body);
    }

    public function testValidationErrorReturnsConstraintViolationList(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_hydra_errors_validation', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => new \stdClass(),
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('application/problem+json; charset=utf-8', $response->getHeaders(false)['content-type'][0]);
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@id' => '/validation_errors/c1051bb4-d103-4f74-8988-acbcafc7fdc3',
            '@type' => 'ConstraintViolation',
            'status' => 422,
            'violations' => [
                [
                    'propertyPath' => 'name',
                    'message' => 'This value should not be blank.',
                    'code' => 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                ],
            ],
            'detail' => 'name: This value should not be blank.',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'name: This value should not be blank.',
            'type' => '/validation_errors/c1051bb4-d103-4f74-8988-acbcafc7fdc3',
        ]);
    }

    public function testNotFoundReturnsHydraError(): void
    {
        $response = self::createClient()->request('POST', '/does_not_exist', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(404);
        $headers = $response->getHeaders(false);
        $this->assertSame('application/problem+json; charset=utf-8', $headers['content-type'][0]);
        $this->assertStringContainsString(
            '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"',
            implode(',', $headers['link']),
        );
        $body = $response->toArray(false);
        $this->assertArrayHasKey('@context', $body);
        $this->assertArrayHasKey('type', $body);
        $this->assertSame('An error occurred', $body['hydra:title']);
        $this->assertArrayHasKey('detail', $body);
        $this->assertArrayNotHasKey('description', $body);
    }

    public function testMethodNotAllowedReturnsHydraError(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_hydra_errors_patch_only', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => new \stdClass(),
        ]);
        $this->assertResponseStatusCodeSame(405);
        $headers = $response->getHeaders(false);
        $this->assertSame('application/problem+json; charset=utf-8', $headers['content-type'][0]);
        $this->assertStringContainsString(
            '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"',
            implode(',', $headers['link']),
        );
        $body = $response->toArray(false);
        $this->assertArrayHasKey('@context', $body);
        $this->assertArrayHasKey('type', $body);
        $this->assertSame('An error occurred', $body['hydra:title']);
        $this->assertArrayHasKey('detail', $body);
        $this->assertArrayNotHasKey('description', $body);
    }

    public function testNoHydraPrefixWhenDisabled(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_hydra_errors_no_prefix', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => new \stdClass(),
        ]);
        $this->assertResponseStatusCodeSame(400);
        $headers = $response->getHeaders(false);
        $this->assertSame('application/problem+json; charset=utf-8', $headers['content-type'][0]);
        $this->assertStringContainsString(
            '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"',
            implode(',', $headers['link']),
        );
        $body = $response->toArray(false);
        $this->assertArrayHasKey('@context', $body);
        $this->assertArrayHasKey('type', $body);
        $this->assertSame('An error occurred', $body['hydra:title']);
        $this->assertArrayHasKey('detail', $body);
        $this->assertArrayHasKey('trace', $body);
        $this->assertArrayHasKey('status', $body);
        $this->assertArrayNotHasKey('description', $body);
    }
}
