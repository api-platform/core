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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class EloquentTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    public function testSearchFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $book = $response->json()['hydra:member'][0];

        $response = $this->get('/api/books?isbn='.$book['isbn'], ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0], $book);
    }

    public function testPartialSearchFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $book = $response->json()['hydra:member'][0];

        $name = substr($book['name'],0, strpos($book['name'],' '));

        $response = $this->get('/api/books?name='.$name, ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0], $book);
    }

    public function testDateSearchFilter(): void
    {
        // get the 1st book object return
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $book = $response->json()['hydra:member'][0];

        // patch to add a specific date to test on
        $this->patch(
            $book['@id'],
            [ 'publicationDate' => '2024-18-02 00:00:00'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json']
            ]
        );

        $response = $this->get('/api/books?publicationDate='.$book['publicationDate'], ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0], $book);
    }
}
