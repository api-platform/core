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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SoMany;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CursorPaginationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [SoMany::class];
    }

    public function testEmptyCollectionWithCursorPagination(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([SoMany::class]);

        $response = self::createClient()->request('GET', '/so_manies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/contexts/SoMany', $body['@context']);
        $this->assertSame('/so_manies', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertSame('/so_manies', $body['hydra:view']['@id']);
        $this->assertSame('hydra:PartialCollectionView', $body['hydra:view']['@type']);
        $this->assertCount(0, $body['hydra:member']);
    }

    public function testRangedItemsWithCursorPagination(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([SoMany::class]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 10; ++$i) {
            $s = new SoMany();
            $s->content = "row $i";
            $manager->persist($s);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/so_manies?order[id]=desc', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/so_manies?order%5Bid%5D=desc', $body['hydra:view']['@id']);
        $this->assertSame('/so_manies?order%5Bid%5D=desc&id%5Bgt%5D=10', $body['hydra:view']['hydra:previous']);
        $this->assertSame('/so_manies?order%5Bid%5D=desc&id%5Blt%5D=8', $body['hydra:view']['hydra:next']);
        $this->assertGreaterThanOrEqual(3, \count($body['hydra:member']));
    }

    public function testRangeFilteredItemsWithCursorPagination(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([SoMany::class]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 10; ++$i) {
            $s = new SoMany();
            $s->content = "row $i";
            $manager->persist($s);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/so_manies?order[id]=desc&id[gt]=10', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(0, $body['hydra:member']);
        $this->assertSame('/so_manies?id%5Bgt%5D=10&order%5Bid%5D=desc', $body['hydra:view']['@id']);
        $this->assertSame('hydra:PartialCollectionView', $body['hydra:view']['@type']);
    }
}
