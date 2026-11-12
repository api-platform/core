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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedAnimal;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedCat;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedCircle;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedDog;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedShape;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedSportsCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedSquare;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedTruck;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedVehicle;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Polymorphic resources declared through the Doctrine ORM discriminator map only.
 */
final class DoctrineDiscriminatedResourceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    protected static function getKernelClass(): string
    {
        return DoctrineDiscriminatorMapAppKernel::class;
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            DoctrineDiscriminatedVehicle::class,
            DoctrineDiscriminatedCar::class,
            DoctrineDiscriminatedShape::class,
            DoctrineDiscriminatedAnimal::class,
        ];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ORM only, see DoctrineDiscriminatedDocumentTest.');
        }

        $this->recreateSchema([
            DoctrineDiscriminatedVehicle::class,
            DoctrineDiscriminatedCar::class,
            DoctrineDiscriminatedSportsCar::class,
            DoctrineDiscriminatedTruck::class,
            DoctrineDiscriminatedShape::class,
            DoctrineDiscriminatedCircle::class,
            DoctrineDiscriminatedSquare::class,
            DoctrineDiscriminatedAnimal::class,
            DoctrineDiscriminatedDog::class,
            DoctrineDiscriminatedCat::class,
        ]);

        $car = new DoctrineDiscriminatedCar();
        $car->name = 'Family car';
        $car->seats = 5;
        $car->vin = 'secret-vin';

        $truck = new DoctrineDiscriminatedTruck();
        $truck->name = 'Big truck';
        $truck->payload = 20000;

        $sportsCar = new DoctrineDiscriminatedSportsCar();
        $sportsCar->name = 'Fast car';
        $sportsCar->seats = 2;
        $sportsCar->topSpeed = 320;

        $circle = new DoctrineDiscriminatedCircle();
        $circle->color = 'red';
        $circle->radius = 2.5;

        $dog = new DoctrineDiscriminatedDog();
        $dog->name = 'Rex';

        $manager = $this->getManager();
        foreach ([$car, $truck, $sportsCar, $circle, $dog] as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();
        $manager->clear();
    }

    public function testGetAndPostANonResourceSubtype(): void
    {
        $client = self::createClient();

        $client->request('GET', '/doctrine_discriminated_vehicles/2');
        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/DoctrineDiscriminatedVehicle',
            '@id' => '/doctrine_discriminated_vehicles/2',
            '@type' => 'DoctrineDiscriminatedTruck',
            'kind' => 'truck',
            'id' => 2,
            'name' => 'Big truck',
            'payload' => 20000,
        ]);

        $client->request('POST', '/doctrine_discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Small truck', 'payload' => 3500],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['@id' => '/doctrine_discriminated_vehicles/4', '@type' => 'DoctrineDiscriminatedTruck', 'kind' => 'truck', 'payload' => 3500]);

        $truck = $this->findVehicle(4);
        $this->assertInstanceOf(DoctrineDiscriminatedTruck::class, $truck);
        $this->assertSame(3500, $truck->payload);
    }

    public function testGetItemOfAResourceSubtypeUsesItsOwnResource(): void
    {
        self::createClient()->request('GET', '/doctrine_discriminated_cars/3');

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/DoctrineDiscriminatedCar',
            '@id' => '/doctrine_discriminated_cars/3',
            '@type' => 'DoctrineDiscriminatedSportsCar',
            'kind' => 'sportsCar',
            'id' => 3,
            'name' => 'Fast car',
            'seats' => 2,
            'topSpeed' => 320,
        ]);
    }

    public function testJoinedInheritanceWithIntegerDiscriminatorValues(): void
    {
        $client = self::createClient();

        $client->request('GET', '/doctrine_discriminated_shapes/1');
        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/DoctrineDiscriminatedShape',
            '@id' => '/doctrine_discriminated_shapes/1',
            '@type' => 'DoctrineDiscriminatedCircle',
            'shapeType' => '1',
            'id' => 1,
            'color' => 'red',
            'radius' => 2.5,
        ]);

        $client->request('POST', '/doctrine_discriminated_shapes', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['shapeType' => '2', 'color' => 'blue', 'side' => 3],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['shapeType' => '2', 'side' => 3]);
        $this->assertInstanceOf(DoctrineDiscriminatedSquare::class, $this->getManager()->find(DoctrineDiscriminatedShape::class, 2));
    }

    public function testSerializerDiscriminatorMapTakesPrecedence(): void
    {
        $client = self::createClient();

        $client->request('GET', '/doctrine_discriminated_animals/1');
        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/DoctrineDiscriminatedAnimal',
            '@id' => '/doctrine_discriminated_animals/1',
            '@type' => 'DoctrineDiscriminatedDog',
            'species' => 'canine',
            'id' => 1,
            'name' => 'Rex',
            'goodBoy' => true,
        ]);

        // Only the subtypes of the serializer map can be created, using its type property
        $client->request('POST', '/doctrine_discriminated_animals', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['discr' => 'cat', 'name' => 'Tom', 'lives' => 7],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    private function findVehicle(int $id): ?DoctrineDiscriminatedVehicle
    {
        $manager = $this->getManager();
        $manager->clear();

        return $manager->find(DoctrineDiscriminatedVehicle::class, $id);
    }
}
