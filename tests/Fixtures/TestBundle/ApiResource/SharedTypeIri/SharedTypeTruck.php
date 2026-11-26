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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedTypeIri;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    operations: [new Get()],
    types: ['https://schema.org/Vehicle'],
    provider: [SharedTypeVehicle::class, 'provide'],
    processor: [SharedTypeVehicle::class, 'process'],
)]
final class SharedTypeTruck extends SharedTypeVehicle
{
}
