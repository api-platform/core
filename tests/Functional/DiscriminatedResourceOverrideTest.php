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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedCar;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedSportsCar;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedTruck;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedVehicle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedVehicleStore;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Subtypes of a discriminated resource overriding inherited properties.
 */
final class DiscriminatedResourceOverrideTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DiscriminatedVehicle::class];
    }

    protected function setUp(): void
    {
        $car = new DiscriminatedCar();
        $car->id = 1;
        $car->name = 'Beetle';
        $car->plate = 'AB-123';

        $truck = new DiscriminatedTruck();
        $truck->id = 2;
        $truck->name = 'Actros';
        $truck->plate = 'TR-999';
        $truck->payload = 18000;

        $sportsCar = new DiscriminatedSportsCar();
        $sportsCar->id = 3;
        $sportsCar->name = 'Carrera';
        $sportsCar->plate = 'SP-911';
        $sportsCar->topSpeed = '300';

        DiscriminatedVehicleStore::$vehicles = [1 => $car, 2 => $truck, 3 => $sportsCar];
    }

    protected function tearDown(): void
    {
        DiscriminatedVehicleStore::$vehicles = [];

        parent::tearDown();
    }

    public function testOverriddenPropertyMetadataIsReadFromTheSubtype(): void
    {
        $client = self::createClient();

        $car = $client->request('GET', '/discriminated_vehicles/1')->toArray();
        $this->assertSame('AB-123', $car['plate']);
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedVehicle::class);

        // The truck overrides "plate" with a security expression.
        $truck = $client->request('GET', '/discriminated_vehicles/2')->toArray();
        $this->assertSame('truck', $truck['kind']);
        $this->assertSame(18000, $truck['payload']);
        $this->assertArrayNotHasKey('plate', $truck);
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedVehicle::class);
    }

    public function testUnmappedSubclassIsExposedThroughItsMappedParentContract(): void
    {
        $response = self::createClient()->request('GET', '/discriminated_vehicles/3');

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $this->assertSame('car', $json['kind']);
        // Overrides declared on an unmapped subclass are not part of the documented contract.
        $this->assertSame('Carrera', $json['name']);
        $this->assertArrayNotHasKey('topSpeed', $json);
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedVehicle::class);
    }

    public function testOverriddenWritabilityIsEnforced(): void
    {
        self::createClient()->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'car', 'name' => 'Golf', 'plate' => 'not writable on cars'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertNull(DiscriminatedVehicleStore::$vehicles[4]->plate);
    }

    public function testOverriddenSecurityIsEnforcedOnWrite(): void
    {
        self::createClient()->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Scania', 'plate' => 'admins only'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertNull(DiscriminatedVehicleStore::$vehicles[4]->plate);
    }

    public function testOverriddenConstraintsAreValidated(): void
    {
        $client = self::createClient();

        $client->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'MAN'],
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'name']]]);

        $client->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'car', 'name' => 'Kia'],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testNarrowedRelationAcceptsTheNarrowedType(): void
    {
        self::createClient()->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'car', 'name' => 'Polo', 'tows' => '/discriminated_vehicles/1'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['tows' => '/discriminated_vehicles/1']);
        $this->assertSame(DiscriminatedVehicleStore::$vehicles[1], DiscriminatedVehicleStore::$vehicles[4]->tows);
    }

    public function testNarrowedRelationRejectsAnIriOfAnotherSubtype(): void
    {
        self::createClient()->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'car', 'name' => 'Polo', 'tows' => '/discriminated_vehicles/2'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertCount(3, DiscriminatedVehicleStore::$vehicles);
    }

    public function testNarrowedRelationRejectsAnIriOfAnotherSubtypeOnPatch(): void
    {
        self::createClient()->request('PATCH', '/discriminated_vehicles/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['tows' => '/discriminated_vehicles/2'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertNull(DiscriminatedVehicleStore::$vehicles[1]->tows);
    }

    public function testInheritedRelationAcceptsAnySubtype(): void
    {
        self::createClient()->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Volvo FH', 'tows' => '/discriminated_vehicles/1'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(DiscriminatedVehicleStore::$vehicles[1], DiscriminatedVehicleStore::$vehicles[4]->tows);
    }

    public function testCovariantGetterNarrowsTheReadType(): void
    {
        $trailer = new DiscriminatedTruck();
        $trailer->id = 5;
        $trailer->name = 'Trailer truck';
        DiscriminatedVehicleStore::$vehicles[5] = $trailer;
        DiscriminatedVehicleStore::$vehicles[2]->setTrailer($trailer);
        $client = self::createClient();

        $json = $client->request('GET', '/discriminated_vehicles/2')->toArray();
        $this->assertSame(4, $json['rating']);
        $this->assertSame('/discriminated_vehicles/5', $json['trailer']);
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedVehicle::class);

        $json = $client->request('GET', '/discriminated_vehicles/1')->toArray();
        $this->assertSame(4.5, $json['rating']);
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedVehicle::class);
    }

    public function testCovariantGetterAcceptsTheNarrowedTypeOnWrite(): void
    {
        $truck = new DiscriminatedTruck();
        $truck->id = 5;
        $truck->name = 'Trailer truck';
        DiscriminatedVehicleStore::$vehicles[5] = $truck;

        self::createClient()->request('PATCH', '/discriminated_vehicles/2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['trailer' => '/discriminated_vehicles/5'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['trailer' => '/discriminated_vehicles/5']);
        $this->assertSame($truck, DiscriminatedVehicleStore::$vehicles[2]->getTrailer());
    }

    /**
     * A setter parameter cannot be narrowed (contravariance), so the write type is the inherited one:
     * the narrowing of the covariant getter is enforced by validation.
     */
    public function testCovariantGetterIsEnforcedOnWriteThroughValidation(): void
    {
        self::createClient()->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Volvo FH', 'trailer' => '/discriminated_vehicles/1'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'trailer']]]);
        $this->assertCount(3, DiscriminatedVehicleStore::$vehicles);
    }

    public function testInheritedGetterAcceptsAnySubtypeOnWrite(): void
    {
        self::createClient()->request('PATCH', '/discriminated_vehicles/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['trailer' => '/discriminated_vehicles/2'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(DiscriminatedVehicleStore::$vehicles[2], DiscriminatedVehicleStore::$vehicles[1]->getTrailer());
    }

    public function testSchemaDocumentsCovariantReturnTypes(): void
    {
        $schemas = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray()['components']['schemas'];

        $this->assertSame('integer', $schemas['DiscriminatedVehicle.DiscriminatedTruck.jsonld']['properties']['rating']['type']);
        $this->assertSame([['type' => 'number'], ['type' => 'integer']], $schemas['DiscriminatedVehicle.DiscriminatedCar.jsonld']['properties']['rating']['anyOf']);
        $this->assertSame([['type' => 'number'], ['type' => 'integer']], $schemas['DiscriminatedVehicle.jsonld']['allOf'][1]['properties']['rating']['anyOf']);
    }

    public function testSchemaDocumentsOverriddenProperties(): void
    {
        $json = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();
        $schemas = $json['components']['schemas'];

        // Overridden writability: "plate" is read-only on cars, but still writable on trucks.
        $this->assertTrue($schemas['DiscriminatedVehicle.DiscriminatedCar']['properties']['plate']['readOnly']);
        $this->assertArrayNotHasKey('readOnly', $schemas['DiscriminatedVehicle.DiscriminatedTruck']['properties']['plate']);
        $this->assertArrayNotHasKey('readOnly', $schemas['DiscriminatedVehicle']['properties']['plate']);
        $this->assertArrayHasKey('payload', $schemas['DiscriminatedVehicle.DiscriminatedTruck']['properties']);

        // Overridden constraints are documented on the subtype only.
        $this->assertSame(5, $schemas['DiscriminatedVehicle.DiscriminatedTruck']['properties']['name']['minLength']);
        $this->assertArrayNotHasKey('minLength', $schemas['DiscriminatedVehicle.DiscriminatedCar']['properties']['name']);

        // Unmapped subclasses are never documented.
        $this->assertArrayNotHasKey('DiscriminatedVehicle.DiscriminatedSportsCar', $schemas);
        $this->assertArrayNotHasKey('DiscriminatedVehicle.DiscriminatedSportsCar.jsonld', $schemas);
    }
}
