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
    shortName: 'GoldenRelated',
    operations: [
        new Get(
            uriTemplate: '/golden_related/{id}',
            name: 'golden_related_get',
            normalizationContext: ['groups' => ['golden:read']],
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenRelated
{
    #[ApiProperty(identifier: true)]
    #[Groups(['golden:read'])]
    public ?int $id = null;

    #[Groups(['golden:read', 'golden:embed', 'golden:noid'])]
    public string $label = '';

    public static function provide(): null
    {
        return null;
    }
}
