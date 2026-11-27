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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;

#[ApiResource(
    operations: [new Get(), new Put(), new Patch()],
    types: ['https://schema.org/BusOrCoach'],
    provider: [DiscriminatedVehicle::class, 'provide'],
    processor: [DiscriminatedVehicle::class, 'process'],
)]
final class DiscriminatedBus extends DiscriminatedVehicle
{
    public int $seats = 0;
}
