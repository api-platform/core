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

#[ApiResource(
    shortName: 'GoldenStrictInput',
    operations: [
        new Post(
            uriTemplate: '/golden_strict_inputs',
            name: 'golden_strict_input_post',
            denormalizationContext: ['allow_extra_attributes' => false],
            processor: [self::class, 'process'],
        ),
    ],
)]
final class GoldenStrictInputResource
{
    public string $name = '';

    public ?int $quantity = null;

    public static function process(): null
    {
        return null;
    }
}
