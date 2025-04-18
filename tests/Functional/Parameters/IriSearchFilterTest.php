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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyAuthor as DummyAuthorDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyBook as DummyBookDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAuthor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyBook;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class IriSearchFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyBook::class,  DummyAuthor::class];
    }

    /**
     * @throws MongoDBException
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        // TODO: implement ODM classes
        $authorEntityClass = $this->isMongoDB() ? DummyAuthorDocument::class : DummyAuthor::class;
        $bookEntityClass = $this->isMongoDB() ? DummyBookDocument::class : DummyBook::class;

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
    #[DataProvider('iriFilterScenariosProvider')]
    public function testIriFilterResponses(string $url, int $expectedCount, string $expectedAuthorIri): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        foreach ($filteredItems as $item) {
            $errorMessage = \sprintf('Expected the book to be associated with author IRI %s', $expectedAuthorIri);
            $this->assertSame($expectedAuthorIri, $item['dummyAuthor'], $errorMessage);
        }
    }

    public static function iriFilterScenariosProvider(): \Generator
    {
        yield 'filter_by_author1' => [
            '/dummy_books?dummyAuthor=/dummy_authors/1',
            2,
            '/dummy_authors/1',
        ];
        yield 'filter_by_author_id_1' => [
            '/dummy_books?dummyAuthor=1',
            2,
            '/dummy_authors/1',
        ];
        yield 'filter_by_author2' => [
            '/dummy_books?dummyAuthor=/dummy_authors/2',
            1,
            '/dummy_authors/2',
        ];
        yield 'filter_by_author_id_2' => [
            '/dummy_books?dummyAuthor=2',
            1,
            '/dummy_authors/2',
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
            /** @var DummyAuthor|DummyAuthorDocument $author */
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
            /** @var DummyBook|DummyBookDocument $book */
            $book = new $bookEntityClass(
                title: $bookData['title'],
                isbn: $bookData['isbn'],
                dummyAuthor: $bookData['author']
            );

            $author->dummyBooks->add($book);
            $manager->persist($book);
        }

        $manager->flush();
    }
}
