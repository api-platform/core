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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\RangeRequest\RangeRequestResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class RangeRequestTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [RangeRequestResource::class];
    }

    public function testAFullResponseAdvertisesTheRangeUnitOnly(): void
    {
        $response = self::createClient()->request('GET', '/range_requests', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Accept-Ranges', 'items');
        $this->assertResponseNotHasHeader('Content-Range');
        $this->assertSame(range(1, 10), array_column($response->toArray()['hydra:member'], 'id'));
    }

    /**
     * @param list<int> $ids
     */
    #[DataProvider('provideSatisfiableRanges')]
    public function testARangeIsServedAsAPartialContent(string $range, string $contentRange, array $ids): void
    {
        $response = self::createClient()->request('GET', '/range_requests', ['headers' => ['Accept' => 'application/ld+json', 'Range' => $range]]);

        $this->assertResponseStatusCodeSame(206);
        $this->assertResponseHeaderSame('Accept-Ranges', 'items');
        $this->assertResponseHeaderSame('Content-Range', $contentRange);
        $body = $response->toArray();
        $this->assertSame(RangeRequestResource::TOTAL_ITEMS, $body['hydra:totalItems']);
        $this->assertSame($ids, array_column($body['hydra:member'], 'id'));
    }

    /**
     * @return iterable<string, array{string, string, list<int>}>
     */
    public static function provideSatisfiableRanges(): iterable
    {
        yield 'first page' => ['items=0-9', 'items 0-9/25', range(1, 10)];
        yield 'second page, ignoring the client items per page permission' => ['items=10-19', 'items 10-19/25', range(11, 20)];
        yield 'last page' => ['items=20-24', 'items 20-24/25', range(21, 25)];
        yield 'shorter page' => ['items=15-19', 'items 15-19/25', range(16, 20)];
        yield 'case-insensitive unit' => ['Items=0-4', 'items 0-4/25', range(1, 5)];
    }

    public function testARangeKeepsTheOtherFilters(): void
    {
        $response = self::createClient()->request('GET', '/range_requests?name=Item%20%2312', ['headers' => ['Accept' => 'application/ld+json', 'Range' => 'items=0-0']]);

        $this->assertResponseStatusCodeSame(206);
        $this->assertResponseHeaderSame('Content-Range', 'items 0-0/1');
        $this->assertSame([12], array_column($response->toArray()['hydra:member'], 'id'));
    }

    public function testARangeBeyondTheCollectionIsNotSatisfiable(): void
    {
        self::createClient()->request('GET', '/range_requests', ['headers' => ['Accept' => 'application/ld+json', 'Range' => 'items=30-39']]);

        $this->assertResponseStatusCodeSame(416);
        $this->assertResponseHeaderSame('Content-Range', 'items */25');
    }

    #[DataProvider('provideInvalidRanges')]
    public function testAnInvalidRangeIsNotSatisfiable(string $range): void
    {
        self::createClient()->request('GET', '/range_requests', ['headers' => ['Accept' => 'application/ld+json', 'Range' => $range]]);

        $this->assertResponseStatusCodeSame(416);
        $this->assertResponseNotHasHeader('Content-Range');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidRanges(): iterable
    {
        yield 'first position beyond last position' => ['items=9-0'];
        yield 'not aligned on a page' => ['items=5-14'];
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('provideIgnoredRanges')]
    public function testARangeIsIgnoredWhenRfc9110SaysSo(string $method, string $range, array $headers = []): void
    {
        self::createClient()->request($method, '/range_requests', ['headers' => $headers + ['Accept' => 'application/ld+json', 'Range' => $range]]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Accept-Ranges', 'items');
        $this->assertResponseNotHasHeader('Content-Range');
    }

    /**
     * @return iterable<string, array{string, string, array<string, string>}>
     */
    public static function provideIgnoredRanges(): iterable
    {
        yield 'HEAD request' => ['HEAD', 'items=10-19', []];
        yield 'unknown unit' => ['GET', 'books=10-19', []];
        yield 'If-Range precondition' => ['GET', 'items=10-19', ['If-Range' => '"abc"']];
    }

    public function testARangeIsIgnoredWithoutARangeUnit(): void
    {
        $response = self::createClient()->request('GET', '/range_requests_disabled', ['headers' => ['Accept' => 'application/ld+json', 'Range' => 'items=10-19']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseNotHasHeader('Accept-Ranges');
        $this->assertResponseNotHasHeader('Content-Range');
        $this->assertSame(range(1, 10), array_column($response->toArray()['hydra:member'], 'id'));
    }

    public function testAccessIsCheckedBeforeTheRange(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('user', 'password', ['ROLE_USER']));

        $client->request('GET', '/range_requests_secured', ['headers' => ['Accept' => 'application/ld+json', 'Range' => 'items=5-14']]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertResponseNotHasHeader('Content-Range');
    }

    public function testASecuredCollectionStillServesRangesToAGrantedUser(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        $response = $client->request('GET', '/range_requests_secured', ['headers' => ['Accept' => 'application/ld+json', 'Range' => 'items=0-4']]);

        $this->assertResponseStatusCodeSame(206);
        $this->assertResponseHeaderSame('Content-Range', 'items 0-4/25');
        $this->assertSame(range(1, 5), array_column($response->toArray()['hydra:member'], 'id'));
    }
}
