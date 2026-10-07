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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

final class SubscriptionSchemaTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    use SubscriptionPublicationTrait;
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

    #[DataProvider('collectionChangeTypes')]
    public function testCollectionChangesUseTheRegisteredTopic(string $type): void
    {
        $client = self::createClient();
        $client->disableReboot();
        self::getContainer()->set('api_platform.graphql.cache.subscription', new TraceableAdapter(new ArrayAdapter()));

        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
subscription {
  update_collectionGraphQlSubscriptionPairSubscribe(input: {id: "/graph_ql_subscription_pairs/1"}) {
    graphQlSubscriptionPair {
      id
      _id
    }
    mercureUrl
  }
}
GRAPHQL,
        ]]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));
        parse_str(parse_url($json['data']['update_collectionGraphQlSubscriptionPairSubscribe']['mercureUrl'], \PHP_URL_QUERY), $query);

        $object = new GraphQlSubscriptionPair();
        $object->id = 2;
        $iri = '/graph_ql_subscription_pairs/2';
        $data = ['graphQlSubscriptionPair' => ['id' => $iri, '_id' => 2]];
        if ('delete' === $type) {
            $object = (object) [
                'resourceClass' => GraphQlSubscriptionPair::class,
                'id' => $iri,
                'iri' => 'http://example.com'.$iri,
                'type' => 'GraphQlSubscriptionPair',
                'private' => [],
            ];
            $data = ['type' => 'delete', 'payload' => ['id' => $iri, 'iri' => $object->iri, 'type' => $object->type]];
        }

        $payloads = $this->publishSubscriptions($object, $type);
        $this->assertCount(1, $payloads);
        [$subscriptionId, $payload] = $payloads[0];
        $this->assertSame($query['topic'], self::getContainer()->get('api_platform.graphql.subscription.mercure_iri_generator')->generateTopicIri($subscriptionId));
        $this->assertSame($data, $payload);
    }

    public static function collectionChangeTypes(): iterable
    {
        yield 'create' => ['create'];
        yield 'update' => ['update'];
        yield 'delete' => ['delete'];
    }
}
