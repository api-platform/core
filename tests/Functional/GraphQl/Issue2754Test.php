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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue2754\Sum;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * A custom GraphQL mutation declaring an explicit output DTO must expose the
 * output DTO's fields on its payload type, not the resource's fields.
 *
 * @see https://github.com/api-platform/core/issues/2754
 */
final class Issue2754Test extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Sum::class];
    }

    public function testCustomMutationHonorsOutputClassFields(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
mutation {
  sumSum(input: {operandA: 2, operandB: 3}) {
    sum {
      sum
    }
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame(5, $json['data']['sumSum']['sum']['sum']);
    }
}
