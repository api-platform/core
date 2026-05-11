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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7966\SortFilterParameterDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Object-form filter (FilterInterface instance) combined with a bracketed parameter
 * key crashed the GraphQL schema build because `filterLocator->has()` was called
 * with the instance rather than a string service id.
 *
 * @see https://github.com/api-platform/core/issues/7966
 */
final class Issue7966Test extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SortFilterParameterDummy::class];
    }

    public function testSchemaBuildsWithObjectFormFilterAndBracketedKey(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => '{ __type(name: "SortFilterParameterDummy") { name } }',
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame('SortFilterParameterDummy', $json['data']['__type']['name']);
    }
}
