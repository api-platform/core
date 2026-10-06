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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\IncompletePrivateSubscriptionResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

final class IncompletePrivateSubscriptionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [IncompletePrivateSubscriptionResource::class];
    }

    #[DataProvider('invalidOperations')]
    public function testIncompletePrivateScopeDoesNotReturnATopicOrCreateACacheEntry(string $name): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $cache = new ArrayAdapter();
        self::getContainer()->set('api_platform.graphql.cache.subscription', new TraceableAdapter($cache));
        $field = $name.'IncompletePrivateSubscriptionResourceSubscribe';
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('subscription { %s(input: {id: "/incomplete_private_subscription_resources/1"}) { mercureUrl } }', $field),
        ]]);
        $json = $response->toArray(false);
        $this->assertArrayHasKey('errors', $json);
        $this->assertEmpty($json['data'][$field]['mercureUrl'] ?? null);
        $this->assertSame([], $cache->getValues());
    }

    public static function invalidOperations(): iterable
    {
        yield 'missing private field' => ['missing'];
        yield 'partially available private fields' => ['partial'];
        yield 'missing enrollment object' => ['noRead'];
    }

    public function testExplicitNullPrivateFieldStillRegisters(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $cache = new ArrayAdapter();
        self::getContainer()->set('api_platform.graphql.cache.subscription', new TraceableAdapter($cache));
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => 'subscription { nullableIncompletePrivateSubscriptionResourceSubscribe(input: {id: "/incomplete_private_subscription_resources/1"}) { mercureUrl } }',
        ]]);
        $json = $response->toArray(false);
        $this->assertResponseIsSuccessful();
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));
        $this->assertNotEmpty($json['data']['nullableIncompletePrivateSubscriptionResourceSubscribe']['mercureUrl']);
        $this->assertCount(1, $cache->getValues());
    }
}
