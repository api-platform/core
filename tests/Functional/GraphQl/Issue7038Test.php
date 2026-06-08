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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7038\Author;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7038\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7038\Category;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * A DTO with a Doctrine \Doctrine\Common\Collections\Collection of nested
 * resources must expose its items in the cursor-based pagination wrapper
 * (`edges/node`) the same way an array would.
 *
 * @see https://github.com/api-platform/core/issues/7038
 */
final class Issue7038Test extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Book::class, Author::class, Category::class];
    }

    public function testDoctrineCollectionInDtoIsExposedThroughCursorPagination(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
{
  book(id: "/books/1") {
    title
    author {
      name
    }
    categories {
      edges {
        node {
          name
        }
      }
    }
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame('Sample Book', $json['data']['book']['title']);
        $this->assertSame('John Doe', $json['data']['book']['author']['name']);
        $edges = $json['data']['book']['categories']['edges'];
        $this->assertIsArray($edges);
        $this->assertCount(2, $edges);
        $this->assertSame('Fiction', $edges[0]['node']['name']);
        $this->assertSame('Adventure', $edges[1]['node']['name']);
    }
}
