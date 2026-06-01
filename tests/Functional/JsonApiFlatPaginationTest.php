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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class JsonApiFlatPaginationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Dummy::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMongoDB()) {
            return;
        }

        $this->recreateSchema([Dummy::class]);
        $this->loadFixtures();
    }

    /**
     * Regression test for issue #7888: when a JSON:API request combines a
     * bracket-form filter (filter[...]) with a flat pagination param (page=N),
     * the page param must still drive the current page rather than being
     * silently dropped.
     */
    public function testFlatPageWithBracketFilterDrivesCurrentPage(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $response = self::createClient()->request('GET', '/dummies?filter[name]=foo&page=2', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');

        $data = $response->toArray();

        $this->assertSame(2, $data['meta']['currentPage'] ?? null);
        $this->assertSame(5, $data['meta']['totalItems'] ?? null);
    }

    // #8216: flat filter param combined with flat page must still drive filtering.
    public function testFlatCustomParamWithFlatPagePreservesFilter(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $response = self::createClient()->request('GET', '/dummies?name=foo&page=1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');

        $data = $response->toArray();

        $this->assertSame(1, $data['meta']['currentPage'] ?? null);
        $this->assertSame(5, $data['meta']['totalItems'] ?? null);
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        for ($i = 1; $i <= 5; ++$i) {
            $dummy = new Dummy();
            $dummy->setName('foo #'.$i);
            $manager->persist($dummy);
        }

        $bar = new Dummy();
        $bar->setName('bar');
        $manager->persist($bar);

        $manager->flush();
    }
}
