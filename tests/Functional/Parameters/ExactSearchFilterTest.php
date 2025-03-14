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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyAuthorExact as DummyAuthorExactDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyBookExact as DummyBookExactDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAuthorExact;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyBookExact;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class ExactSearchFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyBookExact::class,  DummyAuthorExact::class];
    }

    /**
     * @throws MongoDBException
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        // TODO: implement ODM classes
        $authorEntityClass = $this->isMongoDB() ? DummyAuthorExactDocument::class : DummyAuthorExact::class;
        $bookEntityClass = $this->isMongoDB() ? DummyBookExactDocument::class : DummyBookExact::class;

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
    #[DataProvider('exactSearchFilterProvider')]
    public function testExactSearchFilter(string $url, int $expectedCount, array $expectedTitles): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $titles = array_map(fn ($book) => $book['title'], $filteredItems);
        sort($titles);
        sort($expectedTitles);

        $this->assertSame($expectedTitles, $titles, 'The titles do not match the expected values.');
    }

    public static function exactSearchFilterProvider(): \Generator
    {
        yield 'filter_by_author_exact_id_1' => [
            '/dummy_book_exacts?dummyAuthorExact=1',
            2,
            ['Book 1', 'Book 2'],
        ];
        yield 'filter_by_author_exact_id_1_and_title_book_1' => [
            '/dummy_book_exacts?dummyAuthorExact=1&title=Book 1',
            1,
            ['Book 1'],
        ];
        yield 'filter_by_author_exact_id_1_and_title_book_3' => [
            '/dummy_book_exacts?dummyAuthorExact=1&title=Book 3',
            0,
            [],
        ];
        yield 'filter_by_author_exact_id_3_and_title_book_3' => [
            '/dummy_book_exacts?dummyAuthorExact=2&title=Book 3',
            1,
            ['Book 3'],
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
            $book = new $bookEntityClass(
                title: $bookData['title'],
                isbn: $bookData['isbn'],
                dummyAuthorExact: $bookData['author']
            );

            $author->dummyBookExacts->add($book);
            $manager->persist($book);
        }

        $manager->flush();
    }
}
