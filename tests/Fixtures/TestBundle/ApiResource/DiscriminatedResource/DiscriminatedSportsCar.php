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

use ApiPlatform\Metadata\ApiProperty;

/**
 * Not declared in the discriminator map: it must be exposed exactly as a DiscriminatedCar.
 */
final class DiscriminatedSportsCar extends DiscriminatedCar
{
    #[ApiProperty(readable: false)]
    public string $name = '';

    public ?string $topSpeed = null;
}
