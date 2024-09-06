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
}
