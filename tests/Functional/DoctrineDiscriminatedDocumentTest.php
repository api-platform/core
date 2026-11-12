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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DoctrineDiscriminated\DoctrineDiscriminatedCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DoctrineDiscriminated\DoctrineDiscriminatedSportsCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DoctrineDiscriminated\DoctrineDiscriminatedTruck;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DoctrineDiscriminated\DoctrineDiscriminatedVehicle;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Polymorphic resources declared through the Doctrine MongoDB ODM discriminator map only.
 */
final class DoctrineDiscriminatedDocumentTest extends ApiTestCase
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
        return [DoctrineDiscriminatedVehicle::class, DoctrineDiscriminatedCar::class];
    }

    protected function setUp(): void
    {
        if (!$this->isMongoDB()) {
            $this->markTestSkipped('MongoDB ODM only, see DoctrineDiscriminatedResourceTest.');
        }

        $this->recreateSchema([
            DoctrineDiscriminatedVehicle::class,
            DoctrineDiscriminatedCar::class,
            DoctrineDiscriminatedSportsCar::class,
            DoctrineDiscriminatedTruck::class,
        ]);

        $car = new DoctrineDiscriminatedCar();
        $car->name = 'Family car';
        $car->seats = 5;
        $car->vin = 'secret-vin';

        $truck = new DoctrineDiscriminatedTruck();
        $truck->name = 'Big truck';
        $truck->payload = 20000;

        $manager = $this->getManager();
        $manager->persist($car);
        $manager->persist($truck);
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
        $this->assertJsonContains(['@id' => '/doctrine_discriminated_vehicles/3', 'kind' => 'truck', 'payload' => 3500]);

        $manager = $this->getManager();
        $manager->clear();
        $this->assertInstanceOf(DoctrineDiscriminatedTruck::class, $manager->find(DoctrineDiscriminatedVehicle::class, 3));
    }
}
