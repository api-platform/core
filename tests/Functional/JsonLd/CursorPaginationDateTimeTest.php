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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8085\DatedCursorDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Regression test for https://github.com/api-platform/core/issues/8085.
 *
 * Cursor pagination must support DateTimeInterface fields without throwing
 * "Object of class DateTime could not be converted to string".
 */
final class CursorPaginationDateTimeTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [DatedCursorDummy::class];
    }

    public function testCursorPaginationWithDateTimeField(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([DatedCursorDummy::class]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 5; ++$i) {
            $d = new DatedCursorDummy();
            $d->createdAt = new \DateTimeImmutable("2024-01-0$i 12:00:00", new \DateTimeZone('UTC'));
            $manager->persist($d);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/dated_cursor_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();

        $this->assertArrayHasKey('hydra:view', $body);
        $this->assertArrayHasKey('hydra:next', $body['hydra:view']);

        // 5 rows (2024-01-01..05) sorted DESC + 3 per page → visible page is 05,04,03;
        // hydra:next must use lt with the last visible item (2024-01-03) as ISO 8601.
        $this->assertMatchesRegularExpression(
            '#createdAt%5Blt%5D=2024-01-03T12%3A00%3A00(?:%2B00%3A00|Z)#',
            $body['hydra:view']['hydra:next'],
        );
    }
}
