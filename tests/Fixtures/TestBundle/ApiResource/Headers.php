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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    headers: ['Location' => '/foobar', 'Hello' => 'World'],
    status: 301,
    output: false,
    operations: [
        new Get(uriTemplate: 'redirect_to_foobar', provider: [self::class, 'provide']),
    ],
    graphQlOperations: []
)]
class Headers
{
    public int $id;

    public static function provide(): self
    {
        $s = new self();
        $s->id = 1;

        return $s;
    }
}
