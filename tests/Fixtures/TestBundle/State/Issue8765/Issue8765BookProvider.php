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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State\Issue8765;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765AuthorOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765AuthorSummary;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765AwardOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765CountryOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765ProviderBook;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765ProviderBookWithNestedAuthor;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765ReviewOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Book;

/**
 * The Doctrine collection provider (and its extensions) followed by a hand-written entity to DTO mapping.
 */
final class Issue8765BookProvider implements ProviderInterface
{
    public function __construct(private readonly CollectionProvider $collectionProvider)
    {
    }

    /**
     * @return list<Issue8765ProviderBook|Issue8765ProviderBookWithNestedAuthor>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $books = [];
        /** @var Issue8765Book $book */
        foreach ($this->collectionProvider->provide($operation, $uriVariables, $context) as $book) {
            $books[] = Issue8765ProviderBook::class === $operation->getClass()
                ? new Issue8765ProviderBook(
                    $book->id,
                    $book->title,
                    new Issue8765AuthorSummary($book->author->name),
                    array_map(static fn ($review): Issue8765ReviewOutput => new Issue8765ReviewOutput($review->rating), $book->reviews->getValues()),
                )
                : new Issue8765ProviderBookWithNestedAuthor(
                    $book->id,
                    $book->title,
                    new Issue8765AuthorOutput(
                        $book->author->name,
                        new Issue8765CountryOutput($book->author->country->name),
                        array_map(static fn ($award): Issue8765AwardOutput => new Issue8765AwardOutput($award->name), $book->author->awards->getValues()),
                    ),
                );
        }

        return $books;
    }
}
