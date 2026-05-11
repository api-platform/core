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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\FilteringDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\FilteringProperty;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class FilteringTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [FilteringDummy::class, FilteringProperty::class];
    }

    public function testFilterMatchesPaginatesToThree(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_filtering_dummies?filter[name]=my', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(3, $body['data']);
    }

    public function testFilterNoMatch(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_filtering_dummies?filter[name]=foo', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(0, $body['data']);
    }

    public function testFilterAndPaginationCombined(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_filtering_dummies?filter[name]=foo&page[page]=2', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame(2, $body['meta']['currentPage']);
    }

    public function testSparseFieldsetWithFields(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/jsonapi_filtering_properties?fields[JsonApiFilteringProperty]=id,foo,bar',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(2, $body['data']);
        $this->assertSame('1', (string) $body['data'][0]['attributes']['_id']);
        $this->assertSame('Foo #1', $body['data'][0]['attributes']['foo']);
        $this->assertSame('Bar #1', $body['data'][0]['attributes']['bar']);
        $this->assertArrayNotHasKey('group', $body['data'][0]['attributes']);
    }

    public function testFilterDateAfter(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/jsonapi_filtering_dummies?filter[dummyDate][after]=2015-04-28',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(2, $body['data']);
    }
}
