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
    shortName: 'GoldenMultiResourceOwner',
    operations: [
        new Get(
            uriTemplate: '/golden_multi_resource_owners/{id}',
            name: 'golden_multi_resource_owner_get',
            normalizationContext: ['groups' => ['golden:read']],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/golden_multi_resource_owners',
            name: 'golden_multi_resource_owner_post',
            normalizationContext: ['groups' => ['golden:read']],
            denormalizationContext: ['groups' => ['golden:write']],
            processor: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenMultiResourceOwner
{
    #[ApiProperty(identifier: true)]
    #[Groups(['golden:read'])]
    public ?int $id = null;

    #[ApiProperty(readableLink: true, writableLink: true)]
    #[Groups(['golden:read', 'golden:write'])]
    public ?GoldenMultiResourceEntity $entity = null;

    public static function provide(): null
    {
        return null;
    }
}
