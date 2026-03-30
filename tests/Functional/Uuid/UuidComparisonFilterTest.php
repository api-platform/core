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

namespace ApiPlatform\Tests\Functional\Uuid;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\SymfonyUuidDevice;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\SymfonyUuidDeviceEndpoint;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class UuidComparisonFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SymfonyUuidDevice::class, SymfonyUuidDeviceEndpoint::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMongoDB()) {
            $this->markTestSkipped('UuidFilter is ORM only.');
        }
    }

    public function testGtWithUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($device1 = new SymfonyUuidDevice());
        usleep(1000);
        $manager->persist($device2 = new SymfonyUuidDevice());
        usleep(1000);
        $manager->persist($device3 = new SymfonyUuidDevice());
        $manager->flush();

        $uuids = [(string) $device1->id, (string) $device2->id, (string) $device3->id];
        sort($uuids);

        $response = self::createClient()->request('GET', '/symfony_uuid_devices', [
            'query' => [
                'idComparison' => ['gt' => $uuids[1]],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $json = $response->toArray();
        self::assertSame(1, $json['hydra:totalItems']);
        self::assertSame($uuids[2], $json['hydra:member'][0]['id']);
    }

    public function testGteWithUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($device1 = new SymfonyUuidDevice());
        usleep(1000);
        $manager->persist($device2 = new SymfonyUuidDevice());
        usleep(1000);
        $manager->persist($device3 = new SymfonyUuidDevice());
        $manager->flush();

        $uuids = [(string) $device1->id, (string) $device2->id, (string) $device3->id];
        sort($uuids);

        $response = self::createClient()->request('GET', '/symfony_uuid_devices', [
            'query' => [
                'idComparison' => ['gte' => $uuids[1]],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $json = $response->toArray();
        self::assertSame(2, $json['hydra:totalItems']);
    }

    public function testLtWithUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($device1 = new SymfonyUuidDevice());
        usleep(1000);
        $manager->persist($device2 = new SymfonyUuidDevice());
        usleep(1000);
        $manager->persist($device3 = new SymfonyUuidDevice());
        $manager->flush();

        $uuids = [(string) $device1->id, (string) $device2->id, (string) $device3->id];
        sort($uuids);

        $response = self::createClient()->request('GET', '/symfony_uuid_devices', [
            'query' => [
                'idComparison' => ['lt' => $uuids[1]],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $json = $response->toArray();
        self::assertSame(1, $json['hydra:totalItems']);
        self::assertSame($uuids[0], $json['hydra:member'][0]['id']);
    }

    public function testCombinedGteAndLteWithUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($device1 = new SymfonyUuidDevice());
        usleep(1000);
        $manager->persist($device2 = new SymfonyUuidDevice());
        usleep(1000);
        $manager->persist($device3 = new SymfonyUuidDevice());
        usleep(1000);
        $manager->persist($device4 = new SymfonyUuidDevice());
        $manager->flush();

        $uuids = [(string) $device1->id, (string) $device2->id, (string) $device3->id, (string) $device4->id];
        sort($uuids);

        $response = self::createClient()->request('GET', '/symfony_uuid_devices', [
            'query' => [
                'idComparison' => ['gte' => $uuids[1], 'lte' => $uuids[2]],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $json = $response->toArray();
        self::assertSame(2, $json['hydra:totalItems']);
    }

    public function testNeWithUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($device1 = new SymfonyUuidDevice());
        $manager->persist($device2 = new SymfonyUuidDevice());
        $manager->persist($device3 = new SymfonyUuidDevice());
        $manager->flush();

        $response = self::createClient()->request('GET', '/symfony_uuid_devices', [
            'query' => [
                'idComparison' => ['ne' => (string) $device2->id],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $json = $response->toArray();
        self::assertSame(2, $json['hydra:totalItems']);

        $returnedIds = array_map(static fn (array $m) => $m['id'], $json['hydra:member']);
        self::assertNotContains((string) $device2->id, $returnedIds);
    }

    protected function tearDown(): void
    {
        if ($this->isMongoDB()) {
            return;
        }

        $this->recreateSchema(static::getResources());
        parent::tearDown();
    }
}
