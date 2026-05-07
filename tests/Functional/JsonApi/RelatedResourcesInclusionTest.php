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

namespace ApiPlatform\Tests\Functional\JsonApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\IncludeGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\IncludeProperty;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class RelatedResourcesInclusionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [IncludeProperty::class, IncludeGroup::class];
    }

    public function testIncludeManyToOneRelation(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_include_properties/1?include=group', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'data' => [
                'id' => '/jsonapi_include_properties/1',
                'type' => 'JsonApiIncludeProperty',
                'relationships' => [
                    'group' => [
                        'data' => ['type' => 'JsonApiIncludeGroup', 'id' => '/jsonapi_include_groups/1'],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => '/jsonapi_include_groups/1',
                    'type' => 'JsonApiIncludeGroup',
                    'attributes' => ['_id' => 1, 'foo' => 'Foo #1'],
                ],
            ],
        ]);
    }

    public function testIncludeNonExistingRelation(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_include_properties/1?include=foo', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/jsonapi_include_properties/1', $body['data']['id']);
        $this->assertArrayNotHasKey('included', $body);
    }

    public function testIncludeManyToMany(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_include_properties/1?include=groups', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(3, $body['data']['relationships']['groups']['data']);
        $this->assertCount(3, $body['included']);
        $includedIds = array_column($body['included'], 'id');
        $this->assertContains('/jsonapi_include_groups/2', $includedIds);
        $this->assertContains('/jsonapi_include_groups/3', $includedIds);
        $this->assertContains('/jsonapi_include_groups/4', $includedIds);
    }

    public function testIncludeManyToManyAndManyToOne(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_include_properties/1?include=groups,group', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $includedIds = array_column($body['included'], 'id');
        // 1 from group + 3 from groups = 4 included resources
        $this->assertCount(4, $body['included']);
        $this->assertContains('/jsonapi_include_groups/1', $includedIds);
        $this->assertContains('/jsonapi_include_groups/2', $includedIds);
    }

    public function testIncludeWithSparseFields(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/jsonapi_include_properties/1?include=group&fields[group]=id,foo',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/jsonapi_include_properties/1', $body['data']['id']);
        $this->assertSame('/jsonapi_include_groups/1', $body['included'][0]['id']);
        $this->assertSame('Foo #1', $body['included'][0]['attributes']['foo']);
        $this->assertArrayNotHasKey('bar', $body['included'][0]['attributes']);
        $this->assertArrayNotHasKey('baz', $body['included'][0]['attributes']);
    }
}
