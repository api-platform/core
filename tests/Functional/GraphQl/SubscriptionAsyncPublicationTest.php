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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubscriptionAsyncResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;

final class SubscriptionAsyncPublicationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;
    private ?TraceableAdapter $cache = null;
    private bool $schemaCreated = false;

    public static function getResources(): array
    {
        return [SubscriptionAsyncResource::class];
    }

    protected function tearDown(): void
    {
        if ($this->schemaCreated) {
            $manager = self::getContainer()->get('doctrine')->getManager();
            $this->assertInstanceOf(EntityManagerInterface::class, $manager);
            (new SchemaTool($manager))->dropSchema([$manager->getClassMetadata(SubscriptionAsyncResource::class)]);
        }
        $this->cache?->clear();
        parent::tearDown();
    }

    public function testQueuedPublicationsReachTheirOwnHub(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $container = self::getContainer();
        $this->cache = new TraceableAdapter(new FilesystemAdapter('subscription_async_'.bin2hex(random_bytes(8))));
        $container->set('api_platform.graphql.cache.subscription', $this->cache);
        $publications = [];
        $hubs = [];
        foreach (['default', 'rest', 'graphql'] as $name) {
            $hubs[$name] = new MockHub('http://'.$name.'.example/.well-known/mercure', new StaticTokenProvider('test'), static function (Update $update) use (&$publications, $name): string {
                $publications[$name][] = $update;

                return 'id';
            });
        }
        $container->set(HubRegistry::class, new HubRegistry($hubs['default'], $hubs));
        $transport = new InMemoryTransport(Serializer::create());
        $container->set('messenger.senders_locator', new SendersLocator([Update::class => ['async']], new ServiceLocator(['async' => static fn () => $transport])));
        $manager = $container->get('doctrine')->getManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $manager);
        (new SchemaTool($manager))->createSchema([$manager->getClassMetadata(SubscriptionAsyncResource::class)]);
        $this->schemaCreated = true;
        $resource = new SubscriptionAsyncResource();
        $resource->id = 1;
        $manager->persist($resource);
        $manager->flush();

        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => 'subscription { watchSubscriptionAsyncResourceSubscribe(input: {id: "/subscription_async_resources/1"}) { subscriptionAsyncResource { id name } mercureUrl } }',
        ]]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));
        $url = $json['data']['watchSubscriptionAsyncResourceSubscribe']['mercureUrl'];
        $this->assertStringStartsWith('http://graphql.example/.well-known/mercure?', $url);
        parse_str(parse_url($url, \PHP_URL_QUERY), $query);
        $bus = $container->get('messenger.default_bus');
        $this->assertInstanceOf(MessageBusInterface::class, $bus);
        $manager = $container->get('doctrine')->getManager();
        $resource = $manager->find(SubscriptionAsyncResource::class, 1);
        $this->assertNotNull($resource);

        foreach (['create', 'update', 'delete'] as $event) {
            $transport->reset();
            $publications = [];
            $id = 1;
            if ('create' === $event) {
                $created = new SubscriptionAsyncResource();
                $created->id = $id = 2;
                $manager->persist($created);
            } elseif ('update' === $event) {
                $resource->name = 'Changed';
            } else {
                $manager->remove($resource);
            }
            $manager->flush();
            $this->assertEmpty($publications, 'Publication must wait for the queued messages.');
            $this->assertCount(2, $transport->getSent());
            foreach ($transport->get() as $envelope) {
                $bus->dispatch($envelope->with(new ReceivedStamp('async')));
                $transport->ack($envelope);
            }

            /** @var array<string, list<Update>> $delivered */
            $delivered = $publications;
            $this->assertArrayNotHasKey('default', $delivered);
            $this->assertCount(1, $delivered['rest']);
            $this->assertCount(1, $delivered['graphql']);
            $this->assertFalse($delivered['rest'][0]->isPrivate());
            $this->assertSame(['http://localhost/subscription_async_resources/'.$id], $delivered['rest'][0]->getTopics());
            $update = $delivered['graphql'][0];
            $this->assertTrue($update->isPrivate());
            $this->assertSame([$query['topic']], $update->getTopics());
            $data = json_decode($update->getData(), true, flags: \JSON_THROW_ON_ERROR);
            $this->assertSame('delete' === $event
                ? ['type' => 'delete', 'payload' => ['id' => '/subscription_async_resources/1', 'iri' => 'http://localhost/subscription_async_resources/1', 'type' => 'SubscriptionAsyncResource']]
                : ['subscriptionAsyncResource' => ['id' => '/subscription_async_resources/'.$id, 'name' => 'create' === $event ? 'Initial' : 'Changed', '_id' => $id]], $data);
        }
    }
}
