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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PartialPaginationMongo\PartialPaginationMongoDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PartialPaginationMongoDbTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use RefreshDatabase;
    use SetupClassResourcesTrait;
    use WithWorkbench;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [PartialPaginationMongoDummy::class];
    }

    /**
     * When using partial pagination, totalItems should not be present.
     */
    public function testGetPartialNoItemCount(): void
    {
        if (!$this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('GET', '/partial_pagination_mongo_dummies?page=1&itemsPerPage=3&partial=true', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('hydra:totalItems', $response->toArray());
    }

    /**
     * When not using partial pagination, totalItems should be present.
     *
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetItemCount(): void
    {
        if (!$this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('GET', '/partial_pagination_mongo_dummies?page=1&itemsPerPage=3&partial=false', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('hydra:totalItems', $response->toArray());
    }
}
