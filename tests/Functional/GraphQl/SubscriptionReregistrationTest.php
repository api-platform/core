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
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

final class SubscriptionReregistrationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    use SubscriptionPublicationTrait;

    protected static ?bool $alwaysBootKernel = false;
    private ?TraceableAdapter $cache = null;

    public static function getResources(): array
    {
        return [PrivateSubscriptionResource::class];
    }

    protected function tearDown(): void
    {
        $this->cache?->clear();
        parent::tearDown();
    }

    #[TestWith(['Published'])]
    #[TestWith(['Initial'])]
    public function testReregistrationDoesNotSuppressTheNextCorrection(string $nextValue): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $this->cache = new TraceableAdapter(new FilesystemAdapter('subscription_reregistration_'.bin2hex(random_bytes(8))));
        self::getContainer()->set('api_platform.graphql.cache.subscription', $this->cache);
        $initial = $this->subscribe($client);
        $this->assertSame('Initial', $initial['privateSubscriptionResource']['name']);

        $resource = PrivateSubscriptionResource::provide(new Get(), ['id' => 1]);
        $resource->name = 'Published';
        $published = $this->publishSubscriptions($resource);
        $this->assertCount(1, $published);
        $this->assertSame('Published', $published[0][1]['privateSubscriptionResource']['name']);
        $this->assertSame([], $this->publishSubscriptions($resource));

        // The existing client has Published; a new enrollment reads Initial from the provider.
        $enrollment = $this->subscribe($client);
        $this->assertSame('Initial', $enrollment['privateSubscriptionResource']['name']);
        $this->assertSame($initial['mercureUrl'], $enrollment['mercureUrl']);

        $resource->name = $nextValue;
        $correction = $this->publishSubscriptions($resource);
        $this->assertCount(1, $correction);
        $this->assertSame($published[0][0], $correction[0][0]);
        $this->assertSame([
            'privateSubscriptionResource' => ['id' => '/private_subscription_resources/1', 'name' => $nextValue, '_id' => 1],
        ], $correction[0][1]);
        $this->assertSame([], $this->publishSubscriptions($resource));
    }

    private function subscribe(Client $client): array
    {
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => 'subscription { updatePrivateSubscriptionResourceSubscribe(input: {id: "/private_subscription_resources/1"}) { privateSubscriptionResource { id _id name } mercureUrl } }',
        ]]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));

        return $json['data']['updatePrivateSubscriptionResourceSubscribe'];
    }
}
