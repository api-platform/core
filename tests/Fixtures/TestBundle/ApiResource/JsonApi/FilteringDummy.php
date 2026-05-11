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
    shortName: 'JsonApiFilteringDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    paginationItemsPerPage: 3,
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_filtering_dummies',
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class FilteringDummy
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $name;

    public ?string $dummyDate;

    public function __construct(int $id, int $total = 30)
    {
        $this->id = $id;
        $this->name = "Dummy #{$id}";
        // Last dummy has null date — match behat's thereAreDummyObjectsWithDummyDate.
        $this->dummyDate = $id === $total ? null : \sprintf('2015-04-%02dT00:00:00+00:00', $id);
    }

    public static function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $items = array_map(static fn (int $i): self => new self($i), range(1, 30));
        $filters = $context['filters'] ?? [];

        if (isset($filters['name']) && '' !== $filters['name']) {
            $needle = strtolower((string) $filters['name']);
            $items = array_values(array_filter($items, static fn (self $r): bool => str_contains(strtolower($r->name), $needle)));
        }

        if (isset($filters['dummyDate']['after'])) {
            $threshold = new \DateTimeImmutable((string) $filters['dummyDate']['after']);
            $items = array_values(array_filter($items, static function (self $r) use ($threshold): bool {
                return null !== $r->dummyDate && new \DateTimeImmutable($r->dummyDate) >= $threshold;
            }));
        }

        $page = (int) ($filters['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }
        $itemsPerPage = 3;

        return new ArrayPaginator($items, ($page - 1) * $itemsPerPage, $itemsPerPage);
    }
}
