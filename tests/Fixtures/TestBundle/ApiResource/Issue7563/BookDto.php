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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7563;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\ObjectMapper\IsbnToCustomIsbnTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Get(
    stateOptions: new Options(entityClass: Book::class)
)]
#[GetCollection(
    stateOptions: new Options(entityClass: Book::class)
)]
#[Map(source: Book::class)]
class BookDto
{
    public function __construct(
        #[Map(source: 'id')]
        public ?int $id = null,
        #[Map(source: 'name')]
        public ?string $name = null,
        #[Map(source: 'isbn', transform: IsbnToCustomIsbnTransformer::class)]
        public ?string $customIsbn = null,
    ) {
    }
}
