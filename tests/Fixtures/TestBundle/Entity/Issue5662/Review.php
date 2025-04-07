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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5662;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\CreateProvider;

#[ApiResource(openapi: false)]
#[GetCollection(
    uriTemplate: '/issue5662/admin/reviews{._format}',
    itemUriTemplate: '/issue5662/reviews/{id}{._format}',
    provider: [Review::class, 'getData']
)]
#[Get(
    uriTemplate: '/issue5662/admin/reviews/{id}{._format}',
    provider: [Review::class, 'getDatum']
)]
#[GetCollection(
    uriTemplate: '/issue5662/books/{bookId}/reviews{._format}',
    itemUriTemplate: '/issue5662/books/{bookId}/reviews/{id}{._format}',
    provider: [Review::class, 'getData'],
    uriVariables: [
        'bookId' => new Link(toProperty: 'book', fromClass: Book::class),
    ]
)]
#[Get(
    uriTemplate: '/issue5662/books/{bookId}/reviews/{id}{._format}',
    provider: [Review::class, 'getDatum'],
    uriVariables: [
        'bookId' => new Link(toProperty: 'book', fromClass: Book::class),
        'id' => new Link(fromClass: Review::class),
    ]
)]
#[Post(
    itemUriTemplate: '/issue5662/books/{bookId}/reviews/{id}{._format}',
    uriTemplate: '/issue5662/books/{id}/reviews{._format}',
    uriVariables: [
        'id' => new Link(toProperty: 'book', fromClass: Book::class),
    ],
    provider: CreateProvider::class,
    processor: [Review::class, 'process']
)]
class Review
{
    public function __construct(public ?Book $book = null, public ?int $id = null, public ?string $body = null)
    {
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $data->id = 0;

        return $data;
    }

    public static function getData(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return [
            new self(Book::getDatum($operation, ['id' => 'a'], $context), 1, 'Best book ever!'),
            new self(Book::getDatum($operation, ['id' => 'b'], $context), 2, 'Worst book ever!'),
        ];
    }

    public static function getDatum(Operation $operation, array $uriVariables = [], array $context = []): ?self
    {
        $id = (int) $uriVariables['id'];
        foreach (static::getData($operation, $uriVariables, $context) as $datum) {
            if ($id === $datum->id) {
                return $datum;
            }
        }

        return null;
    }
}
