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
    shortName: 'GoldenOutputDtoResource',
    operations: [
        new Get(
            uriTemplate: '/golden_output_dtos/{id}',
            name: 'golden_output_dto_get',
            output: GoldenOutputDto::class,
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenOutputDtoResource
{
    public ?int $id = null;

    public string $internal = '';

    public static function provide(): null
    {
        return null;
    }
}
