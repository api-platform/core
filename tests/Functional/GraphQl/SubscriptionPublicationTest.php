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
use ApiPlatform\Symfony\Bundle\Test\Client;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubscriptionPublicationResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

final class SubscriptionPublicationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;
    private ?TraceableAdapter $cache = null;
    private bool $schemaCreated = false;

    public static function getResources(): array
    {
        return [SubscriptionPublicationResource::class];
    }

    protected function tearDown(): void
    {
        if ($this->schemaCreated) {
            $manager = self::getContainer()->get('doctrine')->getManager();
            $this->assertInstanceOf(EntityManagerInterface::class, $manager);
            (new SchemaTool($manager))->dropSchema([$manager->getClassMetadata(SubscriptionPublicationResource::class)]);
        }
        $this->cache?->clear();
        parent::tearDown();
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testSubscriptionsOwnTheirPublicationWithOrWithoutRest(bool $restEnabled): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $container = self::getContainer();
        $this->cache = new TraceableAdapter(new FilesystemAdapter('subscription_privacy_'.bin2hex(random_bytes(8))));
        $container->set('api_platform.graphql.cache.subscription', $this->cache);
        $updates = [];
        $hub = new MockHub('http://example.com/.well-known/mercure', new StaticTokenProvider('test'), static function (Update $update) use (&$updates): string {
            $updates[] = $update;

            return 'id';
        });
        $restUpdates = [];
        $restHub = new MockHub('http://rest.example/.well-known/mercure', new StaticTokenProvider('test'), static function (Update $update) use (&$restUpdates): string {
            $restUpdates[] = $update;

            return 'rest-id';
        });
        $container->set(HubRegistry::class, new HubRegistry($restHub, ['rest' => $restHub, 'graphql' => $hub]));
        $manager = $container->get('doctrine')->getManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $manager);
        (new SchemaTool($manager))->createSchema([$manager->getClassMetadata(SubscriptionPublicationResource::class)]);
        $this->schemaCreated = true;
        $resource = new SubscriptionPublicationResource();
        $resource->id = 1;
        $resource->restEnabled = $restEnabled;
        $manager->persist($resource);
        $manager->flush();

        $topics = [];
        foreach (['item', 'watch', 'conditional'] as $name) {
            $topics[$name] = $this->subscribe($client, $name);
        }
        $this->assertCount(3, array_unique($topics));
        $manager = self::getContainer()->get('doctrine')->getManager();
        $resource = $manager->find(SubscriptionPublicationResource::class, 1);
        $this->assertNotNull($resource);

        $updates = [];
        $restUpdates = [];
        $created = new SubscriptionPublicationResource();
        $created->id = 2;
        $created->restEnabled = $restEnabled;
        $manager->persist($created);
        $manager->flush();
        $this->assertUpdates($updates, array_intersect_key($topics, array_flip(['watch', 'conditional'])), 2, 'Initial');
        $this->assertCount($restEnabled ? 2 : 0, $restUpdates);

        $updates = [];
        $restUpdates = [];
        $resource->name = 'Changed';
        $manager->flush();
        $this->assertUpdates($updates, $topics, 1, 'Changed');
        $this->assertCount($restEnabled ? 2 : 0, $restUpdates);

        $updates = [];
        $resource->name = null;
        $manager->flush();
        $this->assertUpdates($updates, $topics, 1, null);

        // A disabled expression suppresses delivery even when its subscription is cached.
        $updates = [];
        $restUpdates = [];
        $resource->name = 'Muted';
        $manager->flush();
        unset($topics['conditional']);
        $this->assertUpdates($updates, $topics, 1, 'Muted');

        $updates = [];
        $restUpdates = [];
        $manager->remove($resource);
        $manager->flush();
        $this->assertUpdates($updates, $topics, 1, null, true);
        $this->assertCount($restEnabled ? 2 : 0, $restUpdates);
    }

    private function subscribe(Client $client, string $operation): string
    {
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('subscription { %sScopedPublicationSubscribe(input: {id: "/subscription_publication_resources/1"}) { scopedPublication { id name } mercureUrl } }', $operation),
        ]]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));
        $this->assertStringStartsWith('http://example.com/.well-known/mercure?', $json['data'][$operation.'ScopedPublicationSubscribe']['mercureUrl']);
        parse_str(parse_url($json['data'][$operation.'ScopedPublicationSubscribe']['mercureUrl'], \PHP_URL_QUERY), $query);

        return $query['topic'];
    }

    /** @param Update[] $updates */
    private function assertUpdates(array $updates, array $topics, int $id, ?string $name, bool $deleted = false): void
    {
        $iri = '/subscription_publication_resources/'.$id;
        $this->assertCount(\count($topics), $updates);
        $this->assertEqualsCanonicalizing(array_values($topics), array_map(static fn (Update $update) => $update->getTopics()[0], $updates));
        foreach ($updates as $update) {
            $this->assertTrue($update->isPrivate());
            $expected = $deleted
                ? ['type' => 'delete', 'payload' => ['id' => $iri, 'iri' => 'http://localhost'.$iri, 'type' => 'SubscriptionPublicationResource']]
                : ['scopedPublication' => ['id' => $iri] + (null === $name ? [] : ['name' => $name]) + ['_id' => $id]];
            $this->assertSame($expected, json_decode($update->getData(), true, flags: \JSON_THROW_ON_ERROR));
        }
    }
}
