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

namespace ApiPlatform\Tests\Functional\Mercure;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedBackingGadget;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedBackingThing;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SharedBackingEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Mercure\TestHub;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SharedBackingEntityMercureTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [SharedBackingThing::class, SharedBackingGadget::class];
    }

    public function testPublishResolvedResourceOnCreate(): void
    {
        if ('mongodb' === static::getContainer()->getParameter('kernel.environment')) {
            $this->markTestSkipped('ORM only.');
        }

        $this->recreateSchema([SharedBackingEntity::class]);
        $hub = static::getContainer()->get('mercure.hub.default.test_hub');
        \assert($hub instanceof TestHub);
        (new \ReflectionProperty(TestHub::class, 'updates'))->setValue($hub, []);

        self::createClient()->request('POST', '/shared_backing_things', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'created', 'kind' => 'thing'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $updates = $hub->getUpdates();
        $this->assertCount(1, $updates);
        $this->assertSame(['http://localhost/shared_backing_things/1'], array_values($updates[0]->getTopics()));
        $this->assertSame('SharedBackingThing', json_decode($updates[0]->getData(), true, flags: \JSON_THROW_ON_ERROR)['@type']);
    }
}
