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

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class JsonProblemTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    public function testNotFound(): void
    {
        $response = $this->get('/api/books/notfound', headers: ['accept' => 'application/ld+json']);
        $response->assertStatus(404);
        $response->assertHeader('content-type', 'application/problem+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Error',
            '@id' => '/api/errors/404',
            '@type' => 'Error',
            'type' => '/errors/404',
            'title' => 'An error occurred',
            'status' => 404,
            'detail' => 'Not Found',
        ]);
    }

    /**
     * @param Application $app
     */
    protected function useProductionMode($app): void
    {
        $app['config']->set('app.debug', false);
    }

    #[DefineEnvironment('useProductionMode')]
    public function testProductionError(): void
    {
        $response = $this->post('/api/books', ['content-type' => 'application/vnd.api+json']);
        $response->assertStatus(415);
        $content = $response->json();
        $this->assertArrayNotHasKey('trace', $content);
        $this->assertArrayNotHasKey('line', $content);
        $this->assertArrayNotHasKey('file', $content);
    }

    /**
     * @param list<array{0: string, 1: string, 2: array<string, mixed>}> $expected
     */
    #[DefineEnvironment('useProductionMode')]
    #[DataProvider('formatsProvider')]
    public function testRetrieveError(string $format, string $status, array $expected): void
    {
        $response = $this->get('/api/errors/'.$status, ['accept' => $format]);
        $this->assertEquals($expected, $response->json());
    }

    #[DefineEnvironment('useProductionMode')]
    public function testRetrieveErrorHtml(): void
    {
        $response = $this->get('/api/errors/403', ['accept' => 'text/html']);
        $this->assertEquals('<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Error 403</title>
    </head>
    <body><h1>Error 403</h1>Forbidden</body>
</html>', $response->getContent());
    }

    /**
     * @return list<array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function formatsProvider(): array
    {
        return [
            [
                'application/vnd.api+json',
                '401',
                [
                    'errors' => [
                        [
                            'id' => '/api/errors/401',
                            'detail' => 'Unauthorized',
                            'type' => 'about:blank',
                            'title' => 'Error 401',
                            'status' => 401,
                            'code' => '401',
                            'links' => [
                                'type' => 'about:blank',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'application/ld+json',
                '401',
                [
                    '@context' => '/api/contexts/Error',
                    '@type' => 'Error',
                    '@id' => '/api/errors/401',
                    'detail' => 'Unauthorized',
                    'title' => 'Error 401',
                    'status' => 401,
                    'type' => 'about:blank',
                ],
            ],
            [
                'application/json',
                '401',
                [
                    'type' => 'about:blank',
                    'detail' => 'Unauthorized',
                    'title' => 'Error 401',
                    'status' => 401,
                ],
            ],
        ];
    }
}
