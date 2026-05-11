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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\PaginationDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class PaginationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [PaginationDummy::class];
    }

    public function testFirstPageDefaults(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_pagination_dummies', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(3, $body['data']);
        $this->assertSame(10, $body['meta']['totalItems']);
        $this->assertSame(3, $body['meta']['itemsPerPage']);
        $this->assertSame(1, $body['meta']['currentPage']);
    }

    public function testFourthPage(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_pagination_dummies?page[page]=4', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(1, $body['data']);
        $this->assertSame(4, $body['meta']['currentPage']);
    }

    public function testCustomItemsPerPage(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_pagination_dummies?page[itemsPerPage]=15', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(10, $body['data']);
        $this->assertSame(10, $body['meta']['totalItems']);
        $this->assertSame(15, $body['meta']['itemsPerPage']);
        $this->assertSame(1, $body['meta']['currentPage']);
    }

    public function testInvalidPageNumberZero(): void
    {
        self::createClient()->request('GET', '/jsonapi_pagination_dummies?page[page]=0', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testTooLargePageNumber(): void
    {
        self::createClient()->request('GET', '/jsonapi_pagination_dummies?page[page]=9223372036854775807', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }
}
