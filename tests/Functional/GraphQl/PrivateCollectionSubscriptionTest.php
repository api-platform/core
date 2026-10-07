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
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

final class PrivateCollectionSubscriptionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    use SubscriptionPublicationTrait;

    protected static ?bool $alwaysBootKernel = false;
    private ?TraceableAdapter $cache = null;

    public static function getResources(): array
    {
        return [PrivateCollectionSubscriptionResource::class];
    }

    protected function tearDown(): void
    {
        $this->cache?->clear();
        parent::tearDown();
    }

    #[DataProvider('eventTypes')]
    public function testCollectionEnrollmentAndPublishingUseTheSamePrivatePartition(string $event): void
    {
        $client = $this->createSubscriptionClient();
        $firstTopic = $this->subscribe($client, 1);
        $this->assertSame($firstTopic, $this->subscribe($client, 2));
        $secondTopic = $this->subscribe($client, 3);
        $this->assertNotSame($firstTopic, $secondTopic);

        $topicGenerator = self::getContainer()->get('api_platform.graphql.subscription.mercure_iri_generator');
        foreach ([2 => $firstTopic, 4 => $secondTopic] as $id => $topic) {
            $object = PrivateCollectionSubscriptionResource::provide(new Get(), ['id' => $id]);
            $this->assertNotNull($object);
            $iri = '/private_collection_subscription_resources/'.$id;
            $expected = ['privateCollectionSubscriptionResource' => ['id' => $iri, '_id' => $id, 'name' => 'Initial']];
            if ('delete' === $event) {
                $object = (object) [
                    'resourceClass' => PrivateCollectionSubscriptionResource::class,
                    'id' => $iri,
                    'iri' => 'http://example.com'.$iri,
                    'type' => 'PrivateCollectionSubscriptionResource',
                    'private' => ['tenant' => $object->tenant],
                ];
                $expected = ['type' => 'delete', 'payload' => ['id' => $iri, 'iri' => $object->iri, 'type' => $object->type]];
            }

            $payloads = $this->publishSubscriptions($object, $event);
            $this->assertCount(1, $payloads);
            $this->assertSame($topic, $topicGenerator->generateTopicIri($payloads[0][0]));
            $this->assertEquals($expected, $payloads[0][1]);
        }
    }

    public static function eventTypes(): iterable
    {
        yield 'create' => ['create'];
        yield 'update' => ['update'];
        yield 'delete' => ['delete'];
    }

    public function testMissingEnrollmentItemIsRejected(): void
    {
        $client = $this->createSubscriptionClient();
        $json = $this->requestSubscription($client, 404);
        $this->assertArrayHasKey('errors', $json);
        $this->assertStringContainsString('not found', $json['errors'][0]['message']);
        $this->assertEmpty($json['data']['watchPrivateCollectionSubscriptionResourceSubscribe']['mercureUrl'] ?? null);
    }

    public function testSecurityReceivesTheEnrollmentItem(): void
    {
        $client = $this->createSubscriptionClient();
        $this->subscribe($client, 1, 'secured');
        $json = $this->requestSubscription($client, 3, 'secured');
        $this->assertArrayHasKey('errors', $json);
        $this->assertStringContainsString('Access Denied', $json['errors'][0]['message']);
        $this->assertEmpty($json['data']['securedPrivateCollectionSubscriptionResourceSubscribe']['mercureUrl'] ?? null);
        $object = PrivateCollectionSubscriptionResource::provide(new Get(), ['id' => 3]);
        $this->assertNotNull($object);
        $this->assertSame([], $this->publishSubscriptions($object));
    }

    private function createSubscriptionClient(): Client
    {
        $client = self::createClient();
        $client->disableReboot();
        $this->cache = new TraceableAdapter(new FilesystemAdapter('private_collection_subscription_'.bin2hex(random_bytes(8))));
        self::getContainer()->set('api_platform.graphql.cache.subscription', $this->cache);

        return $client;
    }

    private function subscribe(Client $client, int $id, string $operation = 'watch'): string
    {
        $json = $this->requestSubscription($client, $id, $operation);
        $this->assertResponseIsSuccessful();
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));
        parse_str(parse_url($json['data'][$operation.'PrivateCollectionSubscriptionResourceSubscribe']['mercureUrl'], \PHP_URL_QUERY), $query);

        return $query['topic'];
    }

    private function requestSubscription(Client $client, int $id, string $operation = 'watch'): array
    {
        return $client->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('subscription ($id: ID!) { %sPrivateCollectionSubscriptionResourceSubscribe(input: {id: $id}) { privateCollectionSubscriptionResource { id _id name } mercureUrl } }', $operation),
            'variables' => ['id' => '/private_collection_subscription_resources/'.$id],
        ]])->toArray(false);
    }
}
