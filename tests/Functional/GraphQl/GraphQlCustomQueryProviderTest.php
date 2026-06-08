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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GraphQlCustomQueryProvider\Account;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GraphQlCustomQueryProvider\AccountWithGet;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * A resource declaring a GraphQl Query with a custom `provider:` must invoke that
 * provider on the root item query — independently of any HTTP item operation.
 *
 * @see https://github.com/api-platform/core/issues/5805
 */
final class GraphQlCustomQueryProviderTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Account::class, AccountWithGet::class];
    }

    public function testGraphQlQueryUsesCustomProviderWhenNoHttpGetIsDeclared(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
{
  account(id: "/accounts/1") {
    id
    credentials
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame('/accounts/1', $json['data']['account']['id']);
        $this->assertSame([['key' => 'static-value']], $json['data']['account']['credentials']);
    }

    public function testGraphQlQueryUsesItsOwnProviderWhenHttpGetHasDifferentOne(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
{
  accountWithGet(id: "/account_with_gets/1") {
    id
    source
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame('/account_with_gets/1', $json['data']['accountWithGet']['id']);
        $this->assertSame('graphql-query', $json['data']['accountWithGet']['source']);
    }

    public function testHttpGetStillUsesItsOwnProvider(): void
    {
        $response = self::createClient()->request('GET', '/account_with_gets/1');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['source' => 'http-get']);
    }
}
