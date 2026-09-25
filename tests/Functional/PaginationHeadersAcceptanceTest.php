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
use Symfony\Component\WebLink\HttpHeaderParser;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class PaginationHeadersAcceptanceTest extends ApiTestCase
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

    public function testHeadTellsThePageCountWithoutAnyBodyButNotThePageSize(): void
    {
        $response = self::createClient()->request('HEAD', '/pagination_links?page=2', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('', $response->getContent(false));
        $state = self::paginationState($response);
        $this->assertSame(2, $state['current_page']);
        $this->assertSame(3, $state['page_count']);
        $this->assertNull($state['items_per_page']);
        $this->assertNull($state['total_min']);
        $this->assertNull($state['total_max']);
    }

    public function testGetAdvertisesTheStateInTheLinkHeader(): void
    {
        $response = self::createClient()->request('GET', '/pagination_links?page=3', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $state = self::paginationState($response);
        $this->assertSame(3, $state['current_page']);
        $this->assertSame(3, $state['page_count']);
        $this->assertArrayNotHasKey('next', $state['relations']);
        $this->assertArrayHasKey('prev', $state['relations']);
    }

    public function testASinglePageCollectionStillTellsItsPageCountOnHead(): void
    {
        $response = self::createClient()->request('HEAD', '/pagination_links_single', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('', $response->getContent(false));
        $state = self::paginationState($response);
        $this->assertSame(1, $state['current_page']);
        $this->assertSame(1, $state['page_count']);
        $this->assertNull($state['items_per_page']);
        $this->assertNull($state['total_min']);
        $this->assertNull($state['total_max']);
        $this->assertArrayNotHasKey('prev', $state['relations']);
        $this->assertArrayNotHasKey('next', $state['relations']);
    }

    public function testAPartialPaginatorAdmitsItDoesNotKnowTheTotal(): void
    {
        $response = self::createClient()->request('HEAD', '/pagination_links_partial?page=2', ['headers' => self::JSON_LD]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('', $response->getContent(false));
        $state = self::paginationState($response);
        $this->assertArrayNotHasKey('last', $state['relations']);
        $this->assertNull($state['page_count']);
        $this->assertNull($state['total_min']);
        $this->assertNull($state['total_max']);
        $this->assertSame(2, $state['current_page']);
        $this->assertNull($state['items_per_page']);
        $this->assertArrayHasKey('next', $state['relations']);
    }

    public function testTheStateIsIdenticalBetweenHeadAndGet(): void
    {
        $client = self::createClient();

        $head = self::paginationState($client->request('HEAD', '/pagination_links?page=2', ['headers' => self::JSON_LD]));
        $get = self::paginationState($client->request('GET', '/pagination_links?page=2', ['headers' => self::JSON_LD]));

        $this->assertSame($get, $head);
    }

    public function testTheLinksCarryOnlyWhatTheClientSent(): void
    {
        $client = self::createClient();

        $withoutAPageSize = self::paginationState($client->request('GET', '/pagination_links?page=2', ['headers' => self::JSON_LD]));
        $withAPageSize = self::paginationState($client->request('GET', '/pagination_links_client_size?itemsPerPage=5&page=2', ['headers' => self::JSON_LD]));

        $this->assertSame([], self::pageSizes($withoutAPageSize['relations']));
        $this->assertSame(['self' => '5', 'first' => '5', 'prev' => '5', 'next' => '5', 'last' => '5'], self::pageSizes($withAPageSize['relations']));
    }

    /**
     * @return array{current_page: ?int, items_per_page: ?int, page_count: ?int, total_min: ?int, total_max: ?int, relations: array<string, string>}
     */
    private static function paginationState(ResponseInterface $response): array
    {
        $headers = $response->getHeaders(false);

        $relations = [];
        foreach ((new HttpHeaderParser())->parse($headers['link'] ?? [])->getLinks() as $link) {
            foreach ($link->getRels() as $rel) {
                $relations[$rel] = $link->getHref();
            }
        }

        $self = self::queryParameters($relations['self'] ?? null);
        $last = self::queryParameters($relations['last'] ?? null);

        $itemsPerPage = isset($self['itemsPerPage']) ? (int) $self['itemsPerPage'] : null;
        $pageCount = isset($last['page']) ? (int) $last['page'] : null;

        return [
            'current_page' => isset($self['page']) ? (int) $self['page'] : null,
            'items_per_page' => $itemsPerPage,
            'page_count' => $pageCount,
            'total_min' => null === $pageCount || null === $itemsPerPage ? null : ($pageCount - 1) * $itemsPerPage + 1,
            'total_max' => null === $pageCount || null === $itemsPerPage ? null : $pageCount * $itemsPerPage,
            'relations' => $relations,
        ];
    }

    /**
     * @param array<string, string> $relations
     *
     * @return array<string, string>
     */
    private static function pageSizes(array $relations): array
    {
        return array_filter(array_map(static fn (string $href): ?string => self::queryParameters($href)['itemsPerPage'] ?? null, $relations), \is_string(...));
    }

    /**
     * @return array<string, string>
     */
    private static function queryParameters(?string $href): array
    {
        if (null === $href) {
            return [];
        }

        parse_str(parse_url($href, \PHP_URL_QUERY) ?: '', $query);

        return array_filter($query, \is_string(...));
    }
}
