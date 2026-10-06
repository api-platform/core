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
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Polymorphic resources declared through the Doctrine MongoDB ODM discriminator map only.
 */
final class DoctrineDiscriminatedDocumentTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

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

    public function testGetItemOfANonResourceSubtypeExposesItsProperties(): void
    {
        self::createClient()->request('GET', '/doctrine_discriminated_vehicles/2');

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/DoctrineDiscriminatedVehicle',
            '@id' => '/doctrine_discriminated_vehicles/2',
            '@type' => 'DoctrineDiscriminatedVehicle',
            'kind' => 'truck',
            'id' => 2,
            'name' => 'Big truck',
            'payload' => 20000,
        ]);
    }

    public function testGetItemAppliesSubtypePropertySecurity(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $json = $client->request('GET', '/doctrine_discriminated_vehicles')->toArray();
        $this->assertSame('car', $json['hydra:member'][0]['kind']);
        $this->assertSame(5, $json['hydra:member'][0]['seats']);
        $this->assertArrayNotHasKey('vin', $json['hydra:member'][0]);

        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));
        $client->request('GET', '/doctrine_discriminated_cars/1');
        $this->assertJsonContains(['vin' => 'secret-vin']);
    }

    public function testPostCreatesTheMappedSubtypes(): void
    {
        $client = self::createClient();

        $client->request('POST', '/doctrine_discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Small truck', 'payload' => 3500],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['@id' => '/doctrine_discriminated_vehicles/3', 'kind' => 'truck', 'payload' => 3500]);

        $client->request('POST', '/doctrine_discriminated_cars', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'sportsCar', 'name' => 'Roadster', 'seats' => 2, 'topSpeed' => 250],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['@id' => '/doctrine_discriminated_cars/4', 'kind' => 'sportsCar', 'topSpeed' => 250]);

        $manager = $this->getManager();
        $manager->clear();
        $this->assertInstanceOf(DoctrineDiscriminatedTruck::class, $manager->find(DoctrineDiscriminatedVehicle::class, 3));
        $this->assertInstanceOf(DoctrineDiscriminatedSportsCar::class, $manager->find(DoctrineDiscriminatedVehicle::class, 4));
    }

    public function testPostWithoutTypeIsRejected(): void
    {
        self::createClient()->request('POST', '/doctrine_discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Unknown'],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPatchUpdatesSubtypeProperties(): void
    {
        $client = self::createClient();

        $client->request('PATCH', '/doctrine_discriminated_vehicles/2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['payload' => 25000],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['kind' => 'truck', 'payload' => 25000]);

        $client->request('PATCH', '/doctrine_discriminated_vehicles/2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['kind' => 'car', 'seats' => 3],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testOpenApiDocumentsDoctrineSubtypes(): void
    {
        $json = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();

        $ref = $json['paths']['/doctrine_discriminated_vehicles/{id}']['get']['responses']['200']['content']['application/ld+json']['schema']['$ref'];
        $definition = $json['components']['schemas'][substr($ref, \strlen('#/components/schemas/'))];
        $output = $definition['allOf'][1] ?? $definition;

        $this->assertSame('kind', $output['discriminator']['propertyName']);
        $this->assertEqualsCanonicalizing(['car', 'sportsCar', 'truck'], array_keys($output['discriminator']['mapping']));
    }
}
