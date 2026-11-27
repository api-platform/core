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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GarageWithPolymorphicVehicle;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedCar;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedVehicle;

#[ApiResource(provider: [self::class, 'provide'])]
class Garage
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?DiscriminatedVehicle $vehicle = null;

    /** @var DiscriminatedVehicle[] */
    public array $vehicles = [];

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $car = new DiscriminatedCar();
        $car->id = 1;
        $car->name = 'Beetle';

        $garage = new self();
        $garage->id = 1;
        $garage->vehicle = $car;
        $garage->vehicles = [$car];

        return $garage;
    }
}
