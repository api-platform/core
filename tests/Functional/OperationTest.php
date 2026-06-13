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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DisableItemOperation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddableDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ReadableOnlyProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class OperationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ReadableOnlyProperty::class, RelationEmbedder::class, EmbeddedDummy::class, DisableItemOperation::class, Book::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([ReadableOnlyProperty::class, RelationEmbedder::class, EmbeddedDummy::class, DisableItemOperation::class, Book::class]);
    }

    public function testReadOnlyPropertyIgnoresInput(): void
    {
        self::createClient()->request('POST', '/readable_only_properties', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'My Dummy'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/ReadableOnlyProperty',
            '@id' => '/readable_only_properties/1',
            '@type' => 'ReadableOnlyProperty',
            'id' => 1,
            'name' => 'Read only',
        ]);
    }

    public function testCustomOperationOnRelationEmbedder(): void
    {
        $response = self::createClient()->request('GET', '/relation_embedders/42/custom');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertSame('"This is a custom action for 42."', $response->getContent());
    }

    public function testEmbeddedDummyWithGroups(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('EmbeddedDummy fixture uses ORM Embeddable.');
        }

        $manager = $this->getManager();
        $dummy = new EmbeddedDummy();
        $dummy->setName('Dummy #1');
        $embeddable = new EmbeddableDummy();
        $embeddable->setDummyName('Dummy #1');
        $dummy->setEmbeddedDummy($embeddable);
        $manager->persist($dummy);
        $manager->flush();

        self::createClient()->request('GET', '/embedded_dummies_groups/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/EmbeddedDummy',
            '@id' => '/embedded_dummies_groups/1',
            '@type' => 'EmbeddedDummy',
            'name' => 'Dummy #1',
            'embeddedDummy' => [
                '@type' => 'EmbeddableDummy',
                'dummyName' => 'Dummy #1',
            ],
        ]);
    }

    public function testCollectionOnResourceWithDisabledItemOperation(): void
    {
        self::createClient()->request('GET', '/disable_item_operations');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testDisabledItemOperationReturns404(): void
    {
        self::createClient()->request('GET', '/disable_item_operations/1');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetBookByCustomUriTemplate(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Book fixture is ORM-only.');
        }

        $manager = $this->getManager();
        $book = new Book();
        $book->name = '1984';
        $book->isbn = '9780451524935';
        $manager->persist($book);
        $manager->flush();

        self::createClient()->request('GET', '/books/by_isbn/9780451524935');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/Book',
            '@id' => '/books/by_isbn/9780451524935',
            '@type' => 'Book',
            'name' => '1984',
            'isbn' => '9780451524935',
            'id' => 1,
        ]);
    }

    public function testNonApiPlatformRouteIsReachable(): void
    {
        self::createClient()->request('GET', '/common/custom/object');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            'id' => 1,
            'text' => 'Lorem ipsum dolor sit amet',
        ]);
    }
}
