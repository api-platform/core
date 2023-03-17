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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State\Issue5452;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\Author;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\Book;

class BookCollectionProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $book = new Book();
        $book->number = 1;
        $book->isbn = '978-3-16-148410-0';
        $book->author = new Author(1, 'John DOE');

        return [$book];
    }
}
