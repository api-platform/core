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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MappedResourcePutTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;
    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [MappedResource::class];
    }

    public function testPutOnMappedResource(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([MappedEntity::class]);
        $client = self::createClient();
        $created = $client->request('POST', '/mapped_resources', ['json' => ['username' => 'so yuka']])->toArray();

        $updated = $client->request('PUT', '/mapped_resources/'.$created['id'], ['json' => ['username' => 'ba zar']])->toArray();

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('ba zar', $updated['username']);
        $this->assertSame($created['id'], $updated['id']);
        $this->assertSame(1, static::getContainer()->get('doctrine')->getRepository(MappedEntity::class)->count([]));
    }
}
