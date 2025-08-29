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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PartialPaginationDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;

final class PartialPaginationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [PartialPaginationDummy::class];
    }

    public function testPartialPagination(): void
    {
        if (!$this->isMongoDB()) {
            $this->markTestSkipped('MongoDB only test.');
        }

        $this->recreateSchema([PartialPaginationDummy::class]);
        $this->loadFixtures();

        $client = self::createClient();
        $r = $client->request('GET', '/partial_pagination_dummies');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/PartialPaginationDummy',
            '@id' => '/partial_pagination_dummies',
            'view' => [
                '@type' => 'PartialCollectionView',
                'next' => '/partial_pagination_dummies?page=2',
            ],
        ]);
        $this->assertArrayNotHasKey('previous', $r->toArray()['view']);

        $r = $client->request('GET', '/partial_pagination_dummies?page=2');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/PartialPaginationDummy',
            '@id' => '/partial_pagination_dummies',
            'view' => [
                '@type' => 'PartialCollectionView',
                'previous' => '/partial_pagination_dummies?page=1',
                'next' => '/partial_pagination_dummies?page=3',
            ],
        ]);

        $r = $client->request('GET', '/partial_pagination_dummies?page=4');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/PartialPaginationDummy',
            '@id' => '/partial_pagination_dummies',
            'view' => [
                '@type' => 'PartialCollectionView',
                'previous' => '/partial_pagination_dummies?page=3',
            ],
        ]);
        $this->assertArrayNotHasKey('next', $r->toArray()['view']);
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        for ($i = 1; $i <= 10; ++$i) {
            $dummy = new PartialPaginationDummy();
            $dummy->setName('Dummy '.$i);
            $manager->persist($dummy);
        }

        $manager->flush();
    }
}
