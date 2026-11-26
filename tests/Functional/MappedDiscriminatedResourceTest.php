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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedDiscriminatedVehicle\MappedVehicle;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedSportsCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedTruck;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedVehicle;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MappedDiscriminatedResourceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    protected static function getKernelClass(): string
    {
        return DiscriminatorMapAppKernel::class;
    }

    public static function getResources(): array
    {
        return [MappedVehicle::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ORM only.');
        }

        $this->recreateSchema([DoctrineDiscriminatedVehicle::class, DoctrineDiscriminatedCar::class, DoctrineDiscriminatedSportsCar::class, DoctrineDiscriminatedTruck::class]);
    }

    public function testGetItemMapsEntitySubtypeToResourceSubtype(): void
    {
        $truck = new DoctrineDiscriminatedTruck();
        $truck->name = 'Volvo';
        $truck->payload = 20000;
        $this->persist($truck);

        self::createClient()->request('GET', '/mapped_vehicles/'.$truck->id);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['kind' => 'truck', 'name' => 'Volvo', 'payload' => 20000]);
    }

    public function testGetCollectionMapsEachItemToItsSubtype(): void
    {
        $car = new DoctrineDiscriminatedCar();
        $car->name = 'Clio';
        $car->seats = 5;
        $truck = new DoctrineDiscriminatedTruck();
        $truck->name = 'Volvo';
        $truck->payload = 20000;
        $this->persist($car, $truck);

        self::createClient()->request('GET', '/mapped_vehicles');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'hydra:member' => [
                ['kind' => 'car', 'name' => 'Clio', 'seats' => 5],
                ['kind' => 'truck', 'name' => 'Volvo', 'payload' => 20000],
            ],
        ]);
    }

    public function testPostPersistsEntitySubtype(): void
    {
        self::createClient()->request('POST', '/mapped_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Scania', 'payload' => 3500],
        ]);

        $this->assertResponseStatusCodeSame(201);

        $manager = $this->getManager();
        $manager->clear();
        $stored = $manager->getRepository(DoctrineDiscriminatedVehicle::class)->findOneBy(['name' => 'Scania']);
        $this->assertInstanceOf(DoctrineDiscriminatedTruck::class, $stored);
        $this->assertSame(3500, $stored->payload);
    }

    private function persist(object ...$entities): void
    {
        $manager = $this->getManager();
        foreach ($entities as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();
        $manager->clear();
    }
}
