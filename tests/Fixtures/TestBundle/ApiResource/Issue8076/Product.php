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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8076;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;

#[ApiResource(
    operations: [],
    provider: ProductProvider::class,
    graphQlOperations: [
        new Query(),
        new QueryCollection(paginationEnabled: false),
    ],
)]
final class Product
{
    public function __construct(
        public string $id,
        public string $name,
        public Facility $facility,
    ) {
    }
}
