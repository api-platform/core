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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8076\Facility;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8076\Product;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8076\Variant;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * A root resource with a custom provider returns nested resources whose own
 * {@see \ApiPlatform\Metadata\ApiResource::$graphQlOperations} is an empty array.
 * The framework auto-adds `nested: true` Query/QueryCollection operations on those
 * nested resources, with no provider. The resolver must reuse the data already
 * loaded by the root provider through {@see $source} instead of trying to call the
 * (non-existent) nested provider, which would raise
 * `Provider not found on operation "collection_query"`.
 *
 * @see https://github.com/api-platform/core/issues/8076
 */
final class Issue8076Test extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Product::class, Facility::class, Variant::class];
    }

    public function testNestedCollectionWithoutGraphQlOperationsUsesParentProviderData(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
{
  product(id: "/products/1") {
    id
    name
    facility {
      name
      variants {
        edges {
          node {
            sku
          }
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
        $this->assertSame('a product', $json['data']['product']['name']);
        $this->assertSame('a facility', $json['data']['product']['facility']['name']);
        $edges = $json['data']['product']['facility']['variants']['edges'];
        $this->assertCount(2, $edges);
        $this->assertSame('sku-1', $edges[0]['node']['sku']);
        $this->assertSame('sku-2', $edges[1]['node']['sku']);
    }
}
