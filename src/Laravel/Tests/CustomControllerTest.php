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

class CustomControllerTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    public function testCustomController(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get('/api/with_custom_controller/42', ['Accept' => ['application/ld+json']]);
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('id', $data);
        $this->assertSame(42, $data['id']);
        $this->assertArrayHasKey('name', $data);
        $this->assertSame('Custom Controller Response', $data['name']);
        $this->assertArrayHasKey('custom', $data);
        $this->assertTrue($data['custom']);
    }

    public function testCustomControllerWithDifferentFormat(): void
    {
        $response = $this->get('/api/with_custom_controller/123', ['Accept' => ['application/json']]);
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('id', $data);
        $this->assertSame(123, $data['id']);
        $this->assertArrayHasKey('name', $data);
        $this->assertSame('Custom Controller Response', $data['name']);
        $this->assertTrue($data['custom']);
    }
}
