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
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GoldenGroupedItem',
    operations: [
        new Get(
            uriTemplate: '/golden_grouped_items/{id}',
            name: 'golden_grouped_item_get',
            normalizationContext: ['groups' => ['golden:read']],
            denormalizationContext: ['groups' => ['golden:write']],
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenGroupedItem
{
    #[ApiProperty(identifier: true)]
    #[Groups(['golden:read'])]
    public ?int $id = null;

    #[Groups(['golden:read', 'golden:write'])]
    public string $title = '';

    #[Groups(['golden:read'])]
    public ?string $createdAt = null;

    #[Groups(['golden:write'])]
    public ?string $secretToken = null;

    public static function provide(): null
    {
        return null;
    }
}
