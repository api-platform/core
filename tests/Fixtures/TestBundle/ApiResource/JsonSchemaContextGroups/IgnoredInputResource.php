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
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'IgnoredInputResource',
    operations: [
        new Post(
            uriTemplate: '/json_schema_context_groups/ignored_input_resources',
            denormalizationContext: ['ignored_attributes' => ['internalNote']],
            processor: [self::class, 'process'],
        ),
    ],
)]
class IgnoredInputResource
{
    public ?string $name = null;

    public ?string $internalNote = null;

    public static function process(): null
    {
        return null;
    }
}
