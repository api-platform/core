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

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: 'frame', mapping: [
    'bike' => DiscriminatedBike::class,
    'mountain' => DiscriminatedMountainBike::class,
])]
class DiscriminatedBike extends DiscriminatedVehicle
{
    public int $gears = 1;
}
