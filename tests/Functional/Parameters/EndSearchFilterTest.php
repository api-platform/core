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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyAuthorEnd as DummyAuthorEndDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyBookEnd as DummyBookEndDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAuthorEnd;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyBookEnd;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class EndSearchFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyBookEnd::class,  DummyAuthorEnd::class];
    }

    /**
     * @throws MongoDBException
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        // TODO: implement ODM classes
        $authorEntityClass = $this->isMongoDB() ? DummyAuthorEndDocument::class : DummyAuthorEnd::class;
        $bookEntityClass = $this->isMongoDB() ? DummyBookEndDocument::class : DummyBookEnd::class;

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
    #[DataProvider('endSearchFilterProvider')]
    public function testEndSearchFilter(string $url, int $expectedCount, array $expectedTitles): void
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

    public static function endSearchFilterProvider(): \Generator
    {
        yield 'filter_by_title_ending_with_book' => [
            '/dummy_book_ends?title=Book',
            0,
            [],
        ];
        yield 'filter_by_title_ending_with_1' => [
            '/dummy_book_ends?title=1',
            1,
            ['Book 1'],
        ];
        yield 'filter_by_title_ending_with_2' => [
            '/dummy_book_ends?title=2',
            1,
            ['Book 2'],
        ];
        yield 'filter_by_title_ending_with_3' => [
            '/dummy_book_ends?title=3',
            1,
            ['Book 3'],
        ];
        yield 'filter_by_title_ending_with_nonexistent_term' => [
            '/dummy_book_ends?title=NonExistent',
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
            /** @var DummyAuthorEnd|DummyAuthorEndDocument $author */
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
            /** @var DummyBookEnd|DummyBookEndDocument $book */
            $book = new $bookEntityClass(
                title: $bookData['title'],
                isbn: $bookData['isbn'],
                dummyAuthorEnd: $bookData['author'],
            );

            $author->dummyBookEnds->add($book);
            $manager->persist($book);
        }

        $manager->flush();
    }
}
