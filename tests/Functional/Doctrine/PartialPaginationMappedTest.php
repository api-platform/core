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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PartialPaginationMappedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PartialPaginationMappedDocument;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class PartialPaginationMappedTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [PartialPaginationMappedResource::class];
    }

    public function testPartialPaginationWithObjectMapper(): void
    {
        if (!$this->isMongoDB()) {
            $this->markTestSkipped('MongoDB only test.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([PartialPaginationMappedDocument::class]);
        $this->loadFixtures();

        $client = self::createClient();
        $r = $client->request('GET', '/partial_pagination_mapped_resources');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/partial_pagination_mapped_resources',
            'member' => [
                ['title' => 'Item 1'],
                ['title' => 'Item 2'],
                ['title' => 'Item 3'],
            ],
            'view' => [
                '@type' => 'PartialCollectionView',
                'next' => '/partial_pagination_mapped_resources?page=2',
            ],
        ]);
        $this->assertCount(3, $r->toArray()['member']);

        $r = $client->request('GET', '/partial_pagination_mapped_resources?page=2');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'view' => [
                '@type' => 'PartialCollectionView',
                'previous' => '/partial_pagination_mapped_resources?page=1',
                'next' => '/partial_pagination_mapped_resources?page=3',
            ],
        ]);
        $this->assertCount(3, $r->toArray()['member']);
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        for ($i = 1; $i <= 10; ++$i) {
            $doc = new PartialPaginationMappedDocument();
            $doc->setName('Item '.$i);
            $manager->persist($doc);
        }

        $manager->flush();
    }
}
