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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubscriptionPrivacyResource;
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

final class SubscriptionPrivacyTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;
    private ?TraceableAdapter $cache = null;
    private bool $schemaCreated = false;

    public static function getResources(): array
    {
        return [SubscriptionPrivacyResource::class];
    }

    protected function tearDown(): void
    {
        if ($this->schemaCreated) {
            $manager = self::getContainer()->get('doctrine')->getManager();
            $this->assertInstanceOf(EntityManagerInterface::class, $manager);
            (new SchemaTool($manager))->dropSchema([$manager->getClassMetadata(SubscriptionPrivacyResource::class)]);
        }
        $this->cache?->clear();
        parent::tearDown();
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testEachSubscriptionControlsTheMercurePrivateFlag(bool $restPrivate): void
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
        $container->set(HubRegistry::class, new HubRegistry($hub));
        $container->set('mercure.hub.default', $hub);
        $manager = $container->get('doctrine')->getManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $manager);
        (new SchemaTool($manager))->createSchema([$manager->getClassMetadata(SubscriptionPrivacyResource::class)]);
        $this->schemaCreated = true;
        $resource = new SubscriptionPrivacyResource();
        $resource->id = 1;
        $resource->restPrivate = $restPrivate;
        $manager->persist($resource);
        $manager->flush();

        $topics = [];
        foreach (['privateItem', 'publicItem', 'privateCollection', 'publicCollection', 'inherited'] as $name) {
            $topics[$name] = $this->subscribe($client, $name);
        }
        $this->assertCount(5, array_unique($topics));
        $manager = self::getContainer()->get('doctrine')->getManager();
        $resource = $manager->find(SubscriptionPrivacyResource::class, 1);
        $this->assertNotNull($resource);

        $updates = [];
        $created = new SubscriptionPrivacyResource();
        $created->id = 2;
        $created->restPrivate = $restPrivate;
        $manager->persist($created);
        $manager->flush();
        $this->assertUpdates($updates, array_intersect_key($topics, array_flip(['privateCollection', 'publicCollection', 'inherited'])), $restPrivate, 2, 'Initial');

        $updates = [];
        $resource->name = 'Changed';
        $manager->flush();
        $this->assertUpdates($updates, $topics, $restPrivate, 1, 'Changed');

        $updates = [];
        $manager->remove($resource);
        $manager->flush();
        $this->assertUpdates($updates, $topics, $restPrivate, 1, null);
    }

    private function subscribe(Client $client, string $operation): string
    {
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('subscription { %sSubscriptionPrivacyResourceSubscribe(input: {id: "/subscription_privacy_resources/1"}) { subscriptionPrivacyResource { id name } mercureUrl } }', $operation),
        ]]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));
        parse_str(parse_url($json['data'][$operation.'SubscriptionPrivacyResourceSubscribe']['mercureUrl'], \PHP_URL_QUERY), $query);

        return $query['topic'];
    }

    /** @param Update[] $updates */
    private function assertUpdates(array $updates, array $topics, bool $restPrivate, int $id, ?string $name): void
    {
        $iri = '/subscription_privacy_resources/'.$id;
        $this->assertCount(\count($topics) + 1, $updates);
        $this->assertSame(['http://localhost'.$iri], $updates[0]->getTopics());
        $this->assertSame($restPrivate, $updates[0]->isPrivate());
        $this->assertEqualsCanonicalizing(array_values($topics), array_map(static fn (Update $update) => $update->getTopics()[0], \array_slice($updates, 1)));
        foreach (\array_slice($updates, 1) as $update) {
            $operation = array_search($update->getTopics()[0], $topics, true);
            $this->assertSame('inherited' === $operation ? $restPrivate : str_starts_with($operation, 'private'), $update->isPrivate(), $operation);
            $expected = null === $name
                ? ['type' => 'delete', 'payload' => ['id' => $iri, 'iri' => 'http://localhost'.$iri, 'type' => 'SubscriptionPrivacyResource']]
                : ['subscriptionPrivacyResource' => ['id' => $iri, 'name' => $name, '_id' => $id]];
            $this->assertSame($expected, json_decode($update->getData(), true, flags: \JSON_THROW_ON_ERROR));
        }
    }
}
