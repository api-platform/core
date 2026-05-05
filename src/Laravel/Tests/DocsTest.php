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

class DocsTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    public function testOpenApi(): void
    {
        $res = $this->get('/api/docs.jsonopenapi');
        $this->assertArrayHasKey('openapi', $res->json());
        $this->assertSame('application/vnd.openapi+json; charset=utf-8', $res->headers->get('content-type'));
    }

    public function testOpenApiAccept(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'application/vnd.openapi+json']);
        $this->assertArrayHasKey('openapi', $res->json());
        $this->assertSame('application/vnd.openapi+json; charset=utf-8', $res->headers->get('content-type'));
    }

    public function testJsonLd(): void
    {
        $res = $this->get('/api/docs.jsonld');
        $this->assertArrayHasKey('@context', $res->json());
        $this->assertSame('application/ld+json; charset=utf-8', $res->headers->get('content-type'));
    }

    public function testJsonLdAccept(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'application/ld+json']);
        $this->assertArrayHasKey('@context', $res->json());
        $this->assertSame('application/ld+json; charset=utf-8', $res->headers->get('content-type'));
    }

    public function testHtmlDocsRendersSwaggerUiByDefault(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'text/html']);
        $res->assertOk();
        $content = $res->getContent();

        $this->assertStringContainsString('init-swagger-ui.js', $content);
        $this->assertStringContainsString('id="formats"', $content);
        $this->assertStringContainsString('>ReDoc</a>', $content);
        $this->assertStringContainsString('>Scalar</a>', $content);
        $this->assertStringNotContainsString('>Swagger UI</a>', $content);
    }

    public function testHtmlDocsRendersRedocWhenRequested(): void
    {
        $res = $this->get('/api/docs?ui=redoc', headers: ['accept' => 'text/html']);
        $res->assertOk();
        $content = $res->getContent();

        $this->assertStringContainsString('init-redoc-ui.js', $content);
        $this->assertStringContainsString('id="formats"', $content);
        $this->assertStringContainsString('>Swagger UI</a>', $content);
        $this->assertStringContainsString('>Scalar</a>', $content);
        $this->assertStringNotContainsString('>ReDoc</a>', $content);
    }

    public function testHtmlDocsRendersScalarWithoutFooterWhenRequested(): void
    {
        $res = $this->get('/api/docs?ui=scalar', headers: ['accept' => 'text/html']);
        $res->assertOk();
        $content = $res->getContent();

        $this->assertStringContainsString('init-scalar-ui.js', $content);
        $this->assertStringNotContainsString('id="formats"', $content);
    }
}
