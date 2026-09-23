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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\WithResponseHeader;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ResponseHeaderTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [WithResponseHeader::class];
    }

    public function testStaticValueWithoutProvider(): void
    {
        self::createClient()->request('GET', 'with_response_headers/1');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('x-static-header', 'static-value');
    }

    public function testProviderResolvedThroughTheParameterProviderLocator(): void
    {
        self::createClient()->request('GET', 'with_response_headers/1');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('ratelimit-limit', '100');
        $this->assertResponseHeaderSame('ratelimit-remaining', '99');
    }

    public function testProviderResolvedThroughTheParameterProviderLocatorWhenStreaming(): void
    {
        self::createClient()->request('GET', 'with_streamed_response_headers/1');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('ratelimit-limit', '100');
    }

    public function testProviderResolvingNoValueLeavesTheExistingHeaderUntouched(): void
    {
        self::createClient()->request('GET', 'with_response_headers/1');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('x-frame-options', 'deny');
    }

    public function testProviderResolvingAnEmptyStringEmitsAnEmptyHeader(): void
    {
        self::createClient()->request('GET', 'with_response_headers/1');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('x-empty-header', '');
    }

    public function testCustomOpenApiHeaderOverridesTheGeneratedOne(): void
    {
        $response = self::createClient()->request('GET', 'docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();

        $headers = $response->toArray()['paths']['/with_response_headers/{id}']['get']['responses']['200']['headers'];

        $this->assertArrayHasKey('X-Custom-Documented', $headers);
        $this->assertSame('Fully custom documentation', $headers['X-Custom-Documented']['description']);
        $this->assertTrue($headers['X-Custom-Documented']['required']);
        $this->assertTrue($headers['X-Custom-Documented']['deprecated']);
        $this->assertSame(['type' => 'string', 'format' => 'uuid'], $headers['X-Custom-Documented']['schema']);

        $this->assertArrayNotHasKey('X-Frame-Options', $headers);
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
        $this->assertArrayHasKey('X-Static-Header', $successResponse['headers']);
        $this->assertArrayNotHasKey('X-Frame-Options', $successResponse['headers']);
        $this->assertSame('integer', $successResponse['headers']['RateLimit-Limit']['schema']['type']);
        $this->assertSame('Maximum number of requests per window', $successResponse['headers']['RateLimit-Limit']['description']);

        foreach ($itemPath['parameters'] ?? [] as $parameter) {
            $this->assertNotSame('RateLimit-Limit', $parameter['name']);
            $this->assertNotSame('RateLimit-Remaining', $parameter['name']);
            $this->assertNotSame('X-Static-Header', $parameter['name']);
        }
    }
}
