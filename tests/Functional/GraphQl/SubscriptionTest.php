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

use ApiPlatform\GraphQl\Test\GraphQlTestTrait;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyMercure as DummyMercureDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyMercure;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Mercure\TestHub;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SubscriptionTest extends ApiTestCase
{
    use GraphQlTestTrait;
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyMercure::class, RelatedDummy::class];
    }

    public function testIntrospectSubscriptionType(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              __type(name: "Subscription") {
                fields {
                  name
                  description
                  type { name kind }
                  args {
                    name
                    type { name kind ofType { name kind } }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $fields = $response->toArray()['data']['__type']['fields'];
        $this->assertNotEmpty($fields);

        foreach ($fields as $field) {
            $this->assertMatchesRegularExpression('/^update[A-Za-z0-9_]+Subscribe$/', $field['name']);
            $this->assertMatchesRegularExpression('/^Subscribes to the update event of a [A-Za-z0-9_]+\.$/', $field['description']);
            $this->assertMatchesRegularExpression('/^update[A-Za-z0-9_]+SubscriptionPayload$/', $field['type']['name']);
            $this->assertSame('OBJECT', $field['type']['kind']);

            $this->assertCount(1, $field['args']);
            $arg = $field['args'][0];
            $this->assertSame('input', $arg['name']);
            $this->assertSame('NON_NULL', $arg['type']['kind']);
            $this->assertMatchesRegularExpression('/^update[A-Za-z0-9_]+SubscriptionInput$/', $arg['type']['ofType']['name']);
            $this->assertSame('INPUT_OBJECT', $arg['type']['ofType']['kind']);
        }
    }

    public function testSubscribeToUpdatesProducesMercureUrl(): void
    {
        $this->recreateSchema($this->resources());
        $this->seedDummyMercure(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            subscription {
              updateDummyMercureSubscribe(input: {id: "/dummy_mercures/1", clientSubscriptionId: "myId"}) {
                dummyMercure {
                  id
                  name
                  relatedDummy {
                    name
                  }
                }
                mercureUrl
                clientSubscriptionId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['updateDummyMercureSubscribe'];
        $this->assertSame('/dummy_mercures/1', $data['dummyMercure']['id']);
        $this->assertSame('Dummy Mercure #1', $data['dummyMercure']['name']);
        $this->assertSame('myId', $data['clientSubscriptionId']);
        $this->assertMatchesRegularExpression(
            '@^https://demo\.mercure\.rocks\?topic=http://[^/]+/subscriptions/[a-f0-9]+$@',
            $data['mercureUrl'],
        );

        $response = $this->executeGraphQl(<<<'QUERY'
            subscription {
              updateDummyMercureSubscribe(input: {id: "/dummy_mercures/2"}) {
                dummyMercure { id }
                mercureUrl
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['updateDummyMercureSubscribe'];
        $this->assertSame('/dummy_mercures/2', $data['dummyMercure']['id']);
        $this->assertMatchesRegularExpression(
            '@^https://demo\.mercure\.rocks\?topic=http://[^/]+/subscriptions/[a-f0-9]+$@',
            $data['mercureUrl'],
        );
    }

    public function testReceiveMercureUpdatesAfterPut(): void
    {
        $this->recreateSchema($this->resources());
        $this->seedDummyMercure(2);

        $client = self::createClient();
        $client->getKernelBrowser()->disableReboot();

        // Subscribe to both dummies so the SubscriptionManager registers different payload shapes.
        $this->executeGraphQl(<<<'QUERY'
            subscription {
              updateDummyMercureSubscribe(input: {id: "/dummy_mercures/1", clientSubscriptionId: "myId"}) {
                dummyMercure { id name relatedDummy { name } }
                mercureUrl
              }
            }
            QUERY);
        $this->executeGraphQl(<<<'QUERY'
            subscription {
              updateDummyMercureSubscribe(input: {id: "/dummy_mercures/2"}) {
                dummyMercure { id }
                mercureUrl
              }
            }
            QUERY);

        $client->request('PUT', '/dummy_mercures/1', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Dummy Mercure #1 updated'],
        ]);
        $this->assertResponseIsSuccessful();

        $client->request('PUT', '/dummy_mercures/2', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Dummy Mercure #2 updated'],
        ]);
        $this->assertResponseIsSuccessful();

        /** @var TestHub $hub */
        $hub = static::getContainer()->get('mercure.hub.default.test_hub');
        $updates = $hub->getUpdates();

        $this->assertGreaterThanOrEqual(2, \count($updates));

        $this->assertMercureUpdatePresent($updates, '#^http://[^/]+/subscriptions/[a-f0-9]+$#', [
            'dummyMercure' => [
                'id' => 1,
                'name' => 'Dummy Mercure #1 updated',
                'relatedDummy' => ['name' => 'RelatedDummy #1'],
            ],
        ]);

        $this->assertMercureUpdatePresent($updates, '#^http://[^/]+/subscriptions/[a-f0-9]+$#', [
            'dummyMercure' => ['id' => 2],
        ]);
    }

    /**
     * @param list<\Symfony\Component\Mercure\Update> $updates
     * @param array<string, mixed> $expectedPayload
     */
    private function assertMercureUpdatePresent(array $updates, string $topicPattern, array $expectedPayload): void
    {
        $expectedJson = json_encode($expectedPayload, \JSON_THROW_ON_ERROR);

        foreach ($updates as $update) {
            $topicsMatch = false;
            foreach ($update->getTopics() as $topic) {
                if (preg_match($topicPattern, (string) $topic)) {
                    $topicsMatch = true;
                    break;
                }
            }
            if (!$topicsMatch) {
                continue;
            }

            if ($update->getData() === $expectedJson) {
                $this->assertTrue(true);

                return;
            }
        }

        $this->fail(\sprintf(
            'No Mercure update matched topic %s with payload %s. Captured: %s',
            $topicPattern,
            $expectedJson,
            json_encode(array_map(
                static fn ($u) => ['topics' => $u->getTopics(), 'data' => $u->getData()],
                $updates,
            ), \JSON_PRETTY_PRINT),
        ));
    }

    /**
     * @return list<class-string>
     */
    private function resources(): array
    {
        return [
            $this->isMongoDB() ? DummyMercureDocument::class : DummyMercure::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
        ];
    }

    private function seedDummyMercure(int $count): void
    {
        $manager = $this->getManager();
        $relatedClass = $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class;
        $dummyClass = $this->isMongoDB() ? DummyMercureDocument::class : DummyMercure::class;

        for ($i = 1; $i <= $count; ++$i) {
            $related = new $relatedClass();
            $related->setName('RelatedDummy #'.$i);

            $dummy = new $dummyClass();
            $dummy->name = "Dummy Mercure #$i";
            $dummy->description = 'Description';
            $dummy->relatedDummy = $related;

            $manager->persist($related);
            $manager->persist($dummy);
        }
        $manager->flush();
    }
}
