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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyAuthorStart as DummyAuthorStartDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyBookStart as DummyBookStartDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAuthorStart;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyBookStart;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class StartSearchFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyBookStart::class,  DummyAuthorStart::class];
    }

    /**
     * @throws MongoDBException
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        // TODO: implement ODM classes
        $authorEntityClass = $this->isMongoDB() ? DummyAuthorStartDocument::class : DummyAuthorStart::class;
        $bookEntityClass = $this->isMongoDB() ? DummyBookStartDocument::class : DummyBookStart::class;

        $this->recreateSchema([$authorEntityClass, $bookEntityClass]);
        $this->loadFixtures($authorEntityClass, $bookEntityClass);
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     */
    #[DataProvider('startSearchFilterProvider')]
    public function testStartSearchFilter(string $url, int $expectedCount, array $expectedTitles): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $titles = array_map(fn ($book) => $book['title'], $filteredItems);
        foreach ($expectedTitles as $expectedTitle) {
            $this->assertContains($expectedTitle, $titles, \sprintf('The title "%s" was not found in the results.', $expectedTitle));
        }
    }

    public static function startSearchFilterProvider(): \Generator
    {
        yield 'filter_by_starting_title_book' => [
            '/dummy_book_starts?title=Book',
            3,
            ['Book 1', 'Book 2', 'Book 3'],
        ];
        yield 'filter_by_starting_title_book_1' => [
            '/dummy_book_starts?title=Book 1',
            1,
            ['Book 1'],
        ];
        yield 'filter_by_starting_title_b' => [
            '/dummy_book_starts?title=B',
            3,
            ['Book 1', 'Book 2', 'Book 3'],
        ];
        yield 'filter_by_starting_title_nonexistent' => [
            '/dummy_book_starts?title=NonExistent',
            0,
            [],
        ];
        yield 'filter_by_title_ending_on_start_filter' => [
            '/dummy_book_starts?title=3',
            0,
            [],
        ];
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(string $authorEntityClass, string $bookEntityClass): void
    {
        $manager = $this->getManager();

        $authors = [];
        foreach ([['name' => 'Author 1'], ['name' => 'Author 2']] as $authorData) {
            /** @var DummyAuthorStart|DummyAuthorStartDocument $author */
            $author = new $authorEntityClass(name: $authorData['name']);
            $manager->persist($author);
            $authors[] = $author;
        }

        $books = [
            ['title' => 'Book 1', 'isbn' => '1234567890123', 'author' => $authors[0]],
            ['title' => 'Book 2', 'isbn' => '1234567890124', 'author' => $authors[0]],
            ['title' => 'Book 3', 'isbn' => '1234567890125', 'author' => $authors[1]],
        ];

        foreach ($books as $bookData) {
            /** @var DummyBookStart|DummyBookStartDocument $book */
            $book = new $bookEntityClass(
                title: $bookData['title'],
                isbn: $bookData['isbn'],
                dummyAuthorStart: $bookData['author'],
            );

            $author->dummyBookStarts->add($book);
            $manager->persist($book);
        }

        $manager->flush();
    }
}
