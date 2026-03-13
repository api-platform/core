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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue3975\ActionSimulation;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class Issue3975Test extends ApiTestCase
{
    use SetupClassResourcesTrait;
    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ActionSimulation::class];
    }

    public function testGraphQlOnlyQueryWithProvider(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
{
  getActionSimulation(actionId: "abc123", structureEntityIds: ["s1", "s2"]) {
    simulation
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json);
        $this->assertEquals('test', $json['data']['getActionSimulation']['simulation']);
    }

    public function testGraphQlOnlyQueryWithId(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
{
  getActionSimulation(actionId: "abc123") {
    id
    simulation
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json);
        $this->assertNotNull($json['data']['getActionSimulation']['id']);
        $this->assertEquals('test', $json['data']['getActionSimulation']['simulation']);
    }
}
