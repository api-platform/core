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
        $book = $response->json()['member'][0];

        $response = $this->get('/api/books?isbn='.$book['isbn'], ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0], $book);
    }

    public function testValidateSearchFilter(): void
    {
        $response = $this->get('/api/books?isbn=a', ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['detail'], 'The isbn field must be at least 2 characters.');
    }

    public function testSearchFilterRelation(): void
    {
        $response = $this->get('/api/books?author=1', ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0]['author'], '/api/authors/1');
    }

    public function testPropertyFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];

        $response = $this->get(\sprintf('%s.jsonld?properties[]=author', $book['@id']));
        $book = $response->json();

        $this->assertArrayHasKey('@id', $book);
        $this->assertArrayHasKey('author', $book);
        $this->assertArrayNotHasKey('name', $book);
    }

    public function testPartialSearchFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];

        if (!isset($book['name'])) {
            throw new \UnexpectedValueException();
        }

        $end = strpos($book['name'], ' ') ?: 3;
        $name = substr($book['name'], 0, $end);

        $response = $this->get('/api/books?name='.$name, ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0], $book);
    }

    public function testDateFilterEqual(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];
        $updated = $this->patchJson(
            $book['@id'],
            ['publicationDate' => '2024-02-18 00:00:00'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate[eq]='.$updated['publicationDate'], ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0]['@id'], $book['@id']);
    }

    public function testDateFilterIncludeNull(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $book = $response->json()['hydra:member'][0];
        $updated = $this->patchJson(
            $book['@id'],
            ['publicationDate' => null],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate2[gt]=9999-12-31', ['accept' => ['application/ld+json']]);
        $this->assertGreaterThan(0, $response->json()['hydra:totalItems']);
    }

    public function testDateFilterExcludeNull(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $book = $response->json()['hydra:member'][0];
        $updated = $this->patchJson(
            $book['@id'],
            ['publicationDate' => null],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate[gt]=9999-12-31', ['accept' => ['application/ld+json']]);
        $this->assertSame(0, $response->json()['hydra:totalItems']);
    }

    public function testDateFilterGreaterThan(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['hydra:member'][0];
        $updated = $this->patchJson(
            $bookBefore['@id'],
            ['publicationDate' => '9998-02-18 00:00:00'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['hydra:member'][1];
        $this->patchJson(
            $bookAfter['@id'],
            ['publicationDate' => '9999-02-18 00:00:00'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate[gt]='.$updated['publicationDate'], ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0]['@id'], $bookAfter['@id']);
        $this->assertSame($response->json()['hydra:totalItems'], 1);
    }

    public function testDateFilterLowerThanEqual(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['hydra:member'][0];
        $updated = $this->patchJson(
            $bookBefore['@id'],
            ['publicationDate' => '0001-02-18 00:00:00'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['hydra:member'][1];
        $this->patchJson(
            $bookAfter['@id'],
            ['publicationDate' => '0002-02-18 00:00:00'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate[lte]=0002-02-18', ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0]['@id'], $bookBefore['@id']);
        $this->assertSame($response->json()['hydra:member'][1]['@id'], $bookAfter['@id']);
        $this->assertSame($response->json()['hydra:totalItems'], 2);
    }

    public function testDateFilterBetween(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $book = $response->json()['hydra:member'][0];
        $updated = $this->patchJson(
            $book['@id'],
            ['publicationDate' => '0001-02-18 00:00:00'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $book2 = $response->json()['hydra:member'][1];
        $this->patchJson(
            $book2['@id'],
            ['publicationDate' => '0002-02-18 00:00:00'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $book3 = $response->json()['hydra:member'][2];
        $updated3 = $this->patchJson(
            $book3['@id'],
            ['publicationDate' => '0003-02-18 00:00:00'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate[gte]='.substr($updated['publicationDate'], 0, 10).'&publicationDate[lt]='.substr($updated3['publicationDate'], 0, 10), ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0]['@id'], $book['@id']);
        $this->assertSame($response->json()['hydra:member'][1]['@id'], $book2['@id']);
        $this->assertSame($response->json()['hydra:totalItems'], 2);
    }

    public function testSearchFilterWithPropertyPlaceholder(): void
    {
        $response = $this->get('/api/authors', ['accept' => ['application/ld+json']])->json();
        $author = $response['member'][0];

        $test = $this->get('/api/authors?name='.explode(' ', $author['name'])[0], ['accept' => ['application/ld+json']])->json();
        $this->assertSame($test['member'][0]['id'], $author['id']);

        $test = $this->get('/api/authors?id='.$author['id'], ['accept' => ['application/ld+json']])->json();
        $this->assertSame($test['member'][0]['id'], $author['id']);
    }

    public function testOrderFilterWithPropertyPlaceholder(): void
    {
        $res = $this->get('/api/authors?order[id]=desc', ['accept' => ['application/ld+json']]);
        $this->assertSame($res['member'][0]['id'], 10);
    }

    public function testOrFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']])->json()['member'];
        $book = $response[0];
        $book2 = $response[1];

        $res = $this->get(sprintf('/api/books?name2[]=%s&name2[]=%s', $book['name'], $book2['name']), ['accept' => ['application/ld+json']])->json();
        $this->assertSame($res['hydra:totalItems'], 2);
    }

    public function testRangeLowerThanFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['hydra:member'][0];
        $this->patchJson(
            $bookBefore['@id'],
            ['isbn' => '12'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['hydra:member'][1];
        $updated = $this->patchJson(
            $bookAfter['@id'],
            ['isbn' => '15'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('api/books?isbn_range[lt]='.$updated['isbn'], ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0]['@id'], $bookBefore['@id']);
        $this->assertSame($response->json()['hydra:totalItems'], 1);
    }

    public function testRangeLowerThanEqualFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['hydra:member'][0];
        $this->patchJson(
            $bookBefore['@id'],
            ['isbn' => '12'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['hydra:member'][1];
        $updated = $this->patchJson(
            $bookAfter['@id'],
            ['isbn' => '15'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('api/books?isbn_range[lte]='.$updated['isbn'], ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0]['@id'], $bookBefore['@id']);
        $this->assertSame($response->json()['hydra:member'][1]['@id'], $bookAfter['@id']);
        $this->assertSame($response->json()['hydra:totalItems'], 2);
    }

    public function testRangeGreaterThanFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['hydra:member'][0];
        $updated = $this->patchJson(
            $bookBefore['@id'],
            ['isbn' => '999999999999998'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['hydra:member'][1];
        $this->patchJson(
            $bookAfter['@id'],
            ['isbn' => '999999999999999'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('api/books?isbn_range[gt]='.$updated['isbn'], ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0]['@id'], $bookAfter['@id']);
        $this->assertSame($response->json()['hydra:totalItems'], 1);
    }

    public function testRangeGreaterThanEqualFilter(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['hydra:member'][0];
        $updated = $this->patchJson(
            $bookBefore['@id'],
            ['isbn' => '999999999999998'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['hydra:member'][1];
        $this->patchJson(
            $bookAfter['@id'],
            ['isbn' => '999999999999999'],
            [
                'accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('api/books?isbn_range[gte]='.$updated['isbn'], ['accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['hydra:member'][0]['@id'], $bookBefore['@id']);
        $this->assertSame($response->json()['hydra:member'][1]['@id'], $bookAfter['@id']);
        $this->assertSame($response->json()['hydra:totalItems'], 2);
    }
}
