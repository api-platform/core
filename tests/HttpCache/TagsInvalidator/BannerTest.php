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

namespace ApiPlatform\Tests\HttpCache\TagsInvalidator;

use ApiPlatform\HttpCache\TagsInvalidator\Banner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class BannerTest extends TestCase
{
    public function testBanRequest(): void
    {
        $response = new MockResponse();
        $client = new MockHttpClient($response);
        $banner = new Banner([$client], 'ApiPlatform-Ban-Regex', 7500);

        $banner->invalidate(['/1', '/2', '/3']);

        self::assertSame('BAN', $response->getRequestMethod());

        $headers = $response->getRequestOptions()['normalized_headers'];
        self::assertArrayHasKey('apiplatform-ban-regex', $headers);
        self::assertSame(['ApiPlatform-Ban-Regex: (/1|/2|/3)($|\,)'], $headers['apiplatform-ban-regex']);
    }

    public function testNoRequestIsSentWithoutTag(): void
    {
        self::expectNotToPerformAssertions();

        $client = new MockHttpClient(static fn () => self::fail('No request should have been sent.'));
        $banner = new Banner([$client], 'ApiPlatform-Ban-Regex', 7500);

        $banner->invalidate([]);
    }

    /**
     * @param string[] $tags
     *
     * @dataProvider provideTags
     */
    public function testHeaderDoNotOverflow(array $tags, int $maxHeaderSize, array $expectedHeadersValue): void
    {
        /** @var MockResponse[] $responses */
        $responses = [];
        $client = new MockHttpClient(static function () use (&$responses) {
            return $responses[] = new MockResponse();
        });
        $banner = new Banner([$client], 'ApiPlatform-Ban-Regex', $maxHeaderSize);

        $banner->invalidate($tags);

        $actualHeadersValue = array_map(
            static fn (MockResponse $response): string => substr($response->getRequestOptions()['normalized_headers']['apiplatform-ban-regex'][0], 23),
            $responses
        );
        self::assertSame($expectedHeadersValue, $actualHeadersValue);
    }

    public function testExceptionIsThrownOnTooLongTag(): void
    {
        self::expectExceptionMessage('IRI "ThisTagIsTooLong" is too long to fit the max header size (currently set to "18").');

        $client = new MockHttpClient();
        $purger = new Banner([$client], 'ApiPlatform-Ban-Regex', 18);

        $purger->invalidate(['ThisTagIsTooLong']);
    }

    public static function provideTags(): \Iterator
    {
        yield 'One header, not filled' => [['/1', '/2', '/3'], 20, ['(/1|/2|/3)($|\,)']];

        yield 'One header, filled' => [['/1', '/2', '/3'], 16, ['(/1|/2|/3)($|\,)']];

        yield 'Two headers, none filled' => [['/1', '/2', '/3'], 15, ['(/1|/2)($|\,)', '(/3)($|\,)']];

        yield 'Two headers, first filled' => [['/1', '/2', '/3'], 13, ['(/1|/2)($|\,)', '(/3)($|\,)']];

        yield 'Two headers, both filled' => [['/1', '/2', '/3', '/4'], 13, ['(/1|/2)($|\,)', '(/3|/4)($|\,)']];

        yield 'Quoted tags' => [['/1-1', '/1-2', '/1-3', '/1-4'], 19, ['(/1\-1|/1\-2)($|\,)', '(/1\-3|/1\-4)($|\,)']];
    }
}
