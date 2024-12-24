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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyAuthorPartial as DummyAuthorPartialDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyBookPartial as DummyBookPartialDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAuthorPartial;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyBookPartial;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class PartialSearchFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyBookPartial::class,  DummyAuthorPartial::class];
    }

    /**
     * @throws MongoDBException
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        // TODO: implement ODM classes
        $authorEntityClass = $this->isMongoDB() ? DummyAuthorPartialDocument::class : DummyAuthorPartial::class;
        $bookEntityClass = $this->isMongoDB() ? DummyBookPartialDocument::class : DummyBookPartial::class;

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
    #[DataProvider('partialSearchFilterProvider')]
    public function testPartialSearchFilter(string $url, int $expectedCount, array $expectedTerms): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $titles = array_map(fn ($book) => $book['title'], $filteredItems);
        foreach ($titles as $expectedTitle) {
            $this->assertContains($expectedTitle, $titles, \sprintf('The title "%s" was not found in the results.', $expectedTitle));
        }
    }

    public static function partialSearchFilterProvider(): \Generator
    {
        yield 'filter_by_partial_title_term_book' => [
            '/dummy_book_partials?title=Book',
            3,
            ['Book'],
        ];
        yield 'filter_by_partial_title_term_1' => [
            '/dummy_book_partials?title=1',
            1,
            ['Book 1'],
        ];
        yield 'filter_by_partial_title_term_3' => [
            '/dummy_book_partials?title=3',
            1,
            ['Book 3'],
        ];
        yield 'filter_by_partial_title_with_no_matching_entities' => [
            '/dummy_book_partials?title=99',
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
            /** @var DummyAuthorPartial|DummyAuthorPartialDocument $author */
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
            /** @var DummyBookPartial|DummyBookPartialDocument $book */
            $book = new $bookEntityClass(
                title: $bookData['title'],
                isbn: $bookData['isbn'],
                dummyAuthorPartial: $bookData['author']
            );

            $author->dummyBookPartials->add($book);
            $manager->persist($book);
        }

        $manager->flush();
    }
}
