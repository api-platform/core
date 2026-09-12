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

namespace ApiPlatform\Tests\Functional\Hal;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\CollectionPagedResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CollectionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [CollectionPagedResource::class];
    }

    public function testFirstPageHasFirstThreeItemsAndNextLink(): void
    {
        $response = self::createClient()->request('GET', '/hal_collection_paged', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/hal+json; charset=utf-8');
        $body = $response->toArray();

        $this->assertSame('/hal_collection_paged?page=1', $body['_links']['self']['href']);
        $this->assertSame('/hal_collection_paged?page=1', $body['_links']['first']['href']);
        $this->assertSame('/hal_collection_paged?page=4', $body['_links']['last']['href']);
        $this->assertSame('/hal_collection_paged?page=2', $body['_links']['next']['href']);
        $this->assertCount(3, $body['_links']['item']);
        $this->assertSame(10, $body['totalItems']);
        $this->assertSame(3, $body['itemsPerPage']);
        $this->assertSame([1, 2, 3], array_column($body['_embedded']['item'], 'id'));
    }

    public function testMiddlePageHasPrevAndNext(): void
    {
        $response = self::createClient()->request('GET', '/hal_collection_paged?page=3', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/hal_collection_paged?page=3', $body['_links']['self']['href']);
        $this->assertSame('/hal_collection_paged?page=2', $body['_links']['prev']['href']);
        $this->assertSame('/hal_collection_paged?page=4', $body['_links']['next']['href']);
        $this->assertSame([7, 8, 9], array_column($body['_embedded']['item'], 'id'));
    }

    public function testLastPageOmitsNext(): void
    {
        $response = self::createClient()->request('GET', '/hal_collection_paged?page=4', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/hal_collection_paged?page=4', $body['_links']['self']['href']);
        $this->assertSame('/hal_collection_paged?page=3', $body['_links']['prev']['href']);
        $this->assertArrayNotHasKey('next', $body['_links']);
        $this->assertSame([10], array_column($body['_embedded']['item'], 'id'));
    }

    public function testPartialPaginationDropsFirstAndLast(): void
    {
        $response = self::createClient()->request('GET', '/hal_collection_paged?page=2&partial=1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertArrayNotHasKey('first', $body['_links']);
        $this->assertArrayNotHasKey('last', $body['_links']);
        $this->assertArrayHasKey('prev', $body['_links']);
        $this->assertArrayHasKey('next', $body['_links']);
        $this->assertArrayNotHasKey('totalItems', $body);
        $this->assertSame(3, $body['itemsPerPage']);
        $this->assertSame([4, 5, 6], array_column($body['_embedded']['item'], 'id'));
    }

    public function testPaginationDisabledExposesAllItems(): void
    {
        $response = self::createClient()->request('GET', '/hal_collection_paged?pagination=0', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/hal_collection_paged?pagination=0', $body['_links']['self']['href']);
        $this->assertCount(10, $body['_links']['item']);
        $this->assertCount(10, $body['_embedded']['item']);
        $this->assertSame(10, $body['totalItems']);
    }

    public function testItemsPerPageOverridesDefault(): void
    {
        $response = self::createClient()->request('GET', '/hal_collection_paged?page=2&itemsPerPage=1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/hal_collection_paged?itemsPerPage=1&page=2', $body['_links']['self']['href']);
        $this->assertSame('/hal_collection_paged?itemsPerPage=1&page=1', $body['_links']['first']['href']);
        $this->assertSame('/hal_collection_paged?itemsPerPage=1&page=10', $body['_links']['last']['href']);
        $this->assertSame(1, $body['itemsPerPage']);
        $this->assertSame([2], array_column($body['_embedded']['item'], 'id'));
    }

    public function testFilterByEncodedIriPreservedInLinks(): void
    {
        $response = self::createClient()->request('GET', '/hal_collection_paged?id=%2fdummies%2f8', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/hal_collection_paged?id=%2Fdummies%2F8', $body['_links']['self']['href']);
        $this->assertSame(1, $body['totalItems']);
        $this->assertSame([8], array_column($body['_embedded']['item'], 'id'));
    }

    public function testFilterByEncodedNamePreservedInLinks(): void
    {
        $response = self::createClient()->request('GET', '/hal_collection_paged?name=Dummy%20%238', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/hal_collection_paged?name=Dummy%20%238', $body['_links']['self']['href']);
        $this->assertSame(1, $body['totalItems']);
        $this->assertSame([8], array_column($body['_embedded']['item'], 'id'));
    }

    public function testItemsPerPageZeroReturnsEmptyEmbeddedItems(): void
    {
        $response = self::createClient()->request('GET', '/hal_collection_paged?itemsPerPage=0', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/hal_collection_paged?itemsPerPage=0', $body['_links']['self']['href']);
        $this->assertSame(10, $body['totalItems']);
        $this->assertSame(0, $body['itemsPerPage']);
        $this->assertArrayNotHasKey('item', $body['_links']);
    }

    public function testEmptyCollectionExposesNoItems(): void
    {
        $response = self::createClient()->request('GET', '/hal_collection_paged?id=999', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame(0, $body['totalItems']);
        $this->assertSame(3, $body['itemsPerPage']);
        $this->assertArrayNotHasKey('item', $body['_links']);
    }
}
