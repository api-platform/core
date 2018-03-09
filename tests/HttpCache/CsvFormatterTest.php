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

namespace ApiPlatform\Core\Tests\HttpCache;

use ApiPlatform\Core\HttpCache\CsvFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class CsvFormatterTest extends TestCase
{
    public function testFormat()
    {
        $formatter = new CsvFormatter();
        $this->assertSame('foobar', $formatter->formatTags(['foobar']));
        $this->assertSame('foobar,foobar2', $formatter->formatTags(['foobar', 'foobar2']));
    }
}
