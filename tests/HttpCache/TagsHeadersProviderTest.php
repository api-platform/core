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

namespace ApiPlatform\Tests\HttpCache;

use ApiPlatform\HttpCache\TagsHeadersProvider;
use PHPUnit\Framework\TestCase;

class TagsHeadersProviderTest extends TestCase
{
    public function testSingleHeader(): void
    {
        $provider = new TagsHeadersProvider('Cache-Tags', ',');

        self::assertSame(['Cache-Tags' => '1,2,3'], $provider->provideHeaders(['1', '2', '3']));
    }

    public function testRepeatedHeader(): void
    {
        $provider = new TagsHeadersProvider('Cache-Tags', null);

        self::assertSame(['Cache-Tags' => ['1', '2', '3']], $provider->provideHeaders(['1', '2', '3']));
    }
}
