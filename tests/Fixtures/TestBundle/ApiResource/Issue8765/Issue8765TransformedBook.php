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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Author;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Book;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Review;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * ObjectMapper, relations mapped through a transform: what is read on the entity is opaque to the mapping metadata.
 */
#[ApiResource(
    operations: [new GetCollection(uriTemplate: '/issue8765_transformed_books', paginationItemsPerPage: 6)],
    stateOptions: new Options(entityClass: Issue8765Book::class),
)]
#[Map(source: Issue8765Book::class)]
class Issue8765TransformedBook
{
    public int $id;

    public string $title;

    #[Map(transform: [self::class, 'mapAuthor'])]
    public Issue8765AuthorOutput $author;

    /** @var list<Issue8765ReviewOutput> */
    #[Map(transform: [self::class, 'mapReviews'])]
    public array $reviews = [];

    public static function mapAuthor(Issue8765Author $author): Issue8765AuthorOutput
    {
        return new Issue8765AuthorOutput(
            $author->name,
            new Issue8765CountryOutput($author->country->name),
            array_map(static fn ($award): Issue8765AwardOutput => new Issue8765AwardOutput($award->name), $author->awards->getValues()),
        );
    }

    /**
     * @param iterable<Issue8765Review> $reviews
     *
     * @return list<Issue8765ReviewOutput>
     */
    public static function mapReviews(iterable $reviews): array
    {
        $outputs = [];
        foreach ($reviews as $review) {
            $outputs[] = new Issue8765ReviewOutput($review->rating);
        }

        return $outputs;
    }
}
