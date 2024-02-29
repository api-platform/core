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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[GetCollection(
    uriTemplate: '/issue5662/books{._format}',
    itemUriTemplate: '/issue5662/books/{id}{._format}',
    provider: [Book::class, 'getData']
)]
#[Get(
    uriTemplate: '/issue5662/books/{id}{._format}',
    provider: [Book::class, 'getDatum']
)]
class Book
{
    public function __construct(public string $id, public string $title)
    {
    }

    public static function getData(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return [new self('a', 'hello'), new self('b', 'you')];
    }

    public static function getDatum(Operation $operation, array $uriVariables = [], array $context = []): ?self
    {
        $id = $uriVariables['id'];
        foreach (static::getData($operation, $uriVariables, $context) as $datum) {
            if ($id === $datum->id) {
                return $datum;
            }
        }

        return null;
    }
}
