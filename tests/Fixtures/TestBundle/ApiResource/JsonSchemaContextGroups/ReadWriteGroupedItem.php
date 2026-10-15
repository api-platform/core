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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaContextGroups;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ReadWriteGroupedItem',
    operations: [
        new Get(uriTemplate: '/json_schema_context_groups/read_write_grouped_items/{id}', provider: [self::class, 'provide']),
        new Post(uriTemplate: '/json_schema_context_groups/read_write_grouped_items', processor: [self::class, 'process']),
    ],
    normalizationContext: ['groups' => ['rw:read']],
    denormalizationContext: ['groups' => ['rw:write']],
)]
class ReadWriteGroupedItem
{
    #[Groups(['rw:read'])]
    public ?int $id = null;

    #[Groups(['rw:read', 'rw:write'])]
    public ?string $title = null;

    #[Groups(['rw:read'])]
    public ?\DateTimeImmutable $createdAt = null;

    #[Groups(['rw:write'])]
    public ?string $secretToken = null;

    public static function provide(): null
    {
        return null;
    }

    public static function process(): null
    {
        return null;
    }
}
