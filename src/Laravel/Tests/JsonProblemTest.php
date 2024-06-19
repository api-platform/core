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

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class JsonProblemTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    public function testNotFound(): void
    {
        $response = $this->get('/api/books/notfound');
        $response->assertStatus(404);
        $response->assertHeader('content-type', 'application/problem+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Error',
            '@id' => '/api/hydra_errors/404',
            '@type' => 'hydra:Error',
            'type' => '/errors/404',
            'title' => 'An error occurred',
            'status' => 404,
            'detail' => 'Not Found',
        ]);
    }
}
