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

namespace ApiPlatform\Tests\Functional\Mercure;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyMercure;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5074\MercureWithTopics;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MercureWithTopicsAndGetOperation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Mercure\TestHub;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Mercure\Update;

final class MercureTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            DummyMercure::class,
            RelatedDummy::class,
            MercureWithTopics::class,
            MercureWithTopicsAndGetOperation::class,
        ];
    }

    public function testDiscoveryLinkOnMercureResource(): void
    {
        $this->recreateSchema([DummyMercure::class, RelatedDummy::class]);

        $response = self::createClient()->request('GET', '/dummy_mercures', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertContains(
            '<https://demo.mercure.rocks>; rel="mercure"',
            $response->getHeaders()['link'],
        );
    }

    public function testNoDiscoveryLinkOnNonMercureEndpoint(): void
    {
        $response = self::createClient()->request('GET', '/');

        $this->assertNotContains(
            '<https://demo.mercure.rocks/hub>; rel="mercure"',
            $response->getHeaders()['link'] ?? [],
        );
    }

    public function testPublishUpdateOnPostWithIriTopic(): void
    {
        $this->recreateSchema([MercureWithTopics::class]);
        $hub = $this->resetTestHub();

        self::createClient()->request('POST', '/issue5074/mercure_with_topics', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'Hello World!',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');

        $updates = $hub->getUpdates();
        $this->assertCount(1, $updates);
        /** @var Update $update */
        $update = $updates[0];
        $this->assertSame(['http://localhost/issue5074/mercure_with_topics/1'], array_values($update->getTopics()));
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '@context' => '/contexts/MercureWithTopics',
                '@id' => '/issue5074/mercure_with_topics/1',
                '@type' => 'MercureWithTopics',
                'id' => 1,
                'name' => 'Hello World!',
            ], \JSON_THROW_ON_ERROR),
            $update->getData(),
        );
    }

    public function testPublishUpdateWithExpressionLanguageTopics(): void
    {
        $this->recreateSchema([MercureWithTopicsAndGetOperation::class]);
        $hub = $this->resetTestHub();

        self::createClient()->request('POST', '/mercure_with_topics_and_get_operations', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['name' => 'Hello World!'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');

        $updates = $hub->getUpdates();
        $this->assertCount(1, $updates);
        /** @var Update $update */
        $update = $updates[0];
        $this->assertSame([
            'http://localhost/mercure_with_topics_and_get_operations/1',
            'http://localhost/custom_resource/mercure_with_topics_and_get_operations/1',
        ], array_values($update->getTopics()));
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '@context' => '/contexts/MercureWithTopicsAndGetOperation',
                '@id' => '/mercure_with_topics_and_get_operations/1',
                '@type' => 'MercureWithTopicsAndGetOperation',
                'id' => 1,
                'name' => 'Hello World!',
            ], \JSON_THROW_ON_ERROR),
            $update->getData(),
        );
    }

    private function resetTestHub(): TestHub
    {
        $hub = static::getContainer()->get('mercure.hub.default.test_hub');
        \assert($hub instanceof TestHub);

        $reflection = new \ReflectionProperty(TestHub::class, 'updates');
        $reflection->setValue($hub, []);

        return $hub;
    }
}
