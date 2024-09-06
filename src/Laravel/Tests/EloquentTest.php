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

        if (!isset($book['name'])) {
            throw new \UnexpectedValueException();
        }

        $end = strpos($book['name'], ' ') ?: 3;
        $name = substr($book['name'], 0, $end);

        $response = $this->get('/api/books?name='.$name, ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0], $book);
    }

    public function testDateSearchFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $book = $response->json()['hydra:member'][0];
        $updated = $this->patchJson(
            $book['@id'],
            ['publicationDate' => '2024-02-18 00:00:00'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate='.$updated['publicationDate'], ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0]['@id'], $book['@id']);
    }

    public function testSearchFilterWithPropertyPlaceholder(): void
    {
        $response = $this->get('/api/authors', ['accept' => ['application/ld+json']])->json();
        $author = $response['hydra:member'][0];

        $test = $this->get('/api/authors?name='.explode(' ', $author['name'])[0], ['accept' => ['application/ld+json']])->json();
        $this->assertSame($test['hydra:member'][0]['id'], $author['id']);

        $test = $this->get('/api/authors?id='.$author['id'], ['accept' => ['application/ld+json']])->json();
        $this->assertSame($test['hydra:member'][0]['id'], $author['id']);
    }

    public function testOrderFilterWithPropertyPlaceholder(): void
    {
        $res = $this->get('/api/authors?order[id]=desc', ['accept' => ['application/ld+json']]);
        $this->assertSame($res['hydra:member'][0]['id'], 10);
    }

    public function testOrFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']])->json()['hydra:member'];
        $book = $response[0];
        $book2 = $response[1];

        $res = $this->get(\sprintf('/api/books?name2[]=%s&name2[]=%s', $book['name'], $book2['name']), ['accept' => ['application/ld+json']])->json();
        $this->assertSame($res['hydra:totalItems'], 2);
    }
}
