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
use ApiPlatform\Test\Client;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Headers;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RuntimeResource\Document;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RuntimeResource\Generation;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RuntimeResource\ResourceDefinition;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\DefinitionProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeResources;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Cache\DataCollector\CacheDataCollector;

final class RuntimeResourceMongoDbTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private const JSONLD = ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'];

    public static function getResources(): array
    {
        return [Headers::class];
    }

    protected function setUp(): void
    {
        $_SERVER['RUNTIME_RESOURCES'] = '1';
        self::ensureKernelShutdown();
        self::bootKernel();
        if (!$this->isMongoDB()) {
            $this->markTestSkipped('Doctrine MongoDB ODM only.');
        }

        $this->recreateSchema([ResourceDefinition::class, Document::class, Generation::class]);
        static::getContainer()->get(DefinitionProvider::class)->publish(static function (DocumentManager $manager): void {
            $manager->persist((new ResourceDefinition('author', 'Author'))
                ->addAttribute('name', 'string', required: true)
                ->addAttribute('birthDate', 'datetime')
                ->addAttribute('books', 'reference', collection: true, options: ['target' => 'book']));
            $manager->persist((new ResourceDefinition('book', 'Book'))
                ->addAttribute('title', 'string', required: true, options: ['maxLength' => 255])
                ->addAttribute('isbn', 'string')
                ->addAttribute('price', 'float')
                ->addAttribute('genre', 'enum', options: ['values' => ['novel', 'essay', 'poetry']], filterable: true)
                ->addAttribute('available', 'boolean')
                ->addAttribute('author', 'reference', options: ['target' => 'author'], filterable: true));
            $manager->persist(new Document('author', ['name' => 'Ursula K. Le Guin', 'birthDate' => new UTCDateTime((new \DateTimeImmutable('1929-10-21'))->getTimestamp() * 1000), 'books' => [3]]));
            $manager->persist(new Document('author', ['name' => 'Italo Calvino', 'birthDate' => new UTCDateTime((new \DateTimeImmutable('1923-10-15'))->getTimestamp() * 1000), 'books' => [4]]));
            $manager->persist(new Document('book', ['title' => 'The Dispossessed', 'isbn' => '9780061054884', 'price' => 9.99, 'genre' => 'novel', 'available' => true, 'author' => 1]));
            $manager->persist(new Document('book', ['title' => 'Six Memos for the Next Millennium', 'price' => 14.5, 'genre' => 'essay', 'available' => false, 'author' => 2]));
        });
        self::ensureKernelShutdown();
        self::invalidateMetadataPools();
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown();
        unset($_SERVER['RUNTIME_RESOURCES']);
        parent::tearDown();
    }

    public function testGetItemEmbedsReference(): void
    {
        $client = self::createClient();
        $client->request('POST', '/runtime/book', ['headers' => self::JSONLD, 'json' => ['title' => 'The Left Hand of Darkness', 'author' => '/runtime/author/1']]);
        $this->assertResponseStatusCodeSame(201);
        $client->request('PATCH', '/runtime/author/1', ['headers' => ['Content-Type' => 'application/merge-patch+json', 'Accept' => 'application/ld+json'], 'json' => ['books' => ['/runtime/book/3', '/runtime/book/5']]]);
        $this->assertResponseIsSuccessful();

        $json = $client->request('GET', '/runtime/book/3', ['headers' => self::JSONLD])->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertSame('/contexts/Book', $json['@context']);
        $this->assertSame('/runtime/book/3', $json['@id']);
        $this->assertSame('Book', $json['@type']);
        $this->assertSame('The Dispossessed', $json['title']);
        $this->assertSame('9780061054884', $json['isbn']);
        $this->assertSame(9.99, $json['price']);
        $this->assertSame('novel', $json['genre']);
        $this->assertTrue($json['available']);
        $this->assertSame(['@id' => '/runtime/author/1', '@type' => 'Author', 'id' => 1, 'name' => 'Ursula K. Le Guin', 'birthDate' => '1929-10-21T00:00:00+00:00', 'books' => ['/runtime/book/3', '/runtime/book/5']], $json['author']);
    }

    public function testGetCollectionIsScopedToResource(): void
    {
        $json = self::createClient()->request('GET', '/runtime/book', ['headers' => self::JSONLD])->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertSame('/runtime/book', $json['@id']);
        $this->assertSame(2, $json['hydra:totalItems']);
        $this->assertSame(['/runtime/book/3', '/runtime/book/4'], array_column($json['hydra:member'], '@id'));
        $this->assertSame(['Book', 'Book'], array_column($json['hydra:member'], '@type'));
        $this->assertSame(['Ursula K. Le Guin', 'Italo Calvino'], array_column(array_column($json['hydra:member'], 'author'), 'name'));
    }

    public function testUnknownResourceIs404(): void
    {
        $client = self::createClient();
        $client->request('GET', '/runtime/publisher', ['headers' => self::JSONLD]);
        $this->assertResponseStatusCodeSame(404);
        $client->request('GET', '/runtime/author/3', ['headers' => self::JSONLD]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPost(): void
    {
        $client = self::createClient();
        $json = $client->request('POST', '/runtime/book', ['headers' => self::JSONLD, 'json' => ['title' => 'The Lathe of Heaven', 'genre' => 'novel', 'price' => 12, 'author' => '/runtime/author/1']])->toArray();

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('/runtime/book/5', $json['@id']);
        $this->assertSame('The Lathe of Heaven', $json['title']);
        $this->assertSame(12, $json['price']);
        $this->assertNull($json['isbn']);
        $this->assertSame('Ursula K. Le Guin', $json['author']['name']);

        $json = $client->request('GET', '/runtime/book/5', ['headers' => self::JSONLD])->toArray();
        $this->assertSame('The Lathe of Heaven', $json['title']);
        $this->assertSame('/runtime/author/1', $json['author']['@id']);
    }

    public function testPutReplaces(): void
    {
        $client = self::createClient();
        $json = $client->request('PUT', '/runtime/book/3', ['headers' => self::JSONLD, 'json' => ['title' => 'The Dispossessed: An Ambiguous Utopia', 'genre' => 'novel', 'author' => '/runtime/author/2']])->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertSame('/runtime/book/3', $json['@id']);
        $this->assertSame('The Dispossessed: An Ambiguous Utopia', $json['title']);
        $this->assertNull($json['isbn']);
        $this->assertSame('/runtime/author/2', $json['author']['@id']);

        $json = $client->request('GET', '/runtime/book/3', ['headers' => self::JSONLD])->toArray();
        $this->assertNull($json['price']);
        $this->assertSame('Italo Calvino', $json['author']['name']);
    }

    public function testPatchMergesScalar(): void
    {
        $client = self::createClient();
        $client->request('PATCH', '/runtime/book/3', ['headers' => ['Content-Type' => 'application/merge-patch+json', 'Accept' => 'application/ld+json'], 'json' => ['price' => 12.5]]);
        $this->assertResponseIsSuccessful();

        $json = $client->request('GET', '/runtime/book/3', ['headers' => self::JSONLD])->toArray();
        $this->assertSame(12.5, $json['price']);
        $this->assertSame('The Dispossessed', $json['title']);
        $this->assertSame('9780061054884', $json['isbn']);
        $this->assertSame('novel', $json['genre']);
        $this->assertTrue($json['available']);
        $this->assertSame('/runtime/author/1', $json['author']['@id']);
    }

    public function testDelete(): void
    {
        $client = self::createClient();
        $client->request('DELETE', '/runtime/book/4');
        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/runtime/book/4', ['headers' => self::JSONLD]);
        $this->assertResponseStatusCodeSame(404);
        $client->request('GET', '/runtime/author/2', ['headers' => self::JSONLD]);
        $this->assertResponseIsSuccessful();
        $client->request('DELETE', '/runtime/book/2');
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @param array<string, mixed> $book
     */
    #[DataProvider('invalidBooks')]
    public function testInvalidBookIs422(array $book, string $propertyPath): void
    {
        $response = self::createClient()->request('POST', '/runtime/book', ['headers' => self::JSONLD, 'json' => $book]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([$propertyPath], array_column($response->toArray(false)['violations'], 'propertyPath'));
    }

    public static function invalidBooks(): iterable
    {
        yield 'missing title' => [['genre' => 'novel'], 'title'];
        yield 'too long title' => [['title' => str_repeat('a', 256)], 'title'];
        yield 'unknown genre' => [['title' => 'Earthsea', 'genre' => 'novella'], 'genre'];
        yield 'book IRI whose id is an author' => [['title' => 'Earthsea', 'author' => '/runtime/book/1'], 'author'];
        yield 'book IRI in author' => [['title' => 'Earthsea', 'author' => '/runtime/book/3'], 'author'];
        yield 'unresolvable IRI' => [['title' => 'Earthsea', 'author' => '/runtime/author/999'], 'author'];
    }

    public function testFilterByEnum(): void
    {
        $json = self::createClient()->request('GET', '/runtime/book?genre=novel', ['headers' => self::JSONLD])->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertSame(['/runtime/book/3'], array_column($json['hydra:member'], '@id'));
    }

    public function testFilterByReference(): void
    {
        $client = self::createClient();
        $json = $client->request('GET', '/runtime/book?author=/runtime/author/2', ['headers' => self::JSONLD])->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertSame(['/runtime/book/4'], array_column($json['hydra:member'], '@id'));

        $json = $client->request('GET', '/runtime/book?author=/runtime/book/3', ['headers' => self::JSONLD])->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertSame([], $json['hydra:member']);
    }

    public function testOpenApi(): void
    {
        $openApi = self::createClient()->request('GET', '/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']])->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertSame(['/runtime/author', '/runtime/author/{id}', '/runtime/book', '/runtime/book/{id}'], $this->runtimePaths($openApi));
        $this->assertSame(['id'], array_column($openApi['paths']['/runtime/book/{id}']['get']['parameters'], 'name'));
        $this->assertSame(['page', 'itemsPerPage', 'pagination', 'partial', 'genre', 'author'], array_column($openApi['paths']['/runtime/book']['get']['parameters'], 'name'));

        $book = $openApi['components']['schemas']['Book'];
        $this->assertSame(['title'], $book['required']);
        $this->assertSame('string', $book['properties']['title']['type']);
        $this->assertSame(255, $book['properties']['title']['maxLength']);
        $this->assertSame(['number', 'null'], $book['properties']['price']['type']);
        $this->assertSame(['boolean', 'null'], $book['properties']['available']['type']);
        $this->assertSame(['novel', 'essay', 'poetry'], $book['properties']['genre']['enum']);
        $this->assertSame([['$ref' => '#/components/schemas/Author'], ['type' => 'null']], $book['properties']['author']['anyOf']);
        $this->assertSame(['name'], $openApi['components']['schemas']['Author']['required']);
        $this->assertSame('date-time', $openApi['components']['schemas']['Author']['properties']['birthDate']['format']);
    }

    public function testPublishIsPickedUpWithoutReboot(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $client->request('GET', '/runtime/book/3', ['headers' => self::JSONLD]);
        $this->assertResponseIsSuccessful();
        $definitions = static::getContainer()->get(DefinitionProvider::class);
        $resources = static::getContainer()->get(RuntimeResources::class);

        $definitions->publish(static function (DocumentManager $manager): void {
            $manager->persist((new ResourceDefinition('publisher', 'Publisher'))->addAttribute('name', 'string', required: true));
        });

        $json = $client->request('POST', '/runtime/publisher', ['headers' => self::JSONLD, 'json' => ['name' => 'Harper & Row']])->toArray();
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('Publisher', $json['@type']);
        $publisher = $resources->classOf('publisher');
        $book = $resources->classOf('book');
        $client->request('GET', '/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();

        $definitions->publish(static function (DocumentManager $manager): void {
            $manager->getRepository(ResourceDefinition::class)->findOneBy(['name' => 'book'])->addAttribute('pages', 'integer');
        });

        $json = $client->request('GET', '/runtime/book/3', ['headers' => self::JSONLD])->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('pages', $json);
        $this->assertNotSame($book, $resources->classOf('book'));
        $this->assertSame($publisher, $resources->classOf('publisher'));
        $openApi = $client->request('GET', '/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']])->toArray();
        $this->assertArrayHasKey('pages', $openApi['components']['schemas']['Book']['properties']);

        $this->assertSame(0, $this->metadataCacheMisses($client, '/runtime/publisher/5'));
        $this->assertGreaterThan(0, $this->metadataCacheMisses($client, '/runtime/author/1'));
    }

    public function testDefinitionSecurity(): void
    {
        self::bootKernel();
        static::getContainer()->get(DefinitionProvider::class)->publish(static function (DocumentManager $manager): void {
            $manager->persist((new ResourceDefinition('secret', 'Secret', security: "is_granted('ROLE_ADMIN')"))->addAttribute('code', 'string'));
        });
        self::ensureKernelShutdown();

        $client = self::createClient();
        $client->request('GET', '/runtime/secret', ['headers' => self::JSONLD]);
        $this->assertResponseStatusCodeSame(401);
        $client->request('GET', '/runtime/secret', ['headers' => self::JSONLD, 'auth_basic' => ['dunglas', 'kevin']]);
        $this->assertResponseStatusCodeSame(403);
        $client->request('GET', '/runtime/secret', ['headers' => self::JSONLD, 'auth_basic' => ['admin', 'kitten']]);
        $this->assertResponseIsSuccessful();
    }

    public function testWorkerModeDoesNotLeakProperties(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        foreach (['/runtime/book/3', '/runtime/author/1', '/runtime/book', '/runtime/author/2', '/runtime/book/4'] as $uri) {
            $json = $client->request('GET', $uri, ['headers' => self::JSONLD])->toArray();
            $this->assertResponseIsSuccessful();
            foreach ($json['hydra:member'] ?? [$json] as $member) {
                unset($member['@context']);
                if ('Book' === $member['@type']) {
                    $this->assertSame(['@id', '@type', 'id', 'title', 'isbn', 'price', 'genre', 'available', 'author'], array_keys($member), $uri);
                    $member = $member['author'];
                }
                $this->assertSame(['@id', '@type', 'id', 'name', 'birthDate', 'books'], array_keys($member), $uri);
            }
        }
    }

    public function testInvalidDefinitionsNeverBreakTheApi(): void
    {
        self::bootKernel();
        static::getContainer()->get(DefinitionProvider::class)->publish(static function (DocumentManager $manager): void {
            foreach (['Book', 'with_underscore', '1book', 'headers'] as $name) {
                $manager->persist(new ResourceDefinition($name));
            }
            $manager->getRepository(ResourceDefinition::class)->findOneBy(['name' => 'author'])
                ->addAttribute('id', 'string')
                ->addAttribute('agent', 'reference', options: ['target' => 'headers']);
        });
        self::ensureKernelShutdown();

        $client = self::createClient();
        $openApi = $client->request('GET', '/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']])->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertSame(['/runtime/author', '/runtime/author/{id}', '/runtime/book', '/runtime/book/{id}'], $this->runtimePaths($openApi));
        $this->assertSame(['id', 'name', 'birthDate', 'books'], array_keys($openApi['components']['schemas']['Author']['properties']));

        foreach (['Book', 'with_underscore', '1book', 'headers'] as $name) {
            $client->request('GET', '/runtime/'.$name, ['headers' => self::JSONLD]);
            $this->assertResponseStatusCodeSame(404, $name);
        }
        $client->request('GET', '/runtime/author/1', ['headers' => self::JSONLD]);
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/redirect_to_foobar');
        $this->assertResponseStatusCodeSame(301);

        $resources = static::getContainer()->get(RuntimeResources::class);
        $this->assertNull($resources->classOf('headers'));
        $this->assertNull($resources->classOf('with_underscore'));
        $this->assertTrue(class_exists((string) $resources->classOf('book')));
    }

    private function metadataCacheMisses(Client $client, string $uri): int
    {
        $client->enableProfiler();
        $client->request('GET', $uri, ['headers' => self::JSONLD]);
        $this->assertResponseIsSuccessful();
        $collector = $client->getProfile()->getCollector('cache');
        $this->assertInstanceOf(CacheDataCollector::class, $collector);

        return array_sum(array_map(static fn (array $statistics): int => $statistics['misses'], array_intersect_key($collector->getStatistics(), array_flip(['api_platform.cache.metadata.resource_collection', 'api_platform.cache.metadata.property']))));
    }

    /**
     * @param array<string, mixed> $openApi
     *
     * @return list<string>
     */
    private function runtimePaths(array $openApi): array
    {
        $paths = array_values(array_filter(array_keys($openApi['paths']), static fn (string $path): bool => str_starts_with($path, '/runtime/')));
        sort($paths);

        return $paths;
    }
}
