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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedBike;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedCar;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedMountainBike;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedSportsCar;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedTruck;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedVehicle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedVehicleStore;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Polymorphic resources declared through a serializer discriminator map.
 */
final class DiscriminatedResourceTest extends ApiTestCase
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
        $sportsCar->topSpeed = '300';

        $mountainBike = new DiscriminatedMountainBike();
        $mountainBike->id = 4;
        $mountainBike->name = 'Rocky';
        $mountainBike->gears = 21;
        $mountainBike->suspension = 'full';

        $bike = new DiscriminatedBike();
        $bike->id = 5;
        $bike->name = 'Fixie';

        DiscriminatedVehicleStore::$vehicles = [1 => $car, 2 => $truck, 3 => $sportsCar, 4 => $mountainBike, 5 => $bike];
    }

    protected function tearDown(): void
    {
        DiscriminatedVehicleStore::$vehicles = [];

        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function formatProvider(): iterable
    {
        yield 'jsonld' => ['application/ld+json', 'jsonld'];
        yield 'json' => ['application/json', 'json'];
        yield 'jsonapi' => ['application/vnd.api+json', 'jsonapi'];
        yield 'hal' => ['application/hal+json', 'jsonhal'];
    }

    #[DataProvider('formatProvider')]
    public function testGetItemExposesSubtypeProperties(string $accept, string $format): void
    {
        $json = self::createClient()->request('GET', '/discriminated_vehicles/2', ['headers' => ['Accept' => $accept]])->toArray();

        $this->assertResponseIsSuccessful();
        $attributes = $json['data']['attributes'] ?? $json;
        $this->assertSame('truck', $attributes['kind']);
        $this->assertSame('Actros', $attributes['name']);
        $this->assertSame(18000, $attributes['payload']);
        // Subtype property security
        $this->assertArrayNotHasKey('plate', $attributes);
        if ('jsonhal' !== $format) {
            $this->assertMatchesResourceItemJsonSchema(DiscriminatedVehicle::class, format: $format);
        }
    }

    /**
     * @return iterable<string, array{int, array<string, mixed>, list<string>}>
     */
    public static function typeProvider(): iterable
    {
        yield 'nested map' => [4, ['@type' => 'DiscriminatedMountainBike', 'kind' => 'bike', 'frame' => 'mountain', 'gears' => 21, 'suspension' => 'full'], []];
        yield 'class declared in its own nested map' => [5, ['@type' => 'DiscriminatedBike', 'kind' => 'bike', 'frame' => 'bike', 'gears' => 1], ['suspension']];
        yield 'unmapped subclass exposed as its mapped parent' => [3, ['@type' => 'DiscriminatedCar', 'kind' => 'car', 'name' => 'Carrera'], ['topSpeed']];
    }

    /**
     * @param array<string, mixed> $expected
     * @param list<string>         $absent
     */
    #[DataProvider('typeProvider')]
    public function testGetItemExposesTheTypeOfEveryMap(int $id, array $expected, array $absent): void
    {
        $json = self::createClient()->request('GET', '/discriminated_vehicles/'.$id)->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains($expected);
        foreach ($absent as $property) {
            $this->assertArrayNotHasKey($property, $json);
        }
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedVehicle::class);
    }

    /**
     * The allowed attributes are cached per cache key, subtype property security must make that key unsafe
     * or the attributes allowed for a user leak to the next ones in long-running processes.
     */
    public function testSubtypePropertySecurityIsNotCachedAcrossUsers(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        foreach ([true, false, true] as $admin) {
            $client->getCookieJar()->clear();
            if ($admin) {
                $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));
            }

            $json = $client->request('GET', '/discriminated_vehicles/2', ['headers' => ['Accept' => 'application/json']])->toArray();
            $this->assertSame($admin ? 'TR-999' : null, $json['plate'] ?? null);
        }
    }

    public function testGetCollectionExposesEachSubtype(): void
    {
        $members = self::createClient()->request('GET', '/discriminated_vehicles')->toArray()['hydra:member'];

        $this->assertResponseIsSuccessful();
        $this->assertSame(['car', 'truck', 'car', 'bike', 'bike'], array_column($members, 'kind'));
        $this->assertSame(['DiscriminatedCar', 'DiscriminatedTruck', 'DiscriminatedCar', 'DiscriminatedMountainBike', 'DiscriminatedBike'], array_column($members, '@type'));
        $this->assertSame(18000, $members[1]['payload']);
        $this->assertArrayNotHasKey('payload', $members[0]);
        $this->assertMatchesResourceCollectionJsonSchema(DiscriminatedVehicle::class);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string}>
     */
    public static function postProvider(): iterable
    {
        yield 'subtype' => [['kind' => 'truck', 'name' => 'Scania', 'payload' => 12000], DiscriminatedTruck::class];
        yield 'nested subtype' => [['kind' => 'bike', 'frame' => 'mountain', 'name' => 'Trek', 'suspension' => 'hardtail'], DiscriminatedMountainBike::class];
        yield 'class declared in its own nested map' => [['kind' => 'bike', 'frame' => 'bike', 'name' => 'Brompton', 'gears' => 3], DiscriminatedBike::class];
    }

    /**
     * @param array<string, mixed> $payload
     * @param class-string         $class
     */
    #[DataProvider('postProvider')]
    public function testPostCreatesTheMostSpecificSubtype(array $payload, string $class): void
    {
        self::createClient()->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $payload,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['@id' => '/discriminated_vehicles/6', '@type' => substr($class, strrpos($class, '\\') + 1)] + $payload);
        $this->assertSame($class, DiscriminatedVehicleStore::$vehicles[6]::class);
    }

    public function testOverriddenPropertyMetadataIsEnforcedOnWrite(): void
    {
        $client = self::createClient();

        // "plate" is not writable on cars
        $client->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'car', 'name' => 'Golf', 'plate' => 'not writable'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertNull(DiscriminatedVehicleStore::$vehicles[6]->plate);

        // "plate" is restricted to admins on trucks
        $client->request('POST', '/discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Scania', 'plate' => 'admins only'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertNull(DiscriminatedVehicleStore::$vehicles[7]->plate);
    }

    /**
     * @return iterable<string, array{string, string, array<string, mixed>}>
     */
    public static function invalidPayloadProvider(): iterable
    {
        yield 'no type' => ['POST', '/discriminated_vehicles', ['name' => 'Untyped']];
        yield 'unknown type' => ['POST', '/discriminated_vehicles', ['kind' => 'plane', 'name' => 'Unknown']];
        yield 'no nested type' => ['POST', '/discriminated_vehicles', ['kind' => 'bike', 'name' => 'Untyped bike']];
        yield 'relation narrowed by the subtype' => ['POST', '/discriminated_vehicles', ['kind' => 'car', 'name' => 'Polo', 'tows' => '/discriminated_vehicles/2']];
        yield 'type change' => ['PATCH', '/discriminated_vehicles/1', ['kind' => 'truck', 'payload' => 1]];
        yield 'nested type change' => ['PATCH', '/discriminated_vehicles/4', ['frame' => 'bike']];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidPayloadProvider')]
    public function testInvalidPayloadIsRejected(string $method, string $uri, array $payload): void
    {
        $classes = array_map(static fn (DiscriminatedVehicle $vehicle): string => $vehicle::class, DiscriminatedVehicleStore::$vehicles);

        self::createClient()->request($method, $uri, [
            'headers' => ['Content-Type' => 'PATCH' === $method ? 'application/merge-patch+json' : 'application/ld+json'],
            'json' => $payload,
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame($classes, array_map(static fn (DiscriminatedVehicle $vehicle): string => $vehicle::class, DiscriminatedVehicleStore::$vehicles));
    }

    /**
     * @return iterable<string, array{string, string, array<string, mixed>, string}>
     */
    public static function constraintViolationProvider(): iterable
    {
        yield 'subtype constraint on POST' => ['POST', '/discriminated_vehicles', ['kind' => 'truck', 'name' => 'MAN'], 'name'];
        yield 'subtype constraint on PATCH' => ['PATCH', '/discriminated_vehicles/2', ['name' => 'MAN'], 'name'];
        // A setter parameter cannot be narrowed (contravariance): the covariant getter is enforced by validation
        yield 'covariant getter' => ['POST', '/discriminated_vehicles', ['kind' => 'truck', 'name' => 'Volvo FH', 'trailer' => '/discriminated_vehicles/1'], 'trailer'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('constraintViolationProvider')]
    public function testSubtypeConstraintsAreValidated(string $method, string $uri, array $payload, string $propertyPath): void
    {
        self::createClient()->request($method, $uri, [
            'headers' => ['Content-Type' => 'PATCH' === $method ? 'application/merge-patch+json' : 'application/ld+json'],
            'json' => $payload,
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => $propertyPath]]]);
    }

    public function testPatchUpdatesSubtypeProperties(): void
    {
        self::createClient()->request('PATCH', '/discriminated_vehicles/2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['kind' => 'truck', 'payload' => 20000],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['kind' => 'truck', 'name' => 'Actros', 'payload' => 20000]);
        $this->assertInstanceOf(DiscriminatedTruck::class, DiscriminatedVehicleStore::$vehicles[2]);
    }

    public function testPutReplacesTheResourceWithTheGivenSubtype(): void
    {
        self::createClient()->request('PUT', '/discriminated_vehicles/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'truck', 'name' => 'Unimog', 'payload' => 5000],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['@id' => '/discriminated_vehicles/1', '@type' => 'DiscriminatedTruck', 'kind' => 'truck', 'payload' => 5000]);
        $this->assertInstanceOf(DiscriminatedTruck::class, DiscriminatedVehicleStore::$vehicles[1]);
    }

    public function testOpenApiDocumentsSubtypes(): void
    {
        $json = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();
        $schemas = $json['components']['schemas'];

        $output = $schemas['DiscriminatedVehicle.jsonld']['allOf'][1] ?? $schemas['DiscriminatedVehicle.jsonld'];
        $this->assertSame([
            'propertyName' => 'kind',
            'mapping' => [
                'car' => '#/components/schemas/DiscriminatedVehicle.DiscriminatedCar.jsonld',
                'truck' => '#/components/schemas/DiscriminatedVehicle.DiscriminatedTruck.jsonld',
                'bike' => '#/components/schemas/DiscriminatedVehicle.DiscriminatedBike.jsonld',
            ],
        ], $output['discriminator']);
        $this->assertCount(3, $output['oneOf']);
        $this->assertArrayNotHasKey('properties', $output);

        $truck = $schemas['DiscriminatedVehicle.DiscriminatedTruck.jsonld'];
        $this->assertSame(['type' => 'string', 'enum' => ['truck']], $truck['properties']['kind']);
        $this->assertArrayHasKey('payload', $truck['properties']);
        $this->assertSame(5, $truck['properties']['name']['minLength']);
        $this->assertArrayNotHasKey('minLength', $schemas['DiscriminatedVehicle.DiscriminatedCar.jsonld']['properties']['name']);

        // The nested map lists the bike itself: an instance of the bike is described by an inline branch
        $bike = $schemas['DiscriminatedVehicle.DiscriminatedBike.jsonld'];
        $this->assertSame('frame', $bike['discriminator']['propertyName']);
        $this->assertSame(['type' => 'string', 'enum' => ['bike']], $bike['oneOf'][0]['properties']['frame']);
        $this->assertSame(['$ref' => '#/components/schemas/DiscriminatedVehicle.DiscriminatedMountainBike.jsonld'], $bike['oneOf'][1]);

        // Unmapped subclasses are never documented
        $this->assertArrayNotHasKey('DiscriminatedVehicle.DiscriminatedSportsCar.jsonld', $schemas);
    }

    public function testHydraDocumentsSubtypesAsSubclasses(): void
    {
        $client = self::createClient();

        $classes = array_column($client->request('GET', '/docs.jsonld')->toArray()['hydra:supportedClass'], null, '@id');
        $truck = $classes['#DiscriminatedTruck'];
        $this->assertSame('#DiscriminatedVehicle', $truck['subClassOf']);
        $this->assertSame(['#DiscriminatedTruck/payload'], array_column(array_column($truck['hydra:supportedProperty'], 'hydra:property'), '@id'));
        $this->assertSame('#DiscriminatedBike', $classes['#DiscriminatedMountainBike']['subClassOf']);
        $this->assertArrayNotHasKey('#DiscriminatedSportsCar', $classes);

        $context = $client->request('GET', '/contexts/DiscriminatedVehicle')->toArray()['@context'];
        $this->assertSame('DiscriminatedVehicle/name', $context['name']);
        $this->assertSame('DiscriminatedTruck/payload', $context['payload']);
        $this->assertSame('DiscriminatedMountainBike/suspension', $context['suspension']);
    }
}
