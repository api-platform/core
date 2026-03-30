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
use ApiPlatform\Tests\Fixtures\NullPurger;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Relation1;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Relation2;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Relation3;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CacheTagsTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            RelationEmbedder::class,
            RelatedDummy::class,
            ThirdLevel::class,
            Relation1::class,
            Relation2::class,
            Relation3::class,
        ];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('HTTP Cache tags only enabled on SQLite test suite');
        }

        $this->recreateSchema($this->getResources());
        $this->purger()->clear();
    }

    public function testFullCacheTagsLifecycle(): void
    {
        $client = self::createClient();

        // Create an embedded relation; collection IRIs should be purged.
        $client->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'anotherRelated' => ['name' => 'Related', 'thirdLevel' => new \stdClass()],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseNotHasHeader('Cache-Tags');
        $this->assertSamePurgedIris([
            '/relation_embedders',
            '/related_dummies',
            '/third_levels',
        ]);

        // Item GET exposes Cache-Tags.
        $client->request('GET', '/relation_embedders/1');
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Cache-Tags', '/third_levels/1,/related_dummies/1,/relation_embedders/1');

        // Create a second embedded relation.
        $client->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['anotherRelated' => ['name' => 'Another Related', 'thirdLevel' => new \stdClass()]],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseNotHasHeader('Cache-Tags');

        // Collection GET aggregates per-item tags.
        $client->request('GET', '/relation_embedders');
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame(
            'Cache-Tags',
            '/third_levels/1,/related_dummies/1,/relation_embedders/1,/third_levels/2,/related_dummies/2,/relation_embedders/2,/relation_embedders',
        );

        // PUT purges item and related dummy.
        $this->purger()->clear();
        $client->request('PUT', '/relation_embedders/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['paris' => 'France'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseNotHasHeader('Cache-Tags');
        $this->assertSamePurgedIris(['/relation_embedders', '/relation_embedders/1', '/related_dummies/1']);

        // DELETE purges item and related dummy.
        $this->purger()->clear();
        $client->request('DELETE', '/relation_embedders/1');
        $this->assertResponseStatusCodeSame(204);
        $this->assertResponseNotHasHeader('Cache-Tags');
        $this->assertSamePurgedIris(['/relation_embedders', '/relation_embedders/1', '/related_dummies/1']);
    }

    public function testManyToManyCacheTags(): void
    {
        $client = self::createClient();

        // Two Relation2 instances.
        $client->request('POST', '/relation2s', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => new \stdClass(),
        ]);
        $client->request('POST', '/relation2s', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => new \stdClass(),
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Item GET on a Relation2 lists embedded collection tag.
        $client->request('GET', '/relation2s/1');
        $this->assertResponseHeaderSame('Cache-Tags', '/relation2s/1');

        // Many-to-one purges Relation2 sibling.
        $this->purger()->clear();
        $client->request('POST', '/relation1s', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['relation2' => '/relation2s/1'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertSamePurgedIris(['/relation1s', '/relation2s/1']);

        // Replacing the relation purges old + new sides.
        $this->purger()->clear();
        $client->request('PUT', '/relation1s/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['relation2' => '/relation2s/2'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSamePurgedIris(['/relation1s', '/relation1s/1', '/relation2s/2', '/relation2s/1']);

        // Many-to-many POST purges all referenced Relation2.
        $this->purger()->clear();
        $client->request('POST', '/relation3s', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['relation2s' => ['/relation2s/1', '/relation2s/2']],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertSamePurgedIris(['/relation3s', '/relation2s/1', '/relation2s/2']);

        // Collection GET aggregates tags including the collection IRI.
        $client->request('GET', '/relation3s');
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Cache-Tags', '/relation2s/1,/relation2s/2,/relation3s/1,/relation3s');

        // Updating a many-to-many removes a sibling and purges the old & new ones.
        $this->purger()->clear();
        $client->request('PUT', '/relation3s/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['relation2s' => ['/relation2s/2']],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSamePurgedIris(['/relation3s', '/relation3s/1', '/relation2s/2', '/relation2s', '/relation2s/1']);

        // Deleting the m2m owner purges the remaining sibling.
        $this->purger()->clear();
        $client->request('DELETE', '/relation3s/1');
        $this->assertResponseStatusCodeSame(204);
        $this->assertSamePurgedIris(['/relation3s', '/relation3s/1', '/relation2s/2']);
    }

    private function assertSamePurgedIris(array $expected): void
    {
        $purged = $this->purger()->getIris();
        sort($expected);
        sort($purged);
        $this->assertSame($expected, $purged);
    }

    private function purger(): NullPurger
    {
        $purger = static::getContainer()->get('test.api_platform.http_cache.purger');
        \assert($purger instanceof NullPurger);

        return $purger;
    }

    private function isMongoDB(): bool
    {
        return 'mongodb' === static::getContainer()->getParameter('kernel.environment');
    }
}
