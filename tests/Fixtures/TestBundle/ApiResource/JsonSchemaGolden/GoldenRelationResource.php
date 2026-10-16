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
    shortName: 'GoldenRelation',
    operations: [
        new Get(
            uriTemplate: '/golden_relations/{id}',
            name: 'golden_relation_get',
            normalizationContext: ['groups' => ['golden:read', 'golden:embed']],
            provider: [self::class, 'provide'],
        ),
        new Get(
            uriTemplate: '/golden_relations_noid/{id}',
            name: 'golden_relation_get_noid',
            normalizationContext: ['groups' => ['golden:noid']],
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenRelationResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['golden:read', 'golden:noid'])]
    public ?int $id = null;

    #[ApiProperty(readableLink: false)]
    #[Groups(['golden:read'])]
    public ?GoldenRelated $linked = null;

    #[ApiProperty(readableLink: true)]
    #[Groups(['golden:embed'])]
    public ?GoldenRelated $embedded = null;

    #[ApiProperty(readableLink: true, genId: false)]
    #[Groups(['golden:noid'])]
    public ?GoldenRelated $embeddedWithoutId = null;

    public static function provide(): null
    {
        return null;
    }
}
