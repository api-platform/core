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
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GoldenNonStandardPutChild',
    operations: [
        new Get(
            uriTemplate: '/golden_non_standard_put_children/{id}',
            name: 'golden_non_standard_put_child_get',
            normalizationContext: ['groups' => ['golden:read']],
            provider: [self::class, 'provide'],
        ),
        new Put(
            uriTemplate: '/golden_non_standard_put_children/{id}',
            name: 'golden_non_standard_put_child_put',
            normalizationContext: ['groups' => ['golden:read']],
            denormalizationContext: ['groups' => ['golden:write']],
            extraProperties: ['standard_put' => false],
            provider: [self::class, 'provide'],
            processor: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenNonStandardPutChild
{
    #[ApiProperty(identifier: true)]
    #[Groups(['golden:read'])]
    public ?int $id = null;

    #[ApiProperty(required: true)]
    #[Groups(['golden:read', 'golden:write'])]
    public string $label = '';

    public static function provide(): null
    {
        return null;
    }
}
