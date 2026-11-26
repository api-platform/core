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
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ConvertedTypePropertyTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    protected static function getKernelClass(): string
    {
        return DiscriminatorMapAppKernel::class;
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ConvertedTypeVehicle::class];
    }

    protected function tearDown(): void
    {
        ConvertedTypeVehicleStore::$vehicles = [];

        parent::tearDown();
    }

    public function testJsonApiReadsTheNameConvertedTypeProperty(): void
    {
        $truck = new ConvertedTypeTruck();
        $truck->id = 1;
        $truck->name = 'Actros';
        ConvertedTypeVehicleStore::$vehicles[1] = $truck;

        $attributes = self::createClient()->request('GET', '/converted_type_vehicles/1', ['headers' => ['Accept' => 'application/vnd.api+json']])->toArray()['data']['attributes'];
        $this->assertSame('truck', $attributes['_type']);

        self::createClient()->request('POST', '/converted_type_vehicles', [
            'headers' => ['Content-Type' => 'application/vnd.api+json', 'Accept' => 'application/vnd.api+json'],
            'json' => ['data' => ['type' => 'ConvertedTypeVehicle', 'attributes' => ['_type' => 'truck', 'name' => 'Arocs', 'payload' => 7500]]],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(ConvertedTypeTruck::class, ConvertedTypeVehicleStore::$vehicles[2]::class);
    }

    public function testJsonApiRejectsAChangeOfTheNameConvertedTypeProperty(): void
    {
        $truck = new ConvertedTypeTruck();
        $truck->id = 1;
        $truck->name = 'Actros';
        ConvertedTypeVehicleStore::$vehicles[1] = $truck;

        self::createClient()->request('PUT', '/converted_type_vehicles/1', [
            'headers' => ['Content-Type' => 'application/vnd.api+json', 'Accept' => 'application/vnd.api+json'],
            'json' => ['data' => ['type' => 'ConvertedTypeVehicle', 'attributes' => ['_type' => 'car', 'name' => 'Golf']]],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Actros', ConvertedTypeVehicleStore::$vehicles[1]->name);
    }
}
