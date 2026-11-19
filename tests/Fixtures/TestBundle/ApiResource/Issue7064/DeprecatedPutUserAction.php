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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7064;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(),
    ],
    normalizationContext: ['groups' => ['issue7064_custom_read']],
    denormalizationContext: ['groups' => ['issue7064_custom_update']],
)]
class DeprecatedPutUserAction
{
    #[Groups(['issue7064_custom_update', 'issue7064_custom_read'])]
    #[ApiProperty(required: true)]
    public DeprecatedPutUser $user;
}
