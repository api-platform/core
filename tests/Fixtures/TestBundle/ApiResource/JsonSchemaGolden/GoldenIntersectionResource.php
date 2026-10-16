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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    shortName: 'GoldenIntersection',
    operations: [
        new Get(uriTemplate: '/golden_intersections/{id}', name: 'golden_intersection_get'),
    ],
)]
final class GoldenIntersectionResource
{
    public ?int $id = null;

    public GoldenActivable&GoldenTimestampable $library;
}
