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

namespace ApiPlatform\Tests\Functional\HttpCache;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class HeadersTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [RelationEmbedder::class];
    }

    public function testDefaultCacheHeaders(): void
    {
        $this->recreateSchema([RelationEmbedder::class]);

        $response = self::createClient()->request('GET', '/relation_embedders');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Etag', '"032297ac74d75a50"');
        $this->assertResponseHeaderSame('Cache-Control', 'max-age=60, public, s-maxage=3600');
        // Vary headers may come on multiple lines depending on the framework version.
        $this->assertSame(
            ['accept', 'cookie', 'accept-language'],
            array_map('strtolower', $response->getHeaders()['vary'] ?? []),
        );
    }
}
