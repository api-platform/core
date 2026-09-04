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
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: 'api_catalog_resources', provider: [self::class, 'provideCollection']),
    ],
    graphQlOperations: []
)]
class ApiCatalogResource
{
    public int $id;

    /**
     * @return self[]
     */
    public static function provideCollection(): array
    {
        $s = new self();
        $s->id = 1;

        return [$s];
    }
}
