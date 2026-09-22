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

#[GetCollection(
    uriTemplate: 'strict_query_parameters_collection',
    strictQueryParameterValidation: true,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    provider: [self::class, 'provider']
)]
class StrictParametersCollection
{
    public string $id = '1';

    public static function provider(): array
    {
        return [new self()];
    }
}
