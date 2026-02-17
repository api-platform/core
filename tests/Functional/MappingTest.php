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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\BookStoreResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\FirstResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7563\BookDto;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceNoMap;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceOdm;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceSourceOnly;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceWithInput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceWithRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceWithRelationRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SecondResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MappedDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\BookStore;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedEntityNoMap;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedEntitySourceOnly;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedResourceWithRelationEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedResourceWithRelationRelatedEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SameEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\DocumentManager;

final class MappingTest extends ApiTestCase
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
            MappedResource::class,
            MappedResourceOdm::class,
            FirstResource::class,
            SecondResource::class,
            MappedResourceWithRelation::class,
            MappedResourceWithRelationRelated::class,
            MappedResourceWithInput::class,
            MappedResourceSourceOnly::class,
            MappedResourceNoMap::class,
            BookDto::class,
            BookStoreResource::class,
        ];
    }

    public function testShouldMapBetweenResourceAndEntity(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([MappedEntity::class]);
        $this->loadFixtures();
        $client = self::createClient();
        $client->request('GET', $this->isMongoDB() ? 'mapped_resource_odms' : 'mapped_resources');
        $this->assertArrayHasKey('mapped_data', $client->getKernelBrowser()->getRequest()->attributes->all());
        $this->assertJsonContains(['member' => [
            ['username' => 'B0 A0'],
            ['username' => 'B1 A1'],
            ['username' => 'B2 A2'],
        ]]);

        $client = self::createClient();
        $r = $client->request('POST', $this->isMongoDB() ? 'mapped_resource_odms' : 'mapped_resources', ['json' => ['username' => 'so yuka']]);
        $this->assertJsonContains(['username' => 'so yuka']);
        $this->assertArrayHasKey('persisted_data', $client->getKernelBrowser()->getRequest()->attributes->all());

        $manager = $this->getManager();
        $repo = $manager->getRepository($this->isMongoDB() ? MappedDocument::class : MappedEntity::class);
        $persisted = $repo->findOneBy(['id' => $r->toArray()['id']]);
        $this->assertSame('so', $persisted->getFirstName());
        $this->assertSame('yuka', $persisted->getLastName());

        $uri = $r->toArray()['@id'];
        self::createClient()->request('GET', $uri);
        $this->assertJsonContains(['username' => 'so yuka']);

        self::createClient()->request('PATCH', $uri, ['json' => ['username' => 'ba zar'], 'headers' => ['content-type' => 'application/merge-patch+json']]);
        $this->assertJsonContains(['username' => 'ba zar']);

        self::createClient()->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testShouldMapToTheCorrectResource(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([SameEntity::class]);
        $manager = $this->getManager();
        $e = new SameEntity();
        $e->setName('foo');
        $manager->persist($e);
        $manager->flush();

        self::createClient()->request('GET', '/seconds');
        $this->assertJsonContains(['hydra:member' => [
            ['name' => 'foo', 'extra' => 'field'],
        ]]);
    }

    public function testMapPutAllowCreate(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB is not tested');
        }

        $this->recreateSchema([MappedResourceWithRelationEntity::class, MappedResourceWithRelationRelatedEntity::class]);
        $manager = $this->getManager();

        $e = new MappedResourceWithRelationRelatedEntity();
        $e->name = 'test';
        $manager->persist($e);
        $manager->flush();

        self::createClient()->request('PUT', '/mapped_resource_with_relations/4', [
            'json' => [
                '@id' => '/mapped_resource_with_relations/4',
                'relation' => '/mapped_resource_with_relation_relateds/'.$e->getId(),
            ],
            'headers' => [
                'content-type' => 'application/ld+json',
            ],
        ]);

        $this->assertJsonContains([
            '@context' => '/contexts/MappedResourceWithRelation',
            '@id' => '/mapped_resource_with_relations/4',
            '@type' => 'MappedResourceWithRelation',
            'id' => '4',
            'relationName' => 'test',
            'relation' => '/mapped_resource_with_relation_relateds/1',
        ]);
    }

    public function testShouldNotMapWhenInput(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested');
        }

        $this->recreateSchema([MappedEntity::class]);
        $this->loadFixtures();
        self::createClient()->request('POST', 'mapped_resource_with_input', [
            'headers' => [
                'content-type' => 'application/ld+json',
            ],
            'json' => ['name' => 'test', 'id' => '1'],
        ]);

        $this->assertJsonContains(['username' => 'test']);
    }

    public function testShouldMapWithSourceOnly(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([MappedEntitySourceOnly::class]);
        $manager = $this->getManager();

        for ($i = 0; $i < 10; ++$i) {
            $e = new MappedEntitySourceOnly();
            $e->setLastName('A'.$i);
            $e->setFirstName('B'.$i);
            $manager->persist($e);
        }

        $manager->flush();

        $r = self::createClient()->request('GET', '/mapped_resource_source_onlies');
        $this->assertJsonContains(['member' => [
            ['username' => 'B0 A0'],
            ['username' => 'B1 A1'],
            ['username' => 'B2 A2'],
        ]]);

        $r = self::createClient()->request('POST', 'mapped_resource_source_onlies', ['json' => ['username' => 'so yuka']]);
        $this->assertJsonContains(['username' => 'so yuka']);

        $manager = $this->getManager();
        $repo = $manager->getRepository(MappedEntitySourceOnly::class);
        $persisted = $repo->findOneBy(['id' => $r->toArray()['id']]);
        $this->assertSame('so', $persisted->getFirstName());
        $this->assertSame('yuka', $persisted->getLastName());

        $uri = $r->toArray()['@id'];
        self::createClient()->request('GET', $uri);
        $this->assertJsonContains(['username' => 'so yuka']);

        self::createClient()->request('PATCH', $uri, ['json' => ['username' => 'ba zar'], 'headers' => ['content-type' => 'application/merge-patch+json']]);
        $this->assertJsonContains(['username' => 'ba zar']);
    }

    public function testShouldNotMapWhenCanMapIsFalse(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested');
        }

        $this->recreateSchema([MappedEntityNoMap::class]);

        $client = self::createClient();
        $client->request('POST', '/mapped_resource_no_maps', [
            'json' => ['name' => 'test name', 'id' => 1],
            'headers' => ['content-type' => 'application/ld+json'],
        ]);
        $this->assertArrayNotHasKey('mapped_data', $client->getKernelBrowser()->getRequest()->attributes->all());

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/MappedResourceNoMap',
            '@id' => '/mapped_resource_no_maps/1',
            '@type' => 'MappedResourceNoMap',
            'id' => 1,
            'name' => 'test name',
        ]);

        $client = self::createClient();
        $client->request('GET', '/mapped_resource_no_maps/1');
        $this->assertArrayNotHasKey('persisted_data', $client->getKernelBrowser()->getRequest()->attributes->all());
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@context' => '/contexts/MappedResourceNoMap',
            '@id' => '/mapped_resource_no_maps/1',
            '@type' => 'MappedResourceNoMap',
            'id' => 1,
            'name' => 'test name',
        ]);
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        for ($i = 0; $i < 10; ++$i) {
            $e = $manager instanceof DocumentManager ? new MappedDocument() : new MappedEntity();
            $e->setLastName('A'.$i);
            $e->setFirstName('B'.$i);
            $manager->persist($e);
        }

        $manager->flush();
    }

    private function loadBookFixtures(): void
    {
        $manager = $this->getManager();

        for ($i = 1; $i <= 5; ++$i) {
            $book = new Book();
            $book->name = 'Book '.$i;
            $book->isbn = 'ISBN-'.$i;
            $manager->persist($book);
        }

        $manager->flush();
    }

    public function testGetSingleBookDto(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([Book::class]);
        $this->loadBookFixtures();

        self::createClient()->request('GET', '/book_dtos/1');
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            '@type' => 'BookDto',
            'id' => 1,
            'name' => 'Book 1',
            'customIsbn' => 'customISBN-1',
        ]);
    }

    public function testGetCollectionBookDtoPaginated(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([Book::class]);
        $this->loadBookFixtures();

        $response = self::createClient()->request('GET', '/book_dtos');
        self::assertResponseIsSuccessful();

        $json = $response->toArray();
        self::assertCount(3, $json['hydra:member']);
        foreach ($response->toArray()['hydra:member'] as $member) {
            self::assertStringStartsWith('customISBN-', $member['customIsbn']);
        }
    }

    public function testGetCollectionBookDtoUnpaginated(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([Book::class]);
        $this->loadBookFixtures();

        $response = self::createClient()->request('GET', '/book_dtos?pagination=false');
        self::assertResponseIsSuccessful();

        $json = $response->toArray();
        self::assertCount(5, $json['hydra:member']);
        foreach ($json['hydra:member'] as $member) {
            self::assertStringStartsWith('customISBN-', $member['customIsbn']);
        }
    }

    public function testOutputDtoForCollectionRead(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([BookStore::class]);
        $manager = $this->getManager();

        $book1 = new BookStore();
        $book1->title = 'API Platform Guide';
        $book1->isbn = '978-1234567890';
        $book1->description = 'A comprehensive guide to API Platform';
        $book1->author = 'John Doe';
        $manager->persist($book1);

        $book2 = new BookStore();
        $book2->title = 'REST APIs Handbook';
        $book2->isbn = '978-0987654321';
        $book2->description = 'Everything about REST APIs';
        $book2->author = 'Jane Smith';
        $manager->persist($book2);

        $manager->flush();

        // Test GetCollection returns only the lighter DTO fields (id, name, isbn)
        $response = self::createClient()->request('GET', '/book_store_resources');
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            '@id' => '/book_store_resources',
            '@type' => 'Collection',
            'member' => [
                [
                    '@id' => '/book_store_resources/1',
                    '@type' => 'BookStoreResource',
                    'id' => 1,
                    'name' => 'API Platform Guide',
                    'isbn' => '978-1234567890',
                ],
                [
                    '@id' => '/book_store_resources/2',
                    '@type' => 'BookStoreResource',
                    'id' => 2,
                    'name' => 'REST APIs Handbook',
                    'isbn' => '978-0987654321',
                ],
            ],
        ]);

        $json = $response->toArray();
        // Verify that description and author are NOT present in collection output
        foreach ($json['member'] as $member) {
            self::assertArrayNotHasKey('description', $member);
            self::assertArrayNotHasKey('author', $member);
            self::assertArrayHasKey('id', $member);
            self::assertArrayHasKey('name', $member);
            self::assertArrayHasKey('isbn', $member);
        }

        // Test Get (single item) returns all fields from the full resource
        self::createClient()->request('GET', '/book_store_resources/1');
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => 1,
            'title' => 'API Platform Guide',
            'isbn' => '978-1234567890',
            'description' => 'A comprehensive guide to API Platform',
            'author' => 'John Doe',
        ]);
    }
}
