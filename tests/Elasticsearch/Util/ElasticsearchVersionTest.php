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

namespace ApiPlatform\Tests\Elasticsearch\Util;

use ApiPlatform\Elasticsearch\Util\ElasticsearchVersion;
use PHPUnit\Framework\TestCase;

class ElasticsearchVersionTest extends TestCase
{
    /**
     * @dataProvider supportsDocumentTypeProvider
     */
    public function testSupportsDocumentType(string $version, bool $expected): void
    {
        self::assertSame($expected, ElasticsearchVersion::supportsMappingType($version));
    }

    public function supportsDocumentTypeProvider(): \Generator
    {
        yield 'ES 5' => ['5.5.0', true];
        yield 'ES 5 dev' => ['5.x', true];
        yield 'ES 6' => ['6.8.2', true];
        yield 'ES 7' => ['7.17.0', false];
        yield 'ES 8' => ['8.1.0', false];
        yield 'ES 8 dev' => ['8.x', false];
    }
}
