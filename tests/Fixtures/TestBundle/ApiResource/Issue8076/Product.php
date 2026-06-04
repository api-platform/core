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
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [],
    provider: [self::class, 'provide'],
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

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self|array
    {
        $product = new self(
            id: '1',
            name: 'a product',
            facility: new Facility(
                id: 'f1',
                name: 'a facility',
                variants: [
                    new Variant(sku: 'sku-1', on: true),
                    new Variant(sku: 'sku-2', on: false),
                ],
            ),
        );

        if ($operation instanceof CollectionOperationInterface) {
            return [$product];
        }

        return $product;
    }
}
