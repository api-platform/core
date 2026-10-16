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
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GoldenPartialChildrenOwner',
    operations: [
        new Get(
            uriTemplate: '/golden_partial_children_owners/{id}',
            name: 'golden_partial_children_owner_get',
            normalizationContext: ['groups' => ['golden:read']],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/golden_partial_children_owners',
            name: 'golden_partial_children_owner_post',
            normalizationContext: ['groups' => ['golden:read']],
            denormalizationContext: ['groups' => ['golden:write']],
            processor: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenPartialChildrenOwner
{
    #[ApiProperty(identifier: true)]
    #[Groups(['golden:read'])]
    public ?int $id = null;

    #[ApiProperty(required: true)]
    #[Groups(['golden:read', 'golden:write'])]
    public string $name = '';

    #[ApiProperty(readableLink: true, writableLink: true)]
    #[Groups(['golden:read', 'golden:write'])]
    public ?GoldenPatchOnlyChild $patchChild = null;

    #[ApiProperty(readableLink: true, writableLink: true)]
    #[Groups(['golden:read', 'golden:write'])]
    public ?GoldenNonStandardPutChild $putChild = null;

    public static function provide(): null
    {
        return null;
    }
}
