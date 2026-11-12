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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\IriDiscriminatedResource\IriDiscriminatedCar;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\IriDiscriminatedResource\IriDiscriminatedVehicle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\IriDiscriminatedResource\IriDiscriminatedVehicleStore;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class IriDiscriminatedResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [IriDiscriminatedVehicle::class];
    }

    protected function setUp(): void
    {
        $car = new IriDiscriminatedCar();
        $car->id = 1;
        $car->name = 'Beetle';

        IriDiscriminatedVehicleStore::$vehicles = [1 => $car];
    }

    protected function tearDown(): void
    {
        IriDiscriminatedVehicleStore::$vehicles = [];

        parent::tearDown();
    }

    /**
     * An IRI carries no type property: the relation must be retrieved from the IRI
     * instead of resolving its class from the discriminator map of the abstract type.
     */
    public function testPostAcceptsTheIriOfAnAbstractDiscriminatedResource(): void
    {
        self::createClient()->request('POST', '/iri_discriminated_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'car', 'name' => 'Golf', 'tows' => '/iri_discriminated_vehicles/1'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['tows' => '/iri_discriminated_vehicles/1']);
        $this->assertSame(IriDiscriminatedVehicleStore::$vehicles[1], IriDiscriminatedVehicleStore::$vehicles[2]->tows);
    }
}
