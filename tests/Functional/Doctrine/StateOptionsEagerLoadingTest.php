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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765BookTitle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765MappedBook;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765ProviderBook;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765ProviderBookWithNestedAuthor;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765TransformedBook;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Author;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Award;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Book;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Country;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Review;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;

/**
 * Eager loading of the relations embedded by a resource mapped to an entity through stateOptions, without serialization groups.
 *
 * The fixtures hold 6 books written by 3 authors (2 books each, from 2 countries), each author has 2 awards and each book has 2 reviews.
 * Without joins, every relation is loaded lazily, once per distinct owner: the query count grows with the page size.
 * The expected counts are the ones of a page query that joins every embedded relation (the Doctrine paginator adds a DISTINCT ids query
 * when a to-many relation is joined, and a COUNT query when the total is needed).
 *
 * @see https://github.com/api-platform/core/issues/8765
 */
final class StateOptionsEagerLoadingTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            Issue8765ProviderBook::class,
            Issue8765ProviderBookWithNestedAuthor::class,
            Issue8765MappedBook::class,
            Issue8765TransformedBook::class,
            Issue8765BookTitle::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMongoDB()) {
            $this->markTestSkipped('Eager loading is ORM only.');
        }

        $this->recreateSchema([Issue8765Country::class, Issue8765Author::class, Issue8765Award::class, Issue8765Book::class, Issue8765Review::class]);
        $this->loadFixtures();
    }

    /**
     * Custom provider (Doctrine collection provider + hand-written mapping), to-one and to-many embedded with readableLink.
     */
    public function testCustomProviderJoinsTheEmbeddedRelations(): void
    {
        [$member, $queries] = $this->requestCollection('/issue8765_provider_books');

        $this->assertSame(['id' => 1, 'title' => 'Book 1', 'author' => ['name' => 'Author 1'], 'reviews' => [['rating' => 1], ['rating' => 2]]], $member[0]);
        // DISTINCT ids of the page + the page with author and reviews joined; currently 10: 1 + 3 authors + 6 reviews collections
        $this->assertQueryCount(2, $queries);
    }

    /**
     * Custom provider, nested embeds: author -> country (to-one) and awards (to-many).
     */
    public function testCustomProviderJoinsTheNestedEmbeddedRelations(): void
    {
        [$member, $queries] = $this->requestCollection('/issue8765_provider_books_with_nested_author');

        $this->assertSame(['id' => 1, 'title' => 'Book 1', 'author' => ['name' => 'Author 1', 'country' => ['name' => 'Country 1'], 'awards' => [['name' => 'Award 1-1'], ['name' => 'Award 1-2']]]], $member[0]);
        // DISTINCT ids of the page + the page with author, country and awards joined; currently 9: 1 + 3 authors + 2 countries + 3 awards collections
        $this->assertQueryCount(2, $queries);
    }

    /**
     * ObjectMapper with #[Map(source: ...)] renaming the associations (writer <- author, comments <- reviews), nested embeds included.
     */
    public function testObjectMapperJoinsTheRenamedAssociations(): void
    {
        [$member, $queries] = $this->requestCollection('/issue8765_mapped_books');

        $this->assertSame([
            'id' => 1,
            'title' => 'Book 1',
            'writer' => ['name' => 'Author 1', 'country' => ['name' => 'Country 1'], 'awards' => [['name' => 'Award 1-1'], ['name' => 'Award 1-2']]],
            'comments' => [['rating' => 1], ['rating' => 2]],
        ], $member[0]);
        // DISTINCT ids of the page + the page with every relation joined + COUNT; currently 16: 2 + 3 authors + 2 countries + 3 awards collections + 6 reviews collections
        $this->assertQueryCount(3, $queries);
    }

    /**
     * ObjectMapper with a transform on the relations: the mapping metadata does not tell what the transform reads on the entity.
     */
    public function testObjectMapperJoinsTheRelationsReadByATransform(): void
    {
        [$member, $queries] = $this->requestCollection('/issue8765_transformed_books');

        $this->assertSame([
            'id' => 1,
            'title' => 'Book 1',
            'author' => ['name' => 'Author 1', 'country' => ['name' => 'Country 1'], 'awards' => [['name' => 'Award 1-1'], ['name' => 'Award 1-2']]],
            'reviews' => [['rating' => 1], ['rating' => 2]],
        ], $member[0]);
        // DISTINCT ids of the page + the page with every relation joined + COUNT; currently 16: 2 + 3 authors + 2 countries + 3 awards collections + 6 reviews collections
        $this->assertQueryCount(3, $queries);
    }

    /**
     * A resource that embeds no relation must not join anything.
     */
    public function testResourceWithoutEmbedsJoinsNothing(): void
    {
        [$member, $queries] = $this->requestCollection('/issue8765_book_titles');

        $this->assertSame(['id' => 1, 'title' => 'Book 1'], $member[0]);
        // the page + COUNT, without any join
        $this->assertQueryCount(2, $queries);
        $this->assertStringNotContainsString(' JOIN ', implode("\n", $queries));
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<string>}
     */
    private function requestCollection(string $uri): array
    {
        $client = self::createClient();
        $client->enableProfiler();
        $response = $client->request('GET', $uri, ['headers' => ['Accept' => 'application/json']]);

        $this->assertResponseIsSuccessful();
        $member = $response->toArray();
        $this->assertCount(6, $member);

        /** @var DoctrineDataCollector $collector */
        $collector = $client->getProfile()->getCollector('db');
        $queries = array_values(array_filter(
            array_column($collector->getQueries()['default'] ?? [], 'sql'),
            static fn (string $sql): bool => str_starts_with($sql, 'SELECT') && str_contains($sql, 'Issue8765'),
        ));

        return [$member, $queries];
    }

    /**
     * @param list<string> $queries
     */
    private function assertQueryCount(int $expected, array $queries): void
    {
        $this->assertCount($expected, $queries, \sprintf("%d queries were run instead of %d:\n%s", \count($queries), $expected, implode("\n", $queries)));
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $countries = [];
        foreach ([1, 2] as $i) {
            $country = new Issue8765Country();
            $country->name = 'Country '.$i;
            $manager->persist($country);
            $countries[] = $country;
        }

        $bookNumber = 0;
        foreach ([1, 2, 3] as $i) {
            $author = new Issue8765Author();
            $author->name = 'Author '.$i;
            $author->country = $countries[($i - 1) % 2];
            $manager->persist($author);

            foreach ([1, 2] as $j) {
                $award = new Issue8765Award();
                $award->name = \sprintf('Award %d-%d', $i, $j);
                $award->author = $author;
                $manager->persist($award);
            }

            foreach ([1, 2] as $j) {
                $book = new Issue8765Book();
                $book->title = 'Book '.++$bookNumber;
                $book->author = $author;
                $manager->persist($book);

                foreach ([1, 2] as $rating) {
                    $review = new Issue8765Review();
                    $review->rating = $rating;
                    $review->book = $book;
                    $manager->persist($review);
                }
            }
        }

        $manager->flush();
        $manager->clear();
    }
}
