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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedBackingGadget;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedBackingThing;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SharedBackingEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;

final class SharedBackingEntityPurgeTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [SharedBackingThing::class, SharedBackingGadget::class];
    }

    protected function setUp(): void
    {
        if ('mongodb' === static::getContainer()->getParameter('kernel.environment')) {
            $this->markTestSkipped('ORM only.');
        }

        $this->recreateSchema([SharedBackingEntity::class]);
    }

    public function testUpdatePurgesResolvedResourceIri(): void
    {
        $entity = new SharedBackingEntity();
        $entity->kind = 'thing';
        $entity->name = 'before';
        $manager = static::getContainer()->get('doctrine')->getManager();
        \assert($manager instanceof EntityManagerInterface);
        $manager->persist($entity);
        $manager->flush();
        $this->purger()->clear();

        self::createClient()->request('PATCH', '/shared_backing_things/'.$entity->id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'after'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['/shared_backing_things', '/shared_backing_things/1'], $this->purgedIris());
    }

    public function testUpdatePurgesCollectionsWithUriVariables(): void
    {
        $entity = new SharedBackingEntity();
        $entity->kind = 'gadget';
        $entity->name = 'before';
        $manager = static::getContainer()->get('doctrine')->getManager();
        \assert($manager instanceof EntityManagerInterface);
        $manager->persist($entity);
        $manager->flush();
        $this->purger()->clear();

        self::createClient()->request('PATCH', '/shared_backing_gadgets/'.$entity->id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'after'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['/shared_backing_gadgets', '/shared_backing_gadgets/1', '/shared_backing_kinds/gadget/gadgets'], $this->purgedIris());
    }

    private function purgedIris(): array
    {
        $iris = $this->purger()->getIris();
        sort($iris);

        return $iris;
    }

    private function purger(): NullPurger
    {
        $purger = static::getContainer()->get('test.api_platform.http_cache.purger');
        \assert($purger instanceof NullPurger);

        return $purger;
    }
}
