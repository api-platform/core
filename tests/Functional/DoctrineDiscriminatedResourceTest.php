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
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Polymorphic resources declared through the Doctrine ORM discriminator map only.
 */
final class DoctrineDiscriminatedResourceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

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

    public function testGetItemOfAResourceSubtypeUsesItsOwnResource(): void
    {
        self::createClient()->request('GET', '/doctrine_discriminated_cars/3');

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/DoctrineDiscriminatedCar',
            '@id' => '/doctrine_discriminated_cars/3',
            '@type' => 'DoctrineDiscriminatedCar',
            'kind' => 'sportsCar',
            'id' => 3,
            'name' => 'Fast car',
            'seats' => 2,
            'topSpeed' => 320,
        ]);
    }

    public function testGetItemAppliesSubtypePropertySecurity(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $client->request('GET', '/doctrine_discriminated_cars/1');
        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/DoctrineDiscriminatedCar',
            '@id' => '/doctrine_discriminated_cars/1',
            '@type' => 'DoctrineDiscriminatedCar',
            'kind' => 'car',
            'id' => 1,
            'name' => 'Family car',
            'seats' => 5,
        ]);

        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));
        $client->request('GET', '/doctrine_discriminated_cars/1');
        $this->assertJsonContains(['vin' => 'secret-vin']);
    }

    public function testGetCollectionExposesEachSubtype(): void
    {
        $response = self::createClient()->request('GET', '/doctrine_discriminated_vehicles');

        $this->assertResponseIsSuccessful();
        $members = $response->toArray()['hydra:member'];
        $this->assertCount(3, $members);

        $this->assertSame('/doctrine_discriminated_cars/1', $members[0]['@id']);
        $this->assertSame('car', $members[0]['kind']);
        $this->assertSame(5, $members[0]['seats']);
        $this->assertArrayNotHasKey('vin', $members[0]);

        $this->assertSame('/doctrine_discriminated_vehicles/2', $members[1]['@id']);
        $this->assertSame('truck', $members[1]['kind']);
        $this->assertSame(20000, $members[1]['payload']);
        $this->assertArrayNotHasKey('seats', $members[1]);

        $this->assertSame('/doctrine_discriminated_cars/3', $members[2]['@id']);
        $this->assertSame('sportsCar', $members[2]['kind']);
        $this->assertSame(320, $members[2]['topSpeed']);
    }

    public function testGetItemInJsonApi(): void
    {
        $response = self::createClient()->request('GET', '/doctrine_discriminated_vehicles/2', ['headers' => ['Accept' => 'application/vnd.api+json']]);

        $this->assertResponseIsSuccessful();
        $attributes = $response->toArray()['data']['attributes'];
        $this->assertSame('truck', $attributes['kind']);
        $this->assertSame(20000, $attributes['payload']);
    }

    public function testGetItemInHal(): void
    {
        $response = self::createClient()->request('GET', '/doctrine_discriminated_vehicles/2', ['headers' => ['Accept' => 'application/hal+json']]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame('/doctrine_discriminated_vehicles/2', $data['_links']['self']['href']);
        $this->assertSame('truck', $data['kind']);
        $this->assertSame(20000, $data['payload']);
    }

    public function testPostCreatesANonResourceSubtype(): void
    {
        self::createClient()->request('POST', '/doctrine_discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Small truck', 'payload' => 3500],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            '@context' => '/contexts/DoctrineDiscriminatedVehicle',
            '@id' => '/doctrine_discriminated_vehicles/4',
            '@type' => 'DoctrineDiscriminatedVehicle',
            'kind' => 'truck',
            'id' => 4,
            'name' => 'Small truck',
            'payload' => 3500,
        ]);

        $truck = $this->findVehicle(4);
        $this->assertInstanceOf(DoctrineDiscriminatedTruck::class, $truck);
        $this->assertSame(3500, $truck->payload);
    }

    public function testPostCreatesAResourceSubtypeThroughTheParentResource(): void
    {
        self::createClient()->request('POST', '/doctrine_discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'car', 'name' => 'Van', 'seats' => 8, 'vin' => 'cannot be written anonymously'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['@id' => '/doctrine_discriminated_cars/4', 'kind' => 'car', 'seats' => 8]);

        $car = $this->findVehicle(4);
        $this->assertInstanceOf(DoctrineDiscriminatedCar::class, $car);
        $this->assertNotInstanceOf(DoctrineDiscriminatedSportsCar::class, $car);
        $this->assertNull($car->vin);
    }

    public function testPostCreatesAGrandchildThroughAnIntermediateResource(): void
    {
        self::createClient()->request('POST', '/doctrine_discriminated_cars', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'sportsCar', 'name' => 'Roadster', 'seats' => 2, 'topSpeed' => 250],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['@id' => '/doctrine_discriminated_cars/4', 'kind' => 'sportsCar', 'topSpeed' => 250]);
        $this->assertInstanceOf(DoctrineDiscriminatedSportsCar::class, $this->findVehicle(4));
    }

    public function testPostOnAConcreteIntermediateResourceDefaultsToItself(): void
    {
        self::createClient()->request('POST', '/doctrine_discriminated_cars', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'car', 'name' => 'Hatchback', 'seats' => 4],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $car = $this->findVehicle(4);
        $this->assertInstanceOf(DoctrineDiscriminatedCar::class, $car);
        $this->assertNotInstanceOf(DoctrineDiscriminatedSportsCar::class, $car);
    }

    public function testPostOnAnIntermediateResourceRejectsSiblingTypes(): void
    {
        self::createClient()->request('POST', '/doctrine_discriminated_cars', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Not a car', 'payload' => 1],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPostWithoutTypeIsRejected(): void
    {
        self::createClient()->request('POST', '/doctrine_discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Unknown'],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPostValidatesSubtypeConstraints(): void
    {
        self::createClient()->request('POST', '/doctrine_discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'car', 'name' => 'Broken', 'seats' => 0],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'seats']]]);
    }

    public function testPatchUpdatesSubtypeProperties(): void
    {
        self::createClient()->request('PATCH', '/doctrine_discriminated_vehicles/2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['payload' => 25000],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['kind' => 'truck', 'name' => 'Big truck', 'payload' => 25000]);
        $truck = $this->findVehicle(2);
        $this->assertInstanceOf(DoctrineDiscriminatedTruck::class, $truck);
        $this->assertSame(25000, $truck->payload);
    }

    public function testPatchCannotChangeTheType(): void
    {
        self::createClient()->request('PATCH', '/doctrine_discriminated_vehicles/2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['kind' => 'car', 'seats' => 3],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertInstanceOf(DoctrineDiscriminatedTruck::class, $this->findVehicle(2));
    }

    public function testPutReplacesSubtypeProperties(): void
    {
        self::createClient()->request('PUT', '/doctrine_discriminated_vehicles/2', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Renamed truck', 'payload' => 1000],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['kind' => 'truck', 'name' => 'Renamed truck', 'payload' => 1000]);

        $truck = $this->findVehicle(2);
        $this->assertInstanceOf(DoctrineDiscriminatedTruck::class, $truck);
        $this->assertSame('Renamed truck', $truck->name);
    }

    public function testDelete(): void
    {
        self::createClient()->request('DELETE', '/doctrine_discriminated_vehicles/2');

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull($this->findVehicle(2));
    }

    public function testJoinedInheritanceWithIntegerDiscriminatorValues(): void
    {
        $client = self::createClient();

        $client->request('GET', '/doctrine_discriminated_shapes/1');
        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/DoctrineDiscriminatedShape',
            '@id' => '/doctrine_discriminated_shapes/1',
            '@type' => 'DoctrineDiscriminatedShape',
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

        $client->request('PATCH', '/doctrine_discriminated_shapes/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['radius' => 4],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['shapeType' => '1', 'radius' => 4]);
    }

    public function testSerializerDiscriminatorMapTakesPrecedence(): void
    {
        $client = self::createClient();

        $client->request('GET', '/doctrine_discriminated_animals/1');
        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/DoctrineDiscriminatedAnimal',
            '@id' => '/doctrine_discriminated_animals/1',
            '@type' => 'DoctrineDiscriminatedAnimal',
            'species' => 'canine',
            'id' => 1,
            'name' => 'Rex',
            'goodBoy' => true,
        ]);

        // Only the subtypes of the serializer map can be created.
        $client->request('POST', '/doctrine_discriminated_animals', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['species' => 'cat', 'name' => 'Tom', 'lives' => 7],
        ]);
        $this->assertResponseStatusCodeSame(400);
        $client->request('POST', '/doctrine_discriminated_animals', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['discr' => 'cat', 'name' => 'Tom', 'lives' => 7],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testOpenApiDocumentsDoctrineSubtypes(): void
    {
        $json = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();
        $schemas = $json['components']['schemas'];

        $output = $this->findDefinition($schemas, $json['paths']['/doctrine_discriminated_vehicles/{id}']['get']['responses']['200']['content']['application/ld+json']['schema']['$ref']);
        $this->assertSame('kind', $output['discriminator']['propertyName']);
        $this->assertEqualsCanonicalizing([
            'car' => '#/components/schemas/DoctrineDiscriminatedVehicle.DoctrineDiscriminatedCar.jsonld',
            'sportsCar' => '#/components/schemas/DoctrineDiscriminatedVehicle.DoctrineDiscriminatedSportsCar.jsonld',
            'truck' => '#/components/schemas/DoctrineDiscriminatedVehicle.DoctrineDiscriminatedTruck.jsonld',
        ], $output['discriminator']['mapping']);
        $this->assertSame(['type' => 'string', 'enum' => ['truck']], $schemas['DoctrineDiscriminatedVehicle.DoctrineDiscriminatedTruck.jsonld']['properties']['kind']);
        $this->assertArrayHasKey('payload', $schemas['DoctrineDiscriminatedVehicle.DoctrineDiscriminatedTruck.jsonld']['properties']);

        $input = $this->findDefinition($schemas, $json['paths']['/doctrine_discriminated_vehicles']['post']['requestBody']['content']['application/ld+json']['schema']['$ref']);
        $this->assertSame('kind', $input['discriminator']['propertyName']);
        $this->assertArrayHasKey('truck', $input['discriminator']['mapping']);

        $shape = $this->findDefinition($schemas, $json['paths']['/doctrine_discriminated_shapes/{id}']['get']['responses']['200']['content']['application/ld+json']['schema']['$ref']);
        $this->assertSame('shapeType', $shape['discriminator']['propertyName']);
        $this->assertEqualsCanonicalizing(['1', '2'], array_map('strval', array_keys($shape['discriminator']['mapping'])));

        $animal = $this->findDefinition($schemas, $json['paths']['/doctrine_discriminated_animals/{id}']['get']['responses']['200']['content']['application/ld+json']['schema']['$ref']);
        $this->assertSame([
            'propertyName' => 'species',
            'mapping' => ['canine' => '#/components/schemas/DoctrineDiscriminatedAnimal.DoctrineDiscriminatedDog.jsonld'],
        ], $animal['discriminator']);
    }

    private function findVehicle(int $id): ?DoctrineDiscriminatedVehicle
    {
        $manager = $this->getManager();
        $manager->clear();

        return $manager->find(DoctrineDiscriminatedVehicle::class, $id);
    }

    /**
     * @param array<string, array<string, mixed>> $schemas
     *
     * @return array<string, mixed>
     */
    private function findDefinition(array $schemas, string $ref): array
    {
        $definition = $schemas[substr($ref, \strlen('#/components/schemas/'))];

        // JSON-LD output wraps the definition in an allOf with the hydra base schema.
        foreach ($definition['allOf'] ?? [] as $part) {
            if (isset($part['discriminator'])) {
                return $part;
            }
        }

        return $definition;
    }
}
