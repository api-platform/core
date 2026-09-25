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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PaginationLink\PaginationLinkResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class PaginationLinkHeaderTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    private const JSON_LD = ['Accept' => 'application/ld+json'];

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [PaginationLinkResource::class];
    }

    public function testAMiddlePageAdvertisesEveryRelation(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links?page=2', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links?page=2>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links?page=1>; rel="first"', $links);
        $this->assertStringContainsString('</pagination_links?page=1>; rel="prev"', $links);
        $this->assertStringContainsString('</pagination_links?page=3>; rel="next"', $links);
        $this->assertStringContainsString('</pagination_links?page=3>; rel="last"', $links);
    }

    public function testAHeadRequestAdvertisesTheSameRelationsWithoutABody(): void
    {
        $response = self::createClient()->request('HEAD', '/pagination_links?page=2', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links?page=2>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links?page=1>; rel="first"', $links);
        $this->assertStringContainsString('</pagination_links?page=1>; rel="prev"', $links);
        $this->assertStringContainsString('</pagination_links?page=3>; rel="next"', $links);
        $this->assertStringContainsString('</pagination_links?page=3>; rel="last"', $links);
        $this->assertSame('', $response->getContent(false));
    }

    public function testTheFirstPageHasNoPreviousRelation(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links?page=1', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links?page=1>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links?page=1>; rel="first"', $links);
        $this->assertStringContainsString('</pagination_links?page=2>; rel="next"', $links);
        $this->assertStringContainsString('</pagination_links?page=3>; rel="last"', $links);
        $this->assertStringNotContainsString('rel="prev"', $links);
    }

    public function testTheLastPageHasNoNextRelation(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links?page=3', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links?page=3>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links?page=1>; rel="first"', $links);
        $this->assertStringContainsString('</pagination_links?page=2>; rel="prev"', $links);
        $this->assertStringContainsString('</pagination_links?page=3>; rel="last"', $links);
        $this->assertStringNotContainsString('rel="next"', $links);
    }

    public function testASinglePageCollectionStillAdvertisesFirstAndLast(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links_single', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links_single?page=1>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links_single?page=1>; rel="first"', $links);
        $this->assertStringContainsString('</pagination_links_single?page=1>; rel="last"', $links);
        $this->assertStringNotContainsString('rel="prev"', $links);
        $this->assertStringNotContainsString('rel="next"', $links);
    }

    public function testACollectionWithoutTheFlagAdvertisesNoPaginationRelation(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links_disabled?page=2', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringNotContainsString('rel="self"', $links);
        $this->assertStringNotContainsString('rel="first"', $links);
        $this->assertStringNotContainsString('rel="prev"', $links);
        $this->assertStringNotContainsString('rel="next"', $links);
        $this->assertStringNotContainsString('rel="last"', $links);
    }

    public function testAClientChosenPageSizeIsCarriedByEveryRelation(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links_client_size?itemsPerPage=5&page=2', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links_client_size?itemsPerPage=5&page=2>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links_client_size?itemsPerPage=5&page=1>; rel="first"', $links);
        $this->assertStringContainsString('</pagination_links_client_size?itemsPerPage=5&page=1>; rel="prev"', $links);
        $this->assertStringContainsString('</pagination_links_client_size?itemsPerPage=5&page=3>; rel="next"', $links);
        $this->assertStringContainsString('</pagination_links_client_size?itemsPerPage=5&page=5>; rel="last"', $links);
    }

    public function testAPageSizeTheOperationRefusesIsEchoedWithoutBeingApplied(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links_fixed_size?itemsPerPage=5&page=2', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links_fixed_size?itemsPerPage=5&page=2>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links_fixed_size?itemsPerPage=5&page=1>; rel="first"', $links);
        $this->assertStringContainsString('</pagination_links_fixed_size?itemsPerPage=5&page=1>; rel="prev"', $links);
        $this->assertStringContainsString('</pagination_links_fixed_size?itemsPerPage=5&page=3>; rel="next"', $links);
        $this->assertStringContainsString('</pagination_links_fixed_size?itemsPerPage=5&page=3>; rel="last"', $links);
    }

    public function testTheOtherFiltersArePreservedInEveryRelation(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links?name=Item%20%2312&page=1', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links?name=Item%20%2312&page=1>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links?name=Item%20%2312&page=1>; rel="first"', $links);
        $this->assertStringContainsString('</pagination_links?name=Item%20%2312&page=1>; rel="last"', $links);
        $this->assertStringNotContainsString('rel="prev"', $links);
        $this->assertStringNotContainsString('rel="next"', $links);
    }

    public function testAPartialPaginatorAdvertisesNeitherFirstNorLast(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links_partial?page=2', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links_partial?page=2>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links_partial?page=1>; rel="prev"', $links);
        $this->assertStringContainsString('</pagination_links_partial?page=3>; rel="next"', $links);
        $this->assertStringNotContainsString('rel="first"', $links);
        $this->assertStringNotContainsString('rel="last"', $links);
    }

    public function testAnIncompletePartialPageHasNoNextRelation(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links_partial?page=3', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links_partial?page=3>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links_partial?page=2>; rel="prev"', $links);
        $this->assertStringNotContainsString('rel="next"', $links);
        $this->assertStringNotContainsString('rel="first"', $links);
        $this->assertStringNotContainsString('rel="last"', $links);
    }

    public function testTheRelationsAreFlatUnderJsonApi(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links?page=2', ['headers' => ['Accept' => 'application/vnd.api+json']]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('</pagination_links?page=2>; rel="self"', $links);
        $this->assertStringContainsString('</pagination_links?page=1>; rel="first"', $links);
        $this->assertStringContainsString('</pagination_links?page=1>; rel="prev"', $links);
        $this->assertStringContainsString('</pagination_links?page=3>; rel="next"', $links);
        $this->assertStringContainsString('</pagination_links?page=3>; rel="last"', $links);
    }

    public function testAnAbsoluteUrlStrategyAdvertisesAbsoluteRelations(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links_absolute?page=2', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $links = self::linkHeader($response);
        $this->assertStringContainsString('<http://localhost/pagination_links_absolute?page=2>; rel="self"', $links);
        $this->assertStringContainsString('<http://localhost/pagination_links_absolute?page=1>; rel="first"', $links);
        $this->assertStringContainsString('<http://localhost/pagination_links_absolute?page=1>; rel="prev"', $links);
        $this->assertStringContainsString('<http://localhost/pagination_links_absolute?page=3>; rel="next"', $links);
        $this->assertStringContainsString('<http://localhost/pagination_links_absolute?page=3>; rel="last"', $links);
    }

    private static function linkHeader(ResponseInterface $response): string
    {
        return implode(',', $response->getHeaders(false)['link'] ?? []);
    }
}
