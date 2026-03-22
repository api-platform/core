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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GraphQlSubscriptionPair;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SubscriptionSchemaTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [GraphQlSubscriptionPair::class];
    }

    public function testItemAndCollectionSubscriptionsCoexistInSchema(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
{
  __schema {
    subscriptionType {
      fields {
        name
      }
    }
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json);

        $fieldNames = array_column($json['data']['__schema']['subscriptionType']['fields'], 'name');

        $this->assertContains('updateGraphQlSubscriptionPairSubscribe', $fieldNames);
        $this->assertContains('update_collectionGraphQlSubscriptionPairSubscribe', $fieldNames);
    }

    public function testItemSubscriptionReturnsMercureMetadata(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
subscription {
  updateGraphQlSubscriptionPairSubscribe(input: {id: "/graph_ql_subscription_pairs/1"}) {
    graphQlSubscriptionPair {
      id
    }
    clientSubscriptionId
    mercureUrl
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json);

        $payload = $json['data']['updateGraphQlSubscriptionPairSubscribe'];
        $this->assertSame('/graph_ql_subscription_pairs/1', $payload['graphQlSubscriptionPair']['id']);
        $this->assertNull($payload['clientSubscriptionId']);
        $this->assertNotEmpty($payload['mercureUrl']);
    }

    public function testCollectionSubscriptionReturnsMercureMetadata(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
subscription {
  update_collectionGraphQlSubscriptionPairSubscribe(input: {id: "/graph_ql_subscription_pairs/1"}) {
    graphQlSubscriptionPair {
      id
    }
    clientSubscriptionId
    mercureUrl
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json);

        $payload = $json['data']['update_collectionGraphQlSubscriptionPairSubscribe'];
        $this->assertNull($payload['graphQlSubscriptionPair']);
        $this->assertNull($payload['clientSubscriptionId']);
        $this->assertNotEmpty($payload['mercureUrl']);
    }
}
