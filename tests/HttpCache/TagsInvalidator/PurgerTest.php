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

use ApiPlatform\HttpCache\TagsInvalidator\Purger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PurgerTest extends TestCase
{
    public function testPurgeRequest(): void
    {
        $response = new MockResponse();
        $client = new MockHttpClient($response);
        $purger = new Purger([$client], 'xkey', 7500, ' ');

        $purger->invalidate(['/1', '/2', '/3']);

        self::assertSame('PURGE', $response->getRequestMethod());

        $headers = $response->getRequestOptions()['normalized_headers'];
        self::assertArrayHasKey('xkey', $headers);
        self::assertSame(['xkey: /1 /2 /3'], $headers['xkey']);
    }

    public function testNoRequestIsSentWithoutTag(): void
    {
        self::expectNotToPerformAssertions();

        $client = new MockHttpClient(static fn () => self::fail('No request should have been sent.'));
        $purger = new Purger([$client], 'xkey', 7500, ' ');

        $purger->invalidate([]);
    }

    /**
     * @param string[] $tags
     *
     * @dataProvider provideTags
     */
    public function testHeaderDoNotOverflow(array $tags, int $maxHeaderSize, string $glue, array $expectedHeadersValue): void
    {
        /** @var MockResponse[] $responses */
        $responses = [];
        $client = new MockHttpClient(static function () use (&$responses) {
            return $responses[] = new MockResponse();
        });
        $purger = new Purger([$client], 'xkey', $maxHeaderSize, $glue);

        $purger->invalidate($tags);

        $actualHeadersValue = array_map(
            static fn (MockResponse $response): string => substr($response->getRequestOptions()['normalized_headers']['xkey'][0], 6),
            $responses
        );
        self::assertSame($expectedHeadersValue, $actualHeadersValue);
    }

    public function testExceptionIsThrownOnTooLongTag(): void
    {
        self::expectExceptionMessage('IRI "ThisTagIsTooLong" is too long to fit the max header size (currently set to "10").');

        $client = new MockHttpClient();
        $purger = new Purger([$client], 'xkey', 10, ' ');

        $purger->invalidate(['ThisTagIsTooLong']);
    }

    public static function provideTags(): \Iterator
    {
        yield 'One header, not filled' => [['/1', '/2', '/3'], 10, ' ', ['/1 /2 /3']];

        yield 'One header, filled' => [['/1', '/2', '/3'], 8, ' ', ['/1 /2 /3']];

        yield 'Two headers, none filled' => [['/1', '/2', '/3'], 7, ' ', ['/1 /2', '/3']];

        yield 'Two headers, first filled' => [['/1', '/2', '/3'], 5, ' ', ['/1 /2', '/3']];

        yield 'Two headers, both filled' => [['/1', '/2', '/3', '/4'], 5, ' ', ['/1 /2', '/3 /4']];

        yield 'Multibyte glue' => [['/1', '/2', '/3', '/4'], 5, ', ', ['/1', '/2', '/3', '/4']];
    }
}
