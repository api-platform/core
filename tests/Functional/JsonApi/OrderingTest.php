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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\OrderingDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class OrderingTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [OrderingDummy::class];
    }

    public function testSortAscendingOnSingleField(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_ordering_dummies?sort=id', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $ids = array_map(static fn (array $d): int => (int) $d['attributes']['_id'], $body['data']);
        $this->assertSame([1, 2, 3], \array_slice($ids, 0, 3));
    }

    public function testSortDescendingOnSingleField(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_ordering_dummies?sort=-id', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $ids = array_map(static fn (array $d): int => (int) $d['attributes']['_id'], $body['data']);
        $this->assertSame([30, 29, 28], \array_slice($ids, 0, 3));
    }

    public function testSortMultipleFields(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_ordering_dummies?sort=description,-id', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $ids = array_map(static fn (array $d): int => (int) $d['attributes']['_id'], $body['data']);
        $this->assertSame([30, 28, 26], \array_slice($ids, 0, 3));
    }
}
