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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\WithResponseHeaderParameter;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ResponseHeaderParameterTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [WithResponseHeaderParameter::class];
    }

    public function testResponseHeadersAreSet(): void
    {
        self::createClient()->request('GET', 'with_response_headers/1');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('ratelimit-limit', '100');
        $this->assertResponseHeaderSame('ratelimit-remaining', '99');
    }

    public function testProcessorSetsResponseHeaders(): void
    {
        self::createClient()->request('POST', 'with_response_headers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['id' => '3', 'name' => 'test'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('ratelimit-limit', '50');
        $this->assertResponseHeaderSame('ratelimit-remaining', '49');
    }

    public function testOpenApiDocumentsResponseHeaders(): void
    {
        $response = self::createClient()->request('GET', 'docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();

        $itemPath = $json['paths']['/with_response_headers/{id}']['get'];
        $this->assertArrayHasKey('responses', $itemPath);

        $successResponse = $itemPath['responses']['200'] ?? $itemPath['responses'][200] ?? null;
        $this->assertNotNull($successResponse);
        $this->assertArrayHasKey('headers', $successResponse);
        $this->assertArrayHasKey('RateLimit-Limit', $successResponse['headers']);
        $this->assertArrayHasKey('RateLimit-Remaining', $successResponse['headers']);
        $this->assertSame('integer', $successResponse['headers']['RateLimit-Limit']['schema']['type']);
        $this->assertSame('Maximum number of requests per window', $successResponse['headers']['RateLimit-Limit']['description']);

        // Verify headers are NOT in request parameters
        foreach ($itemPath['parameters'] ?? [] as $parameter) {
            $this->assertNotSame('RateLimit-Limit', $parameter['name']);
            $this->assertNotSame('RateLimit-Remaining', $parameter['name']);
        }
    }
}
