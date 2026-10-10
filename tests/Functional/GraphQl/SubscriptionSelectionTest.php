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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PrivateCollectionSubscriptionResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PrivateSubscriptionResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

final class SubscriptionSelectionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    use SubscriptionPublicationTrait;

    protected static ?bool $alwaysBootKernel = false;

    /** @var array<string, TraceableAdapter> */
    private array $caches = [];

    protected function tearDown(): void
    {
        foreach ($this->caches as $cache) {
            $cache->clear();
        }
        parent::tearDown();
    }

    public static function getResources(): array
    {
        return [PrivateSubscriptionResource::class, PrivateCollectionSubscriptionResource::class];
    }

    #[DataProvider('equivalentSelections')]
    public function testEquivalentSelectionsHaveOneRegistrationAndPublication(bool $collection, string $fields, string $extras, bool $reverse): void
    {
        $client = $this->createSubscriptionClient();
        $cache = $this->caches['subscription'];
        $shortName = $collection ? 'PrivateCollectionSubscriptionResource' : 'PrivateSubscriptionResource';
        $resourceField = lcfirst($shortName);
        $selections = [['id _id name', 'mercureUrl'], [$fields, $extras]];
        if ($reverse) {
            $selections = array_reverse($selections);
        }
        $urls = [];
        foreach ($selections as [$selection, $outerFields]) {
            $enrollment = $this->enroll($client, $shortName, $selection, $outerFields);
            if ($collection) {
                $this->assertNull($enrollment[$resourceField]);
            } else {
                $this->assertSame('Initial', $enrollment[$resourceField]['name']);
            }
            if (isset($enrollment['mercureUrl'])) {
                $urls[] = $enrollment['mercureUrl'];
            }
        }
        $this->assertCount(1, array_unique($urls));
        $keys = [];
        foreach ($cache->getCalls() as $call) {
            if ('getItem' === $call->name) {
                $keys = array_merge($keys, array_keys($call->result));
            }
        }
        $keys = array_values(array_unique($keys));
        $this->assertCount(1, $keys);
        $entries = $cache->getItem($keys[0])->get();
        $this->assertCount(1, $entries[$shortName.':watch']);
        [$id, $selection] = $entries[$shortName.':watch'][0];
        $this->assertSame([$resourceField => ['_id' => true, 'id' => true, 'name' => true]], $selection);
        parse_str(parse_url($urls[0], \PHP_URL_QUERY), $query);
        $topicGenerator = self::getContainer()->get('api_platform.graphql.subscription.mercure_iri_generator');
        $this->assertSame($query['topic'], $topicGenerator->generateTopicIri($id));

        $resource = $collection ? PrivateCollectionSubscriptionResource::provide(new Get(), ['id' => 1]) : PrivateSubscriptionResource::provide(new Get(), ['id' => 1]);
        $this->assertNotNull($resource);
        $resource->name = 'Changed';
        $iri = $collection ? '/private_collection_subscription_resources/1' : '/private_subscription_resources/1';
        $expected = [[$id, [$resourceField => ['id' => $iri, 'name' => 'Changed', '_id' => 1]]]];
        $this->assertSame($expected, $this->publishSubscriptions($resource));
        $this->assertSame($collection ? $expected : [], $this->publishSubscriptions($resource));
        if ($collection) {
            $this->assertSame($expected, $this->publishSubscriptions($resource, 'create'));
        }

        $snapshot = (object) ['resourceClass' => $resource::class, 'id' => $iri, 'iri' => 'http://example.com'.$iri, 'type' => $shortName, 'private' => ['tenant' => $resource->tenant]];
        $this->assertSame([[$id, ['type' => 'delete', 'payload' => ['id' => $iri, 'iri' => $snapshot->iri, 'type' => $shortName]]]], $this->publishSubscriptions($snapshot, 'delete'));
        $this->assertSame($collection, $cache->hasItem($keys[0]));
    }

    public static function equivalentSelections(): iterable
    {
        foreach ([false, true] as $collection) {
            foreach ([false, true] as $reverse) {
                foreach ([
                    'root typename' => ['id _id name', '__typename mercureUrl'],
                    'nested typename' => ['id _id name __typename', 'mercureUrl'],
                    'client id' => ['id _id name', 'clientSubscriptionId mercureUrl'],
                    'no URL' => ['id _id name', ''],
                    'field order' => ['name _id id', 'mercureUrl'],
                ] as $name => [$fields, $extras]) {
                    yield ($collection ? 'collection' : 'item').' '.$name.($reverse ? ' reversed' : '') => [$collection, $fields, $extras, $reverse];
                }
            }
        }
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testDifferentPayloadSelectionsRetainDistinctTopics(bool $collection): void
    {
        $client = $this->createSubscriptionClient();
        $shortName = $collection ? 'PrivateCollectionSubscriptionResource' : 'PrivateSubscriptionResource';
        $first = $this->enroll($client, $shortName, 'id _id name', 'mercureUrl');
        $second = $this->enroll($client, $shortName, 'name', 'mercureUrl');
        $this->assertNotSame($first['mercureUrl'], $second['mercureUrl']);
        $resource = $collection ? PrivateCollectionSubscriptionResource::provide(new Get(), ['id' => 1]) : PrivateSubscriptionResource::provide(new Get(), ['id' => 1]);
        $this->assertNotNull($resource);
        $resource->name = 'Changed';
        $payloads = $this->publishSubscriptions($resource);
        $this->assertCount(2, $payloads);
        $this->assertNotSame($payloads[0][0], $payloads[1][0]);
        $iri = $collection ? '/private_collection_subscription_resources/1' : '/private_subscription_resources/1';
        $this->assertSame([lcfirst($shortName) => ['id' => $iri, 'name' => 'Changed', '_id' => 1]], $payloads[0][1]);
        $this->assertSame([lcfirst($shortName) => ['name' => 'Changed']], $payloads[1][1]);
    }

    private function createSubscriptionClient(): Client
    {
        $client = self::createClient();
        $client->disableReboot();
        foreach (['subscription', 'subscription_fingerprint'] as $name) {
            $cache = $this->caches[$name] = new TraceableAdapter(new FilesystemAdapter($name.'_selection_'.bin2hex(random_bytes(8))));
            self::getContainer()->set('api_platform.graphql.cache.'.$name, $cache);
        }

        return $client;
    }

    private function enroll(Client $client, string $shortName, string $fields, string $extras): array
    {
        $iri = 'PrivateSubscriptionResource' === $shortName ? '/private_subscription_resources/1' : '/private_collection_subscription_resources/1';
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('subscription { watch%sSubscribe(input: {id: "%s", clientSubscriptionId: "client"}) { %s { %s } %s } }', $shortName, $iri, lcfirst($shortName), $fields, $extras),
        ]]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));

        return $json['data']['watch'.$shortName.'Subscribe'];
    }
}
