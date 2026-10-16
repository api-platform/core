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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    shortName: 'GoldenDescribedAlias',
    description: 'A described related resource.',
    types: ['https://schema.org/Thing'],
    operations: [
        new Get(
            uriTemplate: '/golden_described_related/{id}',
            name: 'golden_described_related_get',
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenDescribedRelated
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $label = '';

    public static function provide(): null
    {
        return null;
    }
}
