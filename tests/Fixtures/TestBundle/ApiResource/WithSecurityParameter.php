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

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\QueryParameter;

#[GetCollection(
    uriTemplate: 'with_security_parameters_collection{._format}',
    parameters: [
        'name' => new QueryParameter(security: 'is_granted("ROLE_ADMIN")'),
        'auth' => new HeaderParameter(security: '"secured" == auth[0]'),
        'secret' => new QueryParameter(security: '"secured" == secret'),
    ],
    provider: [self::class, 'collectionProvider'],
)]
class WithSecurityParameter
{
    public static function collectionProvider()
    {
        return [new self()];
    }
}
