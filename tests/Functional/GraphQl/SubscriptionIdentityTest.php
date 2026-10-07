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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PrivateSubscriptionResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

final class SubscriptionIdentityTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    use SubscriptionPublicationTrait;

    protected static ?bool $alwaysBootKernel = false;
    private TraceableAdapter $cache;

    public static function getResources(): array
    {
        return [PrivateSubscriptionResource::class];
    }

    protected function tearDown(): void
    {
        if (isset($this->cache)) {
            $this->cache->clear();
        }
        parent::tearDown();
    }

    public function testPrivateUpdatesUseDistinctTopicsForEachItemAndOperation(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $cache = $this->cache = new TraceableAdapter(new FilesystemAdapter('subscription_identity_'.bin2hex(random_bytes(8))));
        self::getContainer()->set('api_platform.graphql.cache.subscription', $cache);

        $topics = [
            $this->subscribe($client, 1, 'update'),
            $this->subscribe($client, 2, 'update'),
            $this->subscribe($client, 3, 'update'),
            $this->subscribe($client, 1, 'watch'),
        ];
        $this->assertCount(4, array_unique($topics));
        $this->assertSame($topics[0], $this->subscribe($client, 1, 'update'));

        $cache->clear();
        $this->assertSame($topics, [
            $this->subscribe($client, 1, 'update'),
            $this->subscribe($client, 2, 'update'),
            $this->subscribe($client, 3, 'update'),
            $this->subscribe($client, 1, 'watch'),
        ]);

        $topicGenerator = self::getContainer()->get('api_platform.graphql.subscription.mercure_iri_generator');
        $resource = PrivateSubscriptionResource::provide(new Get(), ['id' => 1]);
        $resource->name = 'Changed';
        $payloads = $this->publishSubscriptions($resource, 'update');
        $this->assertCount(2, $payloads);
        $this->assertSame([$topics[0], $topics[3]], array_map(static fn (array $payload) => $topicGenerator->generateTopicIri($payload[0]), $payloads));
        foreach ($payloads as [$id, $payload]) {
            $this->assertSame(['privateSubscriptionResource' => ['id' => '/private_subscription_resources/1', 'name' => 'Changed', '_id' => 1]], $payload);
        }

        $snapshot = (object) [
            'resourceClass' => PrivateSubscriptionResource::class,
            'id' => '/private_subscription_resources/1',
            'iri' => 'http://example.com/private_subscription_resources/1',
            'type' => 'PrivateSubscriptionResource',
            'private' => ['tenant' => 'tenant-a'],
        ];
        $payloads = $this->publishSubscriptions($snapshot, 'delete');
        $this->assertCount(2, $payloads);
        $this->assertSame([$topics[0], $topics[3]], array_map(static fn (array $payload) => $topicGenerator->generateTopicIri($payload[0]), $payloads));
        foreach ($payloads as [$id, $payload]) {
            $this->assertSame(['type' => 'delete', 'payload' => ['id' => $snapshot->id, 'iri' => $snapshot->iri, 'type' => $snapshot->type]], $payload);
        }
        $this->assertSame([], $this->publishSubscriptions($snapshot, 'delete'));

        $other = PrivateSubscriptionResource::provide(new Get(), ['id' => 2]);
        $other->name = 'Another change';
        $payloads = $this->publishSubscriptions($other, 'update');
        $this->assertCount(1, $payloads);
        $this->assertSame($topics[1], $topicGenerator->generateTopicIri($payloads[0][0]));
    }

    private function subscribe(Client $client, int $id, string $operation): string
    {
        $field = $operation.'PrivateSubscriptionResourceSubscribe';
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('subscription ($id: ID!) { %s(input: {id: $id}) { privateSubscriptionResource { id _id name } mercureUrl } }', $field),
            'variables' => ['id' => '/private_subscription_resources/'.$id],
        ]]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));
        $this->assertArrayNotHasKey('tenant', $json['data'][$field]['privateSubscriptionResource']);
        parse_str(parse_url($json['data'][$field]['mercureUrl'], \PHP_URL_QUERY), $query);

        return $query['topic'];
    }
}
