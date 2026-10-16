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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'GoldenInput',
    operations: [
        new Post(
            uriTemplate: '/golden_inputs',
            name: 'golden_input_post',
            normalizationContext: ['groups' => ['golden:read']],
            denormalizationContext: ['groups' => ['golden:write']],
            processor: [self::class, 'process'],
        ),
        new Patch(
            uriTemplate: '/golden_inputs/{id}',
            name: 'golden_input_patch',
            normalizationContext: ['groups' => ['golden:read']],
            denormalizationContext: ['groups' => ['golden:write']],
            provider: [self::class, 'process'],
            processor: [self::class, 'process'],
        ),
        new Put(
            uriTemplate: '/golden_inputs_put/{id}',
            name: 'golden_input_put_nonstandard',
            normalizationContext: ['groups' => ['golden:read']],
            denormalizationContext: ['groups' => ['golden:write']],
            extraProperties: ['standard_put' => false],
            provider: [self::class, 'process'],
            processor: [self::class, 'process'],
        ),
    ],
)]
final class GoldenInputResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['golden:read'])]
    public ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['golden:read', 'golden:write'])]
    public string $title = '';

    #[Groups(['golden:read'])]
    public ?string $createdAt = null;

    #[ApiProperty(writable: false)]
    #[Groups(['golden:write'])]
    public ?string $computedLabel = null;

    #[ApiProperty(readable: false)]
    #[Groups(['golden:write'])]
    public ?string $password = null;

    public static function process(): null
    {
        return null;
    }
}
