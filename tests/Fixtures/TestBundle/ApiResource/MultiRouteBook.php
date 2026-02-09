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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

/**
 * ApiResource with multiple routes to test entrypoint behavior.
 *
 * This resource demonstrates the issue where multiple ApiResource declarations
 * with different URIs result in the entrypoint advertising only the FIRST route,
 * even when it should advertise the public one.
 *
 * Current behavior (BUG):
 * - Admin route (/admin/multi_route_books) is declared FIRST
 * - Entrypoint advertises the admin route only
 * - Public route (/multi_route_books) works but is never advertised
 *
 * Expected behavior (AFTER FIX):
 * - Entrypoint should advertise the public route (/multi_route_books)
 * - Both routes should remain functional
 */
#[ApiResource(
    uriTemplate: '/admin/multi_route_books',
    operations: [
        new GetCollection(
            itemUriTemplate: '/admin/multi_route_books/{id}',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/admin/multi_route_books/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
#[ApiResource(
    uriTemplate: '/multi_route_books',
    operations: [
        new GetCollection(
            itemUriTemplate: '/multi_route_books/{id}',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/multi_route_books/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class MultiRouteBook
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $title;

    public string $isbn;

    public function __construct(int $id = 0, string $title = '', string $isbn = '')
    {
        $this->id = $id;
        $this->title = $title;
        $this->isbn = $isbn;
    }

    /**
     * Provider for GetCollection operations.
     *
     * @return array<MultiRouteBook>
     */
    public static function provideCollection(): array
    {
        return [
            new self(1, 'The API Platform Book', '978-1-491-904-75-1'),
            new self(2, 'GraphQL in Action', '978-1-617-29513-2'),
        ];
    }

    /**
     * Provider for Get operation (single item).
     */
    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        $id = (int) ($uriVariables['id'] ?? 1);
        $books = [
            1 => new self(1, 'The API Platform Book', '978-1-491-904-75-1'),
            2 => new self(2, 'GraphQL in Action', '978-1-617-29513-2'),
        ];

        return $books[$id] ?? $books[1];
    }
}
