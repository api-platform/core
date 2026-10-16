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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedBook;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedBookStore;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedFictionBook;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedSignedFictionBook;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedTechnicalBook;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\UndiscriminatedBook;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Polymorphic resources declared through a serializer discriminator map.
 */
final class DiscriminatedResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DiscriminatedBook::class, UndiscriminatedBook::class];
    }

    protected function setUp(): void
    {
        $fiction = new DiscriminatedFictionBook();
        $fiction->id = 1;
        $fiction->title = 'The Hobbit';
        $fiction->genre = 'Fantasy';
        $fiction->pageCount = 310;

        $technical = new DiscriminatedTechnicalBook();
        $technical->id = 2;
        $technical->title = 'Design Patterns';
        $technical->programmingLanguage = 'C++';
        $technical->internalNote = 'secret';

        $signed = new DiscriminatedSignedFictionBook();
        $signed->id = 3;
        $signed->title = 'Signed copy';
        $signed->genre = 'Fantasy';
        $signed->signature = 'must not leak';

        DiscriminatedBookStore::$books = [1 => $fiction, 2 => $technical, 3 => $signed];
    }

    protected function tearDown(): void
    {
        DiscriminatedBookStore::$books = [];

        parent::tearDown();
    }

    public function testGetItemExposesSubtypeProperties(): void
    {
        $response = self::createClient()->request('GET', '/discriminated_books/1');

        $this->assertResponseIsSuccessful();
        $this->assertEquals([
            '@context' => '/contexts/DiscriminatedBook',
            '@id' => '/discriminated_books/1',
            '@type' => 'DiscriminatedBook',
            'kind' => 'fiction',
            'id' => 1,
            'title' => 'The Hobbit',
            'genre' => 'Fantasy',
            'pageCount' => 310,
            'sequelOf' => null,
        ], $response->toArray());
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedBook::class);
    }

    public function testGetItemAppliesSubtypePropertySecurity(): void
    {
        $response = self::createClient()->request('GET', '/discriminated_books/2');

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $this->assertSame('technical', $json['kind']);
        $this->assertSame('C++', $json['programmingLanguage']);
        $this->assertArrayNotHasKey('internalNote', $json);
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedBook::class);
    }

    /**
     * The allowed attributes are cached per cache key, subtype property security must make that key unsafe
     * or the attributes allowed for a user leak to the next ones in long-running processes.
     */
    #[DataProvider('userSequenceProvider')]
    public function testSubtypePropertySecurityIsNotCachedAcrossUsers(bool $adminFirst): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $requestAs = static function (bool $admin) use ($client): array {
            $client->getCookieJar()->clear();
            if ($admin) {
                $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));
            }

            return $client->request('GET', '/discriminated_books/2', ['headers' => ['Accept' => 'application/json']])->toArray();
        };

        foreach ([$adminFirst, !$adminFirst, $adminFirst] as $admin) {
            $json = $requestAs($admin);
            if ($admin) {
                $this->assertSame('secret', $json['internalNote'] ?? null);
            } else {
                $this->assertArrayNotHasKey('internalNote', $json);
            }
        }
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function userSequenceProvider(): iterable
    {
        yield 'admin first' => [true];
        yield 'anonymous first' => [false];
    }

    public function testGetItemDoesNotExposePropertiesOfUnmappedSubclass(): void
    {
        $response = self::createClient()->request('GET', '/discriminated_books/3');

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $this->assertSame('fiction', $json['kind']);
        $this->assertSame('Fantasy', $json['genre']);
        $this->assertArrayNotHasKey('signature', $json);
    }

    public function testGetItemWithoutDiscriminatorMapDoesNotExposeSubclassProperties(): void
    {
        $response = self::createClient()->request('GET', '/undiscriminated_books/1');

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $this->assertSame('Hidden', $json['title']);
        $this->assertArrayNotHasKey('secret', $json);
    }

    public function testGetCollectionExposesEachSubtype(): void
    {
        unset(DiscriminatedBookStore::$books[3]);

        $response = self::createClient()->request('GET', '/discriminated_books');

        $this->assertResponseIsSuccessful();
        $members = $response->toArray()['hydra:member'];
        $this->assertCount(2, $members);
        $this->assertSame('fiction', $members[0]['kind']);
        $this->assertSame('Fantasy', $members[0]['genre']);
        $this->assertArrayNotHasKey('programmingLanguage', $members[0]);
        $this->assertSame('technical', $members[1]['kind']);
        $this->assertSame('C++', $members[1]['programmingLanguage']);
        $this->assertArrayNotHasKey('genre', $members[1]);
        $this->assertMatchesResourceCollectionJsonSchema(DiscriminatedBook::class);
    }

    public function testGetItemInJson(): void
    {
        $response = self::createClient()->request('GET', '/discriminated_books/1', ['headers' => ['Accept' => 'application/json']]);

        $this->assertResponseIsSuccessful();
        $this->assertEquals([
            'kind' => 'fiction',
            'id' => 1,
            'title' => 'The Hobbit',
            'genre' => 'Fantasy',
            'pageCount' => 310,
            'sequelOf' => null,
        ], $response->toArray());
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedBook::class, format: 'json');
    }

    public function testGetItemInJsonApi(): void
    {
        $response = self::createClient()->request('GET', '/discriminated_books/2', ['headers' => ['Accept' => 'application/vnd.api+json']]);

        $this->assertResponseIsSuccessful();
        $attributes = $response->toArray()['data']['attributes'];
        $this->assertSame('technical', $attributes['kind']);
        $this->assertSame('C++', $attributes['programmingLanguage']);
        $this->assertArrayNotHasKey('internalNote', $attributes);
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedBook::class, format: 'jsonapi');
    }

    public function testGetItemInHal(): void
    {
        $response = self::createClient()->request('GET', '/discriminated_books/1', ['headers' => ['Accept' => 'application/hal+json']]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $this->assertSame('fiction', $json['kind']);
        $this->assertSame('Fantasy', $json['genre']);
        $this->assertSame(310, $json['pageCount']);
    }

    public function testPostCreatesTheMappedSubtype(): void
    {
        $response = self::createClient()->request('POST', '/discriminated_books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'kind' => 'technical',
                'title' => 'Refactoring',
                'programmingLanguage' => 'Java',
                'internalNote' => 'cannot be written anonymously',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertEquals([
            '@context' => '/contexts/DiscriminatedBook',
            '@id' => '/discriminated_books/4',
            '@type' => 'DiscriminatedBook',
            'kind' => 'technical',
            'id' => 4,
            'title' => 'Refactoring',
            'programmingLanguage' => 'Java',
        ], $response->toArray());
        $this->assertInstanceOf(DiscriminatedTechnicalBook::class, DiscriminatedBookStore::$books[4]);
        $this->assertNull(DiscriminatedBookStore::$books[4]->internalNote);
    }

    public function testPostDenormalizesSubtypeRelation(): void
    {
        $response = self::createClient()->request('POST', '/discriminated_books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'kind' => 'fiction',
                'title' => 'The Lord of the Rings',
                'genre' => 'Fantasy',
                'sequelOf' => '/discriminated_books/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'kind' => 'fiction',
            'sequelOf' => '/discriminated_books/1',
        ]);
        $book = DiscriminatedBookStore::$books[4];
        $this->assertInstanceOf(DiscriminatedFictionBook::class, $book);
        $this->assertSame(DiscriminatedBookStore::$books[1], $book->sequelOf);
    }

    public function testPostInJson(): void
    {
        self::createClient()->request('POST', '/discriminated_books', [
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'json' => [
                'kind' => 'fiction',
                'title' => 'Dune',
                'genre' => 'Science fiction',
                'pageCount' => 412,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            'kind' => 'fiction',
            'id' => 4,
            'title' => 'Dune',
            'genre' => 'Science fiction',
            'pageCount' => 412,
            'sequelOf' => null,
        ]);
    }

    public function testPostValidatesSubtypeConstraints(): void
    {
        self::createClient()->request('POST', '/discriminated_books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'kind' => 'fiction',
                'title' => 'Dune',
                'pageCount' => -1,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'pageCount']]]);
    }

    public function testPostWithoutTypeIsRejected(): void
    {
        self::createClient()->request('POST', '/discriminated_books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => 'Untyped'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertCount(3, DiscriminatedBookStore::$books);
    }

    public function testPostWithUnknownTypeIsRejected(): void
    {
        self::createClient()->request('POST', '/discriminated_books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'poetry', 'title' => 'Unknown'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertCount(3, DiscriminatedBookStore::$books);
    }

    public function testPatchUpdatesSubtypeProperties(): void
    {
        self::createClient()->request('PATCH', '/discriminated_books/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['genre' => 'High fantasy', 'pageCount' => 320],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'kind' => 'fiction',
            'title' => 'The Hobbit',
            'genre' => 'High fantasy',
            'pageCount' => 320,
        ]);
        $book = DiscriminatedBookStore::$books[1];
        $this->assertInstanceOf(DiscriminatedFictionBook::class, $book);
        $this->assertSame('High fantasy', $book->genre);
    }

    public function testPatchAcceptsMatchingType(): void
    {
        self::createClient()->request('PATCH', '/discriminated_books/2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['kind' => 'technical', 'programmingLanguage' => 'Rust'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['kind' => 'technical', 'programmingLanguage' => 'Rust']);
    }

    public function testPatchCannotChangeTheType(): void
    {
        self::createClient()->request('PATCH', '/discriminated_books/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['kind' => 'technical', 'programmingLanguage' => 'Rust'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertInstanceOf(DiscriminatedFictionBook::class, DiscriminatedBookStore::$books[1]);
        $this->assertSame('Fantasy', DiscriminatedBookStore::$books[1]->genre);
    }

    public function testPatchValidatesSubtypeConstraints(): void
    {
        self::createClient()->request('PATCH', '/discriminated_books/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['pageCount' => 0],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'pageCount']]]);
    }

    public function testPutReplacesTheResourceWithTheGivenSubtype(): void
    {
        self::createClient()->request('PUT', '/discriminated_books/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'kind' => 'technical',
                'title' => 'Clean Code',
                'programmingLanguage' => 'Java',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/DiscriminatedBook',
            '@id' => '/discriminated_books/1',
            '@type' => 'DiscriminatedBook',
            'kind' => 'technical',
            'id' => 1,
            'title' => 'Clean Code',
            'programmingLanguage' => 'Java',
        ]);
        $this->assertInstanceOf(DiscriminatedTechnicalBook::class, DiscriminatedBookStore::$books[1]);
    }

    public function testDelete(): void
    {
        self::createClient()->request('DELETE', '/discriminated_books/2');

        $this->assertResponseStatusCodeSame(204);
        $this->assertArrayNotHasKey(2, DiscriminatedBookStore::$books);
    }

    public function testOpenApiDocumentsSubtypes(): void
    {
        $json = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();
        $schemas = $json['components']['schemas'];

        // Output (JSON-LD): the base definition keeps its own properties and lists its subtypes.
        $output = $this->findDefinition($schemas, $json['paths']['/discriminated_books/{id}']['get']['responses']['200']['content']['application/ld+json']['schema']['$ref']);
        $this->assertSame([
            'propertyName' => 'kind',
            'mapping' => [
                'fiction' => '#/components/schemas/DiscriminatedBook.DiscriminatedFictionBook.jsonld',
                'technical' => '#/components/schemas/DiscriminatedBook.DiscriminatedTechnicalBook.jsonld',
            ],
        ], $output['discriminator']);
        $this->assertSame([
            ['$ref' => '#/components/schemas/DiscriminatedBook.DiscriminatedFictionBook.jsonld'],
            ['$ref' => '#/components/schemas/DiscriminatedBook.DiscriminatedTechnicalBook.jsonld'],
        ], $output['oneOf']);
        $this->assertArrayHasKey('title', $output['properties']);
        $this->assertArrayNotHasKey('genre', $output['properties']);

        $fiction = $schemas['DiscriminatedBook.DiscriminatedFictionBook.jsonld'];
        $this->assertSame(['type' => 'string', 'enum' => ['fiction']], $fiction['properties']['kind']);
        $this->assertContains('kind', $fiction['required']);
        $this->assertArrayHasKey('genre', $fiction['properties']);
        $this->assertArrayHasKey('pageCount', $fiction['properties']);
        $this->assertArrayNotHasKey('signature', $fiction['properties']);
        $this->assertArrayNotHasKey('allOf', $fiction, 'Subtype schemas must not reference the base schema back (circular reference).');

        $technical = $schemas['DiscriminatedBook.DiscriminatedTechnicalBook.jsonld'];
        $this->assertSame(['type' => 'string', 'enum' => ['technical']], $technical['properties']['kind']);
        $this->assertArrayHasKey('programmingLanguage', $technical['properties']);
        $this->assertArrayNotHasKey('genre', $technical['properties']);

        // Required properties of a subtype must not leak into the base nor into siblings.
        $this->assertNotContains('kind', $output['required'] ?? []);
        $this->assertNotContains('kind', $schemas['DiscriminatedBook.jsonld']['allOf'][1]['required'] ?? []);

        // Input (POST)
        $postInput = $this->findDefinition($schemas, $json['paths']['/discriminated_books']['post']['requestBody']['content']['application/ld+json']['schema']['$ref']);
        $this->assertSame('kind', $postInput['discriminator']['propertyName']);
        $this->assertSame([
            'fiction' => '#/components/schemas/DiscriminatedBook.DiscriminatedFictionBook',
            'technical' => '#/components/schemas/DiscriminatedBook.DiscriminatedTechnicalBook',
        ], $postInput['discriminator']['mapping']);
        $this->assertContains('kind', $schemas['DiscriminatedBook.DiscriminatedFictionBook']['required']);
        $this->assertArrayHasKey('genre', $schemas['DiscriminatedBook.DiscriminatedFictionBook']['properties']);

        // Input (PATCH): a merge-patch document does not require any property, not even the type.
        $patchInput = $this->findDefinition($schemas, $json['paths']['/discriminated_books/{id}']['patch']['requestBody']['content']['application/merge-patch+json']['schema']['$ref']);
        $this->assertSame([
            'fiction' => '#/components/schemas/DiscriminatedBook.DiscriminatedFictionBook.jsonMergePatch',
            'technical' => '#/components/schemas/DiscriminatedBook.DiscriminatedTechnicalBook.jsonMergePatch',
        ], $patchInput['discriminator']['mapping']);
        $this->assertArrayNotHasKey('required', $schemas['DiscriminatedBook.DiscriminatedFictionBook.jsonMergePatch']);
        $this->assertArrayNotHasKey('required', $schemas['DiscriminatedBook.DiscriminatedTechnicalBook.jsonMergePatch']);

        // JSON:API: the type property is an attribute, each subtype is a variant of the "data" object.
        $jsonApiData = $schemas['DiscriminatedBook.jsonapi']['properties']['data'];
        $this->assertCount(2, $jsonApiData['oneOf']);
        $this->assertSame(['type' => 'string', 'enum' => ['fiction']], $jsonApiData['oneOf'][0]['properties']['attributes']['properties']['kind']);
        $this->assertSame(['kind'], $jsonApiData['oneOf'][0]['properties']['attributes']['required']);
        $this->assertArrayHasKey('sequelOf', $jsonApiData['oneOf'][0]['properties']['relationships']['properties']);
        $this->assertArrayHasKey('programmingLanguage', $jsonApiData['oneOf'][1]['properties']['attributes']['properties']);

        // Resources without a discriminator map are untouched.
        $this->assertArrayNotHasKey('discriminator', $schemas['UndiscriminatedBook.jsonld']['allOf'][1]);
        $this->assertArrayNotHasKey('oneOf', $schemas['UndiscriminatedBook.jsonld']['allOf'][1]);
    }

    /**
     * Resolves a definition, unwrapping the Hydra item decoration if any.
     */
    private function findDefinition(array $schemas, string $ref): array
    {
        $definition = $schemas[substr($ref, \strlen('#/components/schemas/'))];

        return $definition['allOf'][1] ?? $definition;
    }
}
