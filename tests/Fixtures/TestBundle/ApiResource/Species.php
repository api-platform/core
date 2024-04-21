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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource]
#[GetCollection(
    uriTemplate: '/species',
)]
#[Get(
    uriTemplate: '/species/{key}',
)]
final class Species
{
    #[ApiProperty(identifier: true)]
    public ?int $key = null;
    public ?string $kingdom = null;
    public ?string $phylum = null;
    public ?string $order = null;
    public ?string $family = null;
    public ?string $genus = null;
    public ?string $species = null;
}
