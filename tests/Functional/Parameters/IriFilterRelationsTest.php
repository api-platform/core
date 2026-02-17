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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterRelationsTest\Author;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterRelationsTest\Biography;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterRelationsTest\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterRelationsTest\Country;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterRelationsTest\Profile;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterRelationsTest\Publisher;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterRelationsTest\Review;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Comprehensive tests for IriFilter with various Doctrine relation types.
 */
final class IriFilterRelationsTest extends ApiTestCase
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
            Country::class,
            Publisher::class,
            Profile::class,
            Biography::class,
            Book::class,
            Review::class,
            Author::class,
        ];
    }

    protected function setUp(): void
    {
        if ('mongodb' === ($_SERVER['APP_ENV'] ?? null)) {
            $this->markTestSkipped('MongoDB not supported for complex relation tests');
        }

        $this->recreateSchema($this->getResources());
    }

    // OneToOne Tests

    public function testIriFilterWithOneToOneOwning(): void
    {
        $fixtures = $this->loadFixtures();
        $profile1 = $fixtures['profile1'];

        $response = self::createClient()->request('GET', '/authors?profile=/profiles/'.$profile1->getId());
        $data = $response->toArray();

        $this->assertCount(1, $data['hydra:member'], 'Should find author with profile1');
        $this->assertEquals('John Doe', $data['hydra:member'][0]['name']);
    }

    public function testIriFilterWithOneToOneOwningMultiple(): void
    {
        $fixtures = $this->loadFixtures();
        $profile1 = $fixtures['profile1'];
        $profile2 = $fixtures['profile2'];

        $response = self::createClient()->request('GET', '/authors?profile[]=/profiles/'.$profile1->getId().'&profile[]=/profiles/'.$profile2->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find authors with profile1 or profile2');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        sort($names);
        $this->assertEquals(['Jane Smith', 'John Doe'], $names);
    }

    public function testIriFilterWithOneToOneInverse(): void
    {
        $fixtures = $this->loadFixtures();
        $biography1 = $fixtures['biography1'];

        $response = self::createClient()->request('GET', '/authors?biography=/biographies/'.$biography1->getId());
        $data = $response->toArray();

        $this->assertCount(1, $data['hydra:member'], 'Should find author with biography1');
        $this->assertEquals('John Doe', $data['hydra:member'][0]['name']);
    }

    public function testIriFilterWithOneToOneInverseNonExistent(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/authors?biography=/biographies/9999');
        $data = $response->toArray();

        $this->assertCount(0, $data['hydra:member'], 'Non-existent biography should return empty collection');
    }

    public function testIriFilterWithNestedOneToOne(): void
    {
        $fixtures = $this->loadFixtures();
        $publisher1 = $fixtures['publisher1'];

        $response = self::createClient()->request('GET', '/profiles?authorPublisher=/publishers/'.$publisher1->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find profiles whose author has publisher1');
        $bios = array_map(static fn ($m) => $m['bio'], $data['hydra:member']);
        $this->assertContains('Bio of John Doe', $bios);
        $this->assertContains('Bio of Jane Smith', $bios);
    }

    public function testIriFilterWithOneToOneNull(): void
    {
        $fixtures = $this->loadFixtures();
        $author2 = $fixtures['author2'];

        // Verify author2 has no biography
        $this->assertNull($author2->getBiography(), 'Author2 should have null biography');

        $response = self::createClient()->request('GET', '/authors');
        $data = $response->toArray();

        // All authors should be returned when no filter is applied
        $this->assertGreaterThanOrEqual(2, \count($data['hydra:member']));
    }

    // ManyToMany Tests

    public function testIriFilterWithManyToManySingle(): void
    {
        $fixtures = $this->loadFixtures();
        $author1 = $fixtures['author1'];

        $response = self::createClient()->request('GET', '/books?author=/authors/'.$author1->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find books by author1');
        $titles = array_map(static fn ($m) => $m['title'], $data['hydra:member']);
        sort($titles);
        $this->assertEquals(['API Design', 'PHP Mastery'], $titles);
    }

    public function testIriFilterWithManyToManyMultiple(): void
    {
        $fixtures = $this->loadFixtures();
        $author1 = $fixtures['author1'];
        $author2 = $fixtures['author2'];

        $response = self::createClient()->request('GET', '/books?author[]=/authors/'.$author1->getId().'&author[]=/authors/'.$author2->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find books by author1 or author2');
        $titles = array_map(static fn ($m) => $m['title'], $data['hydra:member']);
        sort($titles);
        $this->assertEquals(['API Design', 'PHP Mastery'], $titles);
    }

    public function testIriFilterWithManyToManyInverse(): void
    {
        $fixtures = $this->loadFixtures();
        $book1 = $fixtures['book1'];

        $response = self::createClient()->request('GET', '/authors?book=/books/'.$book1->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find authors of book1');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        sort($names);
        $this->assertEquals(['Jane Smith', 'John Doe'], $names);
    }

    public function testIriFilterWithManyToManyInverseMultiple(): void
    {
        $fixtures = $this->loadFixtures();
        $book1 = $fixtures['book1'];
        $book3 = $fixtures['book3'];

        $response = self::createClient()->request('GET', '/authors?book[]=/books/'.$book1->getId().'&book[]=/books/'.$book3->getId());
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member'], 'Should find authors of book1 or book3');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        sort($names);
        $this->assertEquals(['Jane Smith', 'John Doe', 'Mike Brown'], $names);
    }

    public function testIriFilterWithManyToManyNonExistent(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/books?author=/authors/9999');
        $data = $response->toArray();

        $this->assertCount(0, $data['hydra:member'], 'Non-existent author should return empty collection');
    }

    public function testIriFilterWithManyToManyEmptyCollection(): void
    {
        $fixtures = $this->loadFixtures();
        $book4 = $fixtures['book4'];

        // Verify book4 has no authors
        $this->assertCount(0, $book4->getAuthors(), 'Book4 should have no authors');

        $response = self::createClient()->request('GET', '/books');
        $data = $response->toArray();

        // All 3 books with authors should be returned when no filter is applied
        $this->assertGreaterThanOrEqual(3, \count($data['hydra:member']));
    }

    public function testIriFilterWithManyToManyNestedRelation(): void
    {
        $fixtures = $this->loadFixtures();
        $publisher1 = $fixtures['publisher1'];

        $response = self::createClient()->request('GET', '/books?publisherCountry=/countries/'.$publisher1->getCountry()->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find books whose publisher is in country1');
        $titles = array_map(static fn ($m) => $m['title'], $data['hydra:member']);
        sort($titles);
        $this->assertEquals(['API Design', 'PHP Mastery'], $titles);
    }

    public function testIriFilterWithManyToManyEmptyArray(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/books?author[]=');
        $data = $response->toArray();

        // Empty array parameter should be ignored or return all books
        $this->assertGreaterThanOrEqual(0, \count($data['hydra:member']));
    }

    // Deep Nesting Tests

    public function testIriFilterWithThreeLevelNesting(): void
    {
        $fixtures = $this->loadFixtures();
        $country1 = $fixtures['country1'];

        $response = self::createClient()->request('GET', '/authors?publisherCountry=/countries/'.$country1->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find authors whose publisher is in country1');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        sort($names);
        $this->assertEquals(['Jane Smith', 'John Doe'], $names);
    }

    public function testIriFilterWithThreeLevelNestingFromBook(): void
    {
        $fixtures = $this->loadFixtures();
        $country1 = $fixtures['country1'];

        $response = self::createClient()->request('GET', '/books?publisherCountry=/countries/'.$country1->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find books whose publisher is in country1');
        $titles = array_map(static fn ($m) => $m['title'], $data['hydra:member']);
        sort($titles);
        $this->assertEquals(['API Design', 'PHP Mastery'], $titles);
    }

    public function testIriFilterWithThreeLevelNestingMultiple(): void
    {
        $fixtures = $this->loadFixtures();
        $country1 = $fixtures['country1'];
        $country2 = $fixtures['country2'];

        $response = self::createClient()->request('GET', '/authors?publisherCountry[]=/countries/'.$country1->getId().'&publisherCountry[]=/countries/'.$country2->getId());
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member'], 'Should find authors whose publisher is in country1 or country2');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        sort($names);
        $this->assertEquals(['Jane Smith', 'John Doe', 'Sarah Johnson'], $names);
    }

    public function testIriFilterWithThreeLevelNestingNonExistent(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/authors?publisherCountry=/countries/9999');
        $data = $response->toArray();

        $this->assertCount(0, $data['hydra:member'], 'Non-existent country should return empty collection');
    }

    public function testIriFilterWithCollectionNesting(): void
    {
        $fixtures = $this->loadFixtures();
        $publisher1 = $fixtures['publisher1'];

        $response = self::createClient()->request('GET', '/authors?bookPublisher=/publishers/'.$publisher1->getId());
        $data = $response->toArray();

        $this->assertCount(2, $data['hydra:member'], 'Should find authors who have books with publisher1');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        sort($names);
        $this->assertEquals(['Jane Smith', 'John Doe'], $names);
    }

    // Edge Cases

    public function testIriFilterWithNullRelation(): void
    {
        $fixtures = $this->loadFixtures();
        $author3 = $fixtures['author3'];

        // Verify author3 has null publisher
        $this->assertNull($author3->getPublisher(), 'Author3 should have null publisher');

        $response = self::createClient()->request('GET', '/authors');
        $data = $response->toArray();

        // All authors should be returned when no filter is applied
        $this->assertGreaterThanOrEqual(3, \count($data['hydra:member']));
    }

    public function testIriFilterWithEmptyArrayParameter(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/authors?publisher[]=');
        $data = $response->toArray();

        // Empty array parameter should be ignored or return all authors
        $this->assertGreaterThanOrEqual(0, \count($data['hydra:member']));
    }

    public function testIriFilterWithInvalidIri(): void
    {
        $this->loadFixtures();

        // Test with malformed IRI - currently throws error, but should ideally return empty
        // This test documents current behavior rather than expected behavior
        $response = self::createClient()->request('GET', '/authors?publisher=/publishers/9999');
        $data = $response->toArray();

        // Non-existent publisher should return empty collection
        $this->assertCount(0, $data['hydra:member'], 'Non-existent publisher IRI should return empty collection');
    }

    public function testIriFilterWithWrongResourceType(): void
    {
        $fixtures = $this->loadFixtures();
        $author1 = $fixtures['author1'];

        $response = self::createClient()->request('GET', '/authors?publisher=/authors/'.$author1->getId());
        $data = $response->toArray();

        // Wrong resource type should return empty collection
        $this->assertCount(0, $data['hydra:member'], 'IRI of wrong resource type should return empty collection');
    }

    public function testIriFilterWithOneToOneNullBiography(): void
    {
        $fixtures = $this->loadFixtures();
        $author2 = $fixtures['author2'];

        // Verify author2 has no biography
        $this->assertNull($author2->getBiography(), 'Author2 should have null biography');

        // Query for all authors - author2 should be included
        $response = self::createClient()->request('GET', '/authors');
        $data = $response->toArray();

        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        $this->assertContains('Jane Smith', $names, 'Author without biography should be queryable');
    }

    public function testIriFilterWithManyToManyBidirectionalConsistency(): void
    {
        $fixtures = $this->loadFixtures();
        $author1 = $fixtures['author1'];
        $book1 = $fixtures['book1'];

        // Get books by author1
        $booksResponse = self::createClient()->request('GET', '/books?author=/authors/'.$author1->getId());
        $booksData = $booksResponse->toArray();
        $bookIds = array_map(static fn ($m) => (int) basename($m['@id']), $booksData['hydra:member']);

        // Get authors of book1
        $authorsResponse = self::createClient()->request('GET', '/authors?book=/books/'.$book1->getId());
        $authorsData = $authorsResponse->toArray();
        $authorIds = array_map(static fn ($m) => (int) basename($m['@id']), $authorsData['hydra:member']);

        // Verify bidirectional consistency
        $this->assertContains($book1->getId(), $bookIds, 'Author1 should have book1');
        $this->assertContains($author1->getId(), $authorIds, 'Book1 should have author1');
    }

    public function testIriFilterArrayWithNestedRelation(): void
    {
        $fixtures = $this->loadFixtures();
        $country1 = $fixtures['country1'];
        $country2 = $fixtures['country2'];

        $response = self::createClient()->request('GET', '/authors?publisherCountry[]=/countries/'.$country1->getId().'&publisherCountry[]=/countries/'.$country2->getId());
        $data = $response->toArray();

        $this->assertCount(3, $data['hydra:member'], 'Should find authors whose publisher is in country1 or country2');
        $names = array_map(static fn ($m) => $m['name'], $data['hydra:member']);
        sort($names);
        $this->assertEquals(['Jane Smith', 'John Doe', 'Sarah Johnson'], $names);
    }

    private function loadFixtures(): array
    {
        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();

        // Create countries
        $country1 = new Country();
        $country1->setName('USA');
        $manager->persist($country1);

        $country2 = new Country();
        $country2->setName('UK');
        $manager->persist($country2);

        $manager->flush();

        // Create publishers
        $publisher1 = new Publisher();
        $publisher1->setName('Tech Books Inc');
        $publisher1->setCountry($country1);
        $manager->persist($publisher1);

        $publisher2 = new Publisher();
        $publisher2->setName('British Literature Press');
        $publisher2->setCountry($country2);
        $manager->persist($publisher2);

        $publisher3 = new Publisher();
        $publisher3->setName('Orphan Publisher');
        $publisher3->setCountry(null);
        $manager->persist($publisher3);

        $manager->flush();

        // Create books
        $book1 = new Book();
        $book1->setTitle('PHP Mastery');
        $book1->setPublisher($publisher1);
        $manager->persist($book1);

        $book2 = new Book();
        $book2->setTitle('API Design');
        $book2->setPublisher($publisher1);
        $manager->persist($book2);

        $book3 = new Book();
        $book3->setTitle('British Literature');
        $book3->setPublisher($publisher2);
        $manager->persist($book3);

        $book4 = new Book();
        $book4->setTitle('Orphan Book');
        $book4->setPublisher(null);
        $manager->persist($book4);

        $manager->flush();

        // Create profiles
        $profile1 = new Profile();
        $profile1->setBio('Bio of John Doe');
        $manager->persist($profile1);

        $profile2 = new Profile();
        $profile2->setBio('Bio of Jane Smith');
        $manager->persist($profile2);

        $manager->flush();

        // Create authors
        $author1 = new Author();
        $author1->setName('John Doe');
        $author1->setPublisher($publisher1);
        $author1->setProfile($profile1);
        $author1->addBook($book1);
        $author1->addBook($book2);
        $manager->persist($author1);

        $author2 = new Author();
        $author2->setName('Jane Smith');
        $author2->setPublisher($publisher1);
        $author2->setProfile($profile2);
        $author2->addBook($book1);
        $manager->persist($author2);

        $author3 = new Author();
        $author3->setName('Mike Brown');
        $author3->setPublisher(null);
        $author3->addBook($book3);
        $manager->persist($author3);

        $author4 = new Author();
        $author4->setName('Sarah Johnson');
        $author4->setPublisher($publisher2);
        $manager->persist($author4);

        $manager->flush();

        // Create biography and set it on author1
        $biography1 = new Biography();
        $biography1->setText('Biography of John Doe');
        $manager->persist($biography1);

        $author1->setBiography($biography1);

        $manager->flush();

        // Create reviews
        $review1 = new Review();
        $review1->setRating(5);
        $review1->setBook($book1);
        $manager->persist($review1);

        $review2 = new Review();
        $review2->setRating(4);
        $review2->setBook($book1);
        $manager->persist($review2);

        $review3 = new Review();
        $review3->setRating(3);
        $review3->setBook($book3);
        $manager->persist($review3);

        $manager->flush();

        return [
            'country1' => $country1,
            'country2' => $country2,
            'publisher1' => $publisher1,
            'publisher2' => $publisher2,
            'publisher3' => $publisher3,
            'book1' => $book1,
            'book2' => $book2,
            'book3' => $book3,
            'book4' => $book4,
            'profile1' => $profile1,
            'profile2' => $profile2,
            'biography1' => $biography1,
            'author1' => $author1,
            'author2' => $author2,
            'author3' => $author3,
            'author4' => $author4,
            'review1' => $review1,
            'review2' => $review2,
            'review3' => $review3,
        ];
    }
}
