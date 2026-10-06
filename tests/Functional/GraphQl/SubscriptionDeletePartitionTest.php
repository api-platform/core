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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DeletePartitionResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

final class SubscriptionDeletePartitionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;
    private ?TraceableAdapter $cache = null;
    private bool $schemaCreated = false;

    public static function getResources(): array
    {
        return [DeletePartitionResource::class];
    }

    protected function tearDown(): void
    {
        if ($this->schemaCreated) {
            $manager = self::getContainer()->get('doctrine')->getManager();
            $this->assertInstanceOf(EntityManagerInterface::class, $manager);
            (new SchemaTool($manager))->dropSchema([$manager->getClassMetadata(DeletePartitionResource::class)]);
        }
        $this->cache?->clear();
        parent::tearDown();
    }

    public function testDeleteUsesEachGraphQlOperationsPrivateFields(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $container = self::getContainer();
        $this->cache = new TraceableAdapter(new FilesystemAdapter('delete_partition_'.bin2hex(random_bytes(8))));
        $container->set('api_platform.graphql.cache.subscription', $this->cache);
        $updates = [];
        $hub = new MockHub('http://example.com/.well-known/mercure', new StaticTokenProvider('test'), static function (Update $update) use (&$updates): string {
            $updates[] = $update;

            return 'id';
        });
        $container->set(HubRegistry::class, new HubRegistry($hub));
        $manager = $container->get('doctrine')->getManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $manager);
        (new SchemaTool($manager))->createSchema([$manager->getClassMetadata(DeletePartitionResource::class)]);
        $this->schemaCreated = true;
        foreach ([1, 2] as $id) {
            $resource = new DeletePartitionResource();
            $resource->id = $id;
            $manager->persist($resource);
        }
        $manager->flush();

        $topics = [];
        foreach (['byTenant', 'shared', 'byChat', 'byChatReverse', 'unpartitioned'] as $name) {
            $topics[$name] = $this->subscribe($client, $name);
        }
        $this->assertCount(5, array_unique($topics));
        $updates = [];
        $manager = self::getContainer()->get('doctrine')->getManager();
        $resource = $manager->find(DeletePartitionResource::class, 1);
        $this->assertNotNull($resource);
        $manager->remove($resource);
        $manager->flush();

        $this->assertPublishedTopicsAndPayloads($updates, array_values($topics), 1);

        // Collection registrations must survive deletion of the enrollment item.
        $updates = [];
        $resource = $manager->find(DeletePartitionResource::class, 2);
        $this->assertNotNull($resource);
        $manager->remove($resource);
        $manager->flush();
        $this->assertPublishedTopicsAndPayloads($updates, [$topics['byChat'], $topics['byChatReverse'], $topics['unpartitioned']], 2);
    }

    private function subscribe(Client $client, string $operation): string
    {
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('subscription { %sDeletePartitionResourceSubscribe(input: {id: "/delete_partition_resources/1"}) { deletePartitionResource { id } mercureUrl } }', $operation),
        ]]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));
        parse_str(parse_url($json['data'][$operation.'DeletePartitionResourceSubscribe']['mercureUrl'], \PHP_URL_QUERY), $query);

        return $query['topic'];
    }

    /** @param Update[] $updates */
    private function assertPublishedTopicsAndPayloads(array $updates, array $topics, int $id): void
    {
        $iri = '/delete_partition_resources/'.$id;
        $this->assertCount(\count($topics) + 1, $updates);
        $this->assertSame(['http://localhost'.$iri], $updates[0]->getTopics());
        $this->assertEqualsCanonicalizing($topics, array_map(static fn (Update $update) => $update->getTopics()[0], \array_slice($updates, 1)));
        foreach (\array_slice($updates, 1) as $update) {
            $this->assertSame([
                'type' => 'delete',
                'payload' => ['id' => $iri, 'iri' => 'http://localhost'.$iri, 'type' => 'DeletePartitionResource'],
            ], json_decode($update->getData(), true, flags: \JSON_THROW_ON_ERROR));
        }
    }
}
