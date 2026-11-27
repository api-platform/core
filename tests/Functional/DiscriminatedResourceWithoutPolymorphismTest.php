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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ConvertedTypeProperty\ConvertedTypeTruck;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ConvertedTypeProperty\ConvertedTypeVehicle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ConvertedTypeProperty\ConvertedTypeVehicleStore;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedTypeIri\SharedTypeCar;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedTypeIri\SharedTypeVehicle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedTypeIri\SharedTypeVehicleStore;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DiscriminatedResourceWithoutPolymorphismTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ConvertedTypeVehicle::class, SharedTypeVehicle::class, SharedTypeCar::class];
    }

    protected function tearDown(): void
    {
        ConvertedTypeVehicleStore::$vehicles = [];
        SharedTypeVehicleStore::$vehicles = [];

        parent::tearDown();
    }

    public function testPutWithoutTheTypePropertyDoesNotKeepTheStoredClass(): void
    {
        $truck = new ConvertedTypeTruck();
        $truck->id = 1;
        $truck->name = 'Actros';
        ConvertedTypeVehicleStore::$vehicles[1] = $truck;

        self::createClient()->request('PUT', '/converted_type_vehicles/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Arocs'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('Actros', ConvertedTypeVehicleStore::$vehicles[1]->name);
    }

    public function testGetDoesNotEmitTheTypeProperty(): void
    {
        $truck = new ConvertedTypeTruck();
        $truck->id = 1;
        $truck->name = 'Actros';
        ConvertedTypeVehicleStore::$vehicles[1] = $truck;

        $json = self::createClient()->request('GET', '/converted_type_vehicles/1')->toArray();

        $this->assertArrayNotHasKey('type', $json);
    }

    public function testSubtypeResourceDoesNotDocumentNorEmitTheTypeProperty(): void
    {
        $car = new SharedTypeCar();
        $car->id = 1;
        $car->name = 'Golf';
        SharedTypeVehicleStore::$vehicles[1] = $car;

        $client = self::createClient();
        $this->assertArrayNotHasKey('kind', $client->request('GET', '/shared_type_cars/1')->toArray());

        $schemas = $client->request('GET', '/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']])->toArray()['components']['schemas'];
        $this->assertArrayNotHasKey('kind', $schemas['SharedTypeCar']['properties']);
    }
}
