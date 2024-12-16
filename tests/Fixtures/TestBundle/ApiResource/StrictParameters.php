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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;

#[Get(
    uriTemplate: 'strict_query_parameters',
    strictQueryParameterValidation: true,
    parameters: [
        'foo' => new QueryParameter(),
    ],
    provider: [self::class, 'provider']
)]
class StrictParameters
{
    public string $id;

    public static function provider()
    {
        return new self();
    }
}
