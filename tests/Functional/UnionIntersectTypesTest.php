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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\Author;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\Library;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class UnionIntersectTypesTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Book::class, Author::class, Library::class];
    }

    public function testCreateBookWithUnionTypeNumberAsString(): void
    {
        $response = self::createClient()->request('POST', '/issue-5452/books', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['number' => '1', 'isbn' => '978-3-16-148410-0'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertSame('Book', $data['@type']);
        $this->assertSame('/contexts/Book', $data['@context']);
        $this->assertMatchesRegularExpression('#^/.well-known/genid/.+$#', $data['@id']);
        $this->assertSame('1', $data['number']);
        $this->assertSame('978-3-16-148410-0', $data['isbn']);
    }

    public function testCreateBookWithUnionTypeNumberAsInteger(): void
    {
        $response = self::createClient()->request('POST', '/issue-5452/books', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['number' => 1, 'isbn' => '978-3-16-148410-0'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(1, $data['number']);
        $this->assertSame('978-3-16-148410-0', $data['isbn']);
    }

    public function testCreateBookWithValidIntersectType(): void
    {
        $response = self::createClient()->request('POST', '/issue-5452/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'number' => 1,
                'isbn' => '978-3-16-148410-0',
                'author' => '/issue-5452/authors/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertSame('Book', $data['@type']);
        $this->assertSame(1, $data['number']);
        $this->assertSame('978-3-16-148410-0', $data['isbn']);
        $this->assertSame('/issue-5452/authors/1', $data['author']);
    }

    public function testCreateBookWithInvalidIntersectTypeReturns400(): void
    {
        self::createClient()->request('POST', '/issue-5452/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'number' => 1,
                'isbn' => '978-3-16-148410-0',
                'library' => '/issue-5452/libraries/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            'detail' => 'Could not denormalize object of type "ApiPlatform\\Tests\\Fixtures\\TestBundle\\ApiResource\\Issue5452\\ActivableInterface", no supporting normalizer found.',
        ]);
    }
}
