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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CursorPaginatedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Regression test for https://github.com/api-platform/core/issues/7953.
 *
 * An empty cursor-paginated collection must still emit hydra:next and hydra:previous
 * when the request URL contains a cursor filter parameter.
 */
final class CursorPaginationEmptyCollectionTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [CursorPaginatedDummy::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        for ($i = 0; $i < 10; ++$i) {
            $manager->persist(new CursorPaginatedDummy());
        }
        $manager->flush();
    }

    public function testEmptyCollectionWithCursorFilterHasNavigationLinks(): void
    {
        // id[gt]=10 matches nothing (max id is 10), so the collection is empty
        $response = self::createClient()->request('GET', '/cursor_paginated_dummies?id[gt]=10&order[id]=desc', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEmpty($data['hydra:member'], 'Collection must be empty for this test to be meaningful');
        $this->assertArrayHasKey('hydra:view', $data);

        // Both navigation links must be present even on empty collection
        $this->assertArrayHasKey('hydra:next', $data['hydra:view']);
        $this->assertArrayHasKey('hydra:previous', $data['hydra:view']);

        // hydra:next: inverted operator (gt -> lt), same cursor value
        $this->assertStringContainsString('id%5Blt%5D=10', $data['hydra:view']['hydra:next']);
        // hydra:previous: same operator (gt), value shifted by items_per_page (10 + 3 = 13)
        $this->assertStringContainsString('id%5Bgt%5D=13', $data['hydra:view']['hydra:previous']);
    }
}
