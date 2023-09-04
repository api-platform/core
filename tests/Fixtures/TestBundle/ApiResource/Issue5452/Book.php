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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\State\Issue5452\BookCollectionProvider;

#[GetCollection(uriTemplate: '/issue-5452/books{._format}', provider: BookCollectionProvider::class)]
#[Post(uriTemplate: '/issue-5452/books{._format}')]
class Book
{
    // union types
    public string|int|null $number = null;

    // simple types
    public ?string $isbn = null;

    // intersect types without specific typehint (throw an error: AbstractItemNormalizer line 872)
    public ActivableInterface&TimestampableInterface $library;

    /**
     * @var Author
     */
    // intersect types with PHPDoc
    public ActivableInterface&TimestampableInterface $author;
}
