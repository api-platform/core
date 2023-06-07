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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[
    ApiResource(
        shortName: 'PasswordResource',
        operations: [
            new Post(
                uriTemplate: '/password/reset',
                name: 'password_reset',
            ),
            new Post(
                uriTemplate: '/password/set',
                name: 'password_set',
            ),
            new Get(
                uriTemplate: '/password/reset/{token}',
                name: 'password_reset_token',
            ),
        ],
        routePrefix: '/users'
    )
]
/**
 * Test for issue #5235.
 */
class PasswordResource
{
    #[ApiProperty(identifier: true)]
    public ?string $token = null;
}
