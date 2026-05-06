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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonLdPostNoOutput',
    operations: [
        new Post(
            uriTemplate: '/jsonld_post_no_output',
            output: false,
            processor: [self::class, 'process'],
        ),
    ],
)]
class PostNoOutputResource
{
    public ?string $lorem = null;

    public ?string $ipsum = null;

    public static function process(self $data): self
    {
        return $data;
    }
}
