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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ThrowOnNotFound;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new Post(
        uriTemplate: '/throw_on_not_found_feeders/{id}/feed',
        throwOnNotFound: true,
        provider: [Feeder::class, 'provide'],
        read: true,
    ),
    new Post(
        uriTemplate: '/throw_on_not_found_feeders/{id}/feed_default',
        provider: [Feeder::class, 'provide'],
        read: true,
    ),
])]
final class Feeder
{
    public ?int $id = null;

    public static function provide(): ?self
    {
        return null;
    }
}
