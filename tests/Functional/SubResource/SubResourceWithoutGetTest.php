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

namespace ApiPlatform\Tests\Functional\SubResource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5722\Event;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5722\ItemLog;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;

final class SubResourceWithoutGetTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Event::class, ItemLog::class];
    }

    public function testGetSubresourceFromInverseSideWithoutItemOperation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $this->recreateSchema([Event::class, ItemLog::class]);

        $manager = $this->getManager();
        $event = new Event();
        $event->logs = new ArrayCollection([new ItemLog(), new ItemLog()]);
        $event->uuid = Uuid::fromString('03af3507-271e-4cca-8eee-6244fb06e95b');
        $manager->persist($event);
        foreach ($event->logs as $log) {
            $log->item = $event;
            $manager->persist($log);
        }
        $manager->flush();

        self::createClient()->request('GET', '/events/03af3507-271e-4cca-8eee-6244fb06e95b/logs', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
    }
}
