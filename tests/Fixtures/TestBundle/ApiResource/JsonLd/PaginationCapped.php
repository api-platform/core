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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\State\JsonLdPaginationCappedProvider;

#[ApiResource(
    shortName: 'JsonLdPaginationCapped',
    paginationItemsPerPage: 3,
    paginationMaximumItemsPerPage: 30,
    paginationClientItemsPerPage: true,
    paginationClientEnabled: true,
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_pagination_capped',
            provider: JsonLdPaginationCappedProvider::class,
        ),
    ],
)]
class PaginationCapped
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
