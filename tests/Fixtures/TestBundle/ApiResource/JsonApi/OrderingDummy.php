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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;

#[ApiResource(
    shortName: 'JsonApiOrderingDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    paginationItemsPerPage: 30,
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_ordering_dummies',
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class OrderingDummy
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $name;

    public string $description;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->name = "Dummy #{$id}";
        // Even-id dummies share description "even"; odd dummies "odd".
        // Sorting by description,-id puts evens first (desc within group): 30, 28, 26...
        $this->description = 0 === $id % 2 ? 'even' : 'odd';
    }

    public static function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $items = array_map(static fn (int $i): self => new self($i), range(1, 30));
        $filters = $context['filters'] ?? [];
        $order = $filters['order'] ?? [];

        if ($order) {
            usort($items, static function (self $a, self $b) use ($order): int {
                foreach ($order as $field => $direction) {
                    $cmp = $a->{$field} <=> $b->{$field};
                    if ('desc' === strtolower((string) $direction)) {
                        $cmp = -$cmp;
                    }
                    if (0 !== $cmp) {
                        return $cmp;
                    }
                }

                return 0;
            });
        }

        return new ArrayPaginator($items, 0, \count($items));
    }
}
