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
use ApiPlatform\Tests\Fixtures\NullPurger;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\HttpCachePurgeMultiCollection\Dummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Regression: when a resource declares several GetCollection operations, the
 * HTTP cache purger must invalidate every collection URI, not just the first
 * one resolved from a bare `new GetCollection()`.
 */
final class HttpCachePurgeMultiCollectionTest extends ApiTestCase
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

    public function testPostPurgesAllGetCollectionUris(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('The Doctrine ORM PurgeHttpCacheListener is not loaded with MongoDB.');
        }

        $this->recreateSchema([Dummy::class]);

        $client = static::createClient();
        $purger = static::getContainer()->get('test.api_platform.http_cache.purger');
        self::assertInstanceOf(NullPurger::class, $purger);
        $purger->clear();

        $client->request('POST', '/http_cache_purge_multi_collection_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'foo'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $iris = $purger->getIris();
        self::assertContains('/http_cache_purge_multi_collection_dummies', $iris, 'The default collection URI must be purged.');
        self::assertContains('/http_cache_purge_multi_collection_dummies/featured', $iris, 'The secondary collection URI must also be purged.');
    }

    public function testPatchPurgesAllGetItemUris(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('The Doctrine ORM PurgeHttpCacheListener is not loaded with MongoDB.');
        }

        $this->recreateSchema([Dummy::class]);

        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->name = 'before';
        $manager->persist($dummy);
        $manager->flush();
        $id = $dummy->getId();
        $manager->clear();

        $client = static::createClient();
        $purger = static::getContainer()->get('test.api_platform.http_cache.purger');
        self::assertInstanceOf(NullPurger::class, $purger);
        $purger->clear();

        $client->request('PATCH', '/http_cache_purge_multi_collection_dummies/'.$id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'after'],
        ]);
        $this->assertResponseStatusCodeSame(200);

        $iris = $purger->getIris();
        self::assertContains('/http_cache_purge_multi_collection_dummies/'.$id, $iris, 'The canonical item URI must be purged.');
        self::assertContains('/http_cache_purge_multi_collection_dummies/'.$id.'/details', $iris, 'The secondary item URI must also be purged.');
    }
}
