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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6427;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;

#[ApiResource(
    provider: [self::class, 'provide'],
    graphQlOperations: [
        new Query(
            resolver: 'app.graphql.query_resolver.security_after_resolver',
            securityAfterResolver: "object.name == 'test'",
            name: 'get'
        ),
    ]
)]
class SecurityAfterResolver
{
    public function __construct(public ?string $id, public ?string $name)
    {
    }

    public static function provide()
    {
        return new self('1', '1');
    }
}
