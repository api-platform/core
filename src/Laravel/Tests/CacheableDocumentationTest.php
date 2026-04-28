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

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class CacheableDocumentationTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    public function testDocumentationResponseHasCacheHeaders(): void
    {
        $response = $this->get('/api/docs.jsonld');
        $response->assertStatus(200);

        $etag = $response->headers->get('etag');
        $this->assertNotEmpty($etag, 'documentation response is missing an ETag');

        $cacheControl = $response->headers->get('cache-control') ?? '';
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    public function testEntrypointResponseHasCacheHeaders(): void
    {
        $response = $this->get('/api/index.jsonld');
        $response->assertStatus(200);

        $cacheControl = $response->headers->get('cache-control') ?? '';
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    public function testDocumentationReturnsNotModifiedWhenIfNoneMatchMatches(): void
    {
        $first = $this->get('/api/docs.jsonld');
        $etag = $first->headers->get('etag');
        $this->assertNotEmpty($etag, 'expected an ETag on the first documentation response');

        $second = $this->get('/api/docs.jsonld', ['If-None-Match' => $etag]);
        $second->assertStatus(304);
    }

    public function testRegularResourceDoesNotHaveDocumentationCacheHeaders(): void
    {
        $response = $this->get('/api/staff', headers: ['accept' => 'application/ld+json']);
        $response->assertStatus(200);

        $cacheControl = $response->headers->get('cache-control') ?? '';
        $this->assertStringNotContainsString('must-revalidate', $cacheControl, 'regular resource must not be wrapped by the documentation cache decorator');
        $this->assertStringNotContainsString('max-age=0', $cacheControl, 'regular resource must not be wrapped by the documentation cache decorator');
    }

    public function testCustomMaxAgeAndSharedMaxAgeAreApplied(): void
    {
        $this->app['config']->set('api-platform.documentation.cache_headers.max_age', 3600);
        $this->app['config']->set('api-platform.documentation.cache_headers.shared_max_age', 600);
        $this->app->forgetInstance('api_platform.state_processor.documentation');

        $response = $this->get('/api/docs.jsonld');
        $response->assertStatus(200);

        $cacheControl = $response->headers->get('cache-control') ?? '';
        $this->assertStringContainsString('max-age=3600', $cacheControl);
        $this->assertStringContainsString('s-maxage=600', $cacheControl);
    }

    public function testMustRevalidateCanBeDisabled(): void
    {
        $this->app['config']->set('api-platform.documentation.cache_headers.must_revalidate', false);
        $this->app->forgetInstance('api_platform.state_processor.documentation');

        $response = $this->get('/api/docs.jsonld');
        $response->assertStatus(200);

        $cacheControl = $response->headers->get('cache-control') ?? '';
        $this->assertStringNotContainsString('must-revalidate', $cacheControl);
    }

    public function testEtagCanBeDisabled(): void
    {
        $this->app['config']->set('api-platform.documentation.cache_headers.etag', false);
        $this->app->forgetInstance('api_platform.state_processor.documentation');

        $response = $this->get('/api/docs.jsonld');
        $response->assertStatus(200);

        // documentation decorator stays wired (must-revalidate proves it) but its md5 ETag must not be set
        $cacheControl = $response->headers->get('cache-control') ?? '';
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $etag = trim($response->headers->get('etag') ?? '', '"');
        $this->assertFalse(1 === preg_match('/^[a-f0-9]{32}$/', $etag), 'documentation decorator must not produce its md5 ETag when etag is disabled');
    }

    public function testFeatureCanBeDisabled(): void
    {
        $this->app['config']->set('api-platform.documentation.cache_headers.enabled', false);
        $this->app->forgetInstance('api_platform.state_processor.documentation');

        $response = $this->get('/api/docs.jsonld');
        $response->assertStatus(200);

        $cacheControl = $response->headers->get('cache-control') ?? '';
        $this->assertStringNotContainsString('must-revalidate', $cacheControl, 'when disabled, the cache decorator must not be wired');
    }
}
