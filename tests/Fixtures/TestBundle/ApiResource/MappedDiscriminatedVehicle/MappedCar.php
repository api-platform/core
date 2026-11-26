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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedDiscriminatedVehicle;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedCar;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: DoctrineDiscriminatedCar::class)]
#[Map(target: DoctrineDiscriminatedCar::class)]
final class MappedCar extends MappedVehicle
{
    public int $seats = 4;
    public ?string $vin = null;
}
