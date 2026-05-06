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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\CollectionNoPrefix;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\CollectionPagedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\PaginationCapped;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class HydraCollectionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [CollectionPagedResource::class, CollectionNoPrefix::class, PaginationCapped::class];
    }

    public function testFirstPageHasFirstThreeItemsAndNextLink(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/contexts/JsonLdCollectionPaged', $body['@context']);
        $this->assertSame('/jsonld_collection_paged', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertSame(30, $body['hydra:totalItems']);
        $this->assertCount(3, $body['hydra:member']);
        $this->assertSame([1, 2, 3], array_column($body['hydra:member'], 'id'));
        $this->assertSame('/jsonld_collection_paged?page=1', $body['hydra:view']['@id']);
        $this->assertSame('hydra:PartialCollectionView', $body['hydra:view']['@type']);
        $this->assertSame('/jsonld_collection_paged?page=1', $body['hydra:view']['hydra:first']);
        $this->assertSame('/jsonld_collection_paged?page=10', $body['hydra:view']['hydra:last']);
        $this->assertSame('/jsonld_collection_paged?page=2', $body['hydra:view']['hydra:next']);
    }

    public function testMiddlePageHasPreviousAndNext(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged?page=7', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(3, $body['hydra:member']);
        $this->assertSame([19, 20, 21], array_column($body['hydra:member'], 'id'));
        $this->assertSame('/jsonld_collection_paged?page=6', $body['hydra:view']['hydra:previous']);
        $this->assertSame('/jsonld_collection_paged?page=8', $body['hydra:view']['hydra:next']);
    }

    public function testLastPageOmitsNext(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged?page=10', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame([28, 29, 30], array_column($body['hydra:member'], 'id'));
        $this->assertSame('/jsonld_collection_paged?page=9', $body['hydra:view']['hydra:previous']);
        $this->assertArrayNotHasKey('hydra:next', $body['hydra:view']);
    }

    public function testPaginationDisabledExposesAllItems(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged?pagination=0', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame(30, $body['hydra:totalItems']);
        $this->assertCount(30, $body['hydra:member']);
    }

    public function testItemsPerPageOverridesDefault(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged?page=2&itemsPerPage=10', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(10, $body['hydra:member']);
        $this->assertSame('/jsonld_collection_paged?itemsPerPage=10&page=1', $body['hydra:view']['hydra:first']);
        $this->assertSame('/jsonld_collection_paged?itemsPerPage=10&page=3', $body['hydra:view']['hydra:last']);
        $this->assertSame('/jsonld_collection_paged?itemsPerPage=10&page=1', $body['hydra:view']['hydra:previous']);
        $this->assertSame('/jsonld_collection_paged?itemsPerPage=10&page=3', $body['hydra:view']['hydra:next']);
    }

    public function testItemsPerPageZeroReturnsEmptyMember(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged?itemsPerPage=0', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(0, $body['hydra:member']);
    }

    public function testFilterExactMatchByIdPreservesViewQueryString(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged?id=8', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(1, $body['hydra:member']);
        $this->assertSame(8, $body['hydra:member'][0]['id']);
        $this->assertSame('/jsonld_collection_paged?id=8', $body['hydra:view']['@id']);
    }

    public function testFilterUrlEncodedValuePreservedInView(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged?id=%2Fdummies%2F8', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/jsonld_collection_paged?id=%2Fdummies%2F8', $body['hydra:view']['@id']);
    }

    public function testFilterByEncodedNameValuePreservedInView(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged?name=Dummy%20%238', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(1, $body['hydra:member']);
        $this->assertSame(8, $body['hydra:member'][0]['id']);
        $this->assertSame('/jsonld_collection_paged?name=Dummy%20%238', $body['hydra:view']['@id']);
    }

    public function testEmptyResultExposesEmptyMember(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged?id=999', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertSame(0, $body['hydra:totalItems']);
        $this->assertCount(0, $body['hydra:member']);
    }

    public function testPartialPaginationDropsFirstAndLast(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_paged?page=7&partial=1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('hydra:PartialCollectionView', $body['hydra:view']['@type']);
        $this->assertArrayNotHasKey('hydra:first', $body['hydra:view']);
        $this->assertArrayNotHasKey('hydra:last', $body['hydra:view']);
        $this->assertArrayHasKey('hydra:next', $body['hydra:view']);
        $this->assertArrayHasKey('hydra:previous', $body['hydra:view']);
    }

    public function testCollectionWithoutHydraPrefix(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_collection_no_prefix', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertArrayHasKey('totalItems', $body);
        $this->assertArrayHasKey('member', $body);
        $this->assertArrayNotHasKey('hydra:totalItems', $body);
        $this->assertArrayNotHasKey('hydra:member', $body);
    }

    public function testItemsPerPageZeroAndPageGreaterThanOneReturns400(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_pagination_capped?itemsPerPage=0&page=2', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(400);
        $body = $response->toArray(false);
        $this->assertSame('Page should not be greater than 1 if limit is equal to 0', $body['detail']);
    }

    public function testPaginationMaximumItemsPerPageCapsClientItemsPerPage(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_pagination_capped?itemsPerPage=40', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(30, $body['hydra:member']);
    }
}
