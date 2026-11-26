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

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedTruck;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: DoctrineDiscriminatedTruck::class)]
#[Map(target: DoctrineDiscriminatedTruck::class)]
final class MappedTruck extends MappedVehicle
{
    public int $payload = 0;
}
