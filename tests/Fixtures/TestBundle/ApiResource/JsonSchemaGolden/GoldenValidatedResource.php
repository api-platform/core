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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'GoldenValidated',
    operations: [
        new Post(
            uriTemplate: '/golden_validated',
            name: 'golden_validated_post',
            denormalizationContext: ['groups' => ['golden:write']],
            validationContext: ['groups' => ['golden:create']],
            processor: [self::class, 'process'],
        ),
    ],
)]
final class GoldenValidatedResource
{
    #[Assert\NotBlank(groups: ['golden:create'])]
    #[Assert\Length(max: 20, groups: ['golden:create'])]
    #[Groups(['golden:write'])]
    public string $name = '';

    #[Assert\NotBlank(groups: ['golden:other'])]
    #[Assert\Length(min: 3, groups: ['golden:other'])]
    #[Groups(['golden:write'])]
    public string $reference = '';

    public static function process(): null
    {
        return null;
    }
}
