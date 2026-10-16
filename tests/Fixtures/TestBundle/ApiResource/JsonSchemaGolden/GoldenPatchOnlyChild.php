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
use ApiPlatform\Metadata\Patch;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GoldenPatchOnlyChild',
    operations: [
        new Get(
            uriTemplate: '/golden_patch_only_children/{id}',
            name: 'golden_patch_only_child_get',
            normalizationContext: ['groups' => ['golden:read']],
            provider: [self::class, 'provide'],
        ),
        new Patch(
            uriTemplate: '/golden_patch_only_children/{id}',
            name: 'golden_patch_only_child_patch',
            normalizationContext: ['groups' => ['golden:read']],
            denormalizationContext: ['groups' => ['golden:write']],
            provider: [self::class, 'provide'],
            processor: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenPatchOnlyChild
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
