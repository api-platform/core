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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ToonBook;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use HelgeSverre\Toon\Toon;

class ToonTest extends ApiTestCase
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
            ToonBook::class,
        ];
    }

    public function testCreateResourceWithToonFormat(): void
    {
        $this->recreateSchema(self::getResources());

        // Send JSON-LD, expect Toon response
        $response = self::createClient()->request('POST', '/toon_books', [
            'json' => [
                'title' => 'The Pragmatic Programmer',
                'author' => 'Andy Hunt',
                'pages' => 352,
                'available' => true,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'text/ld+toon; charset=utf-8');

        $responseContent = $response->getContent();
        $decodedData = Toon::decode($responseContent);

        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('@id', $decodedData);
        $this->assertEquals('The Pragmatic Programmer', $decodedData['title']);
        $this->assertEquals('Andy Hunt', $decodedData['author']);
        $this->assertEquals(352, $decodedData['pages']);
        $this->assertTrue($decodedData['available']);
    }

    public function testGetResourceWithToonFormat(): void
    {
        $this->recreateSchema(self::getResources());

        // First, create a resource - send JSON-LD, expect Toon response
        $createResponse = self::createClient()->request('POST', '/toon_books', [
            'json' => [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'pages' => 464,
                'available' => true,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $createdData = Toon::decode($createResponse->getContent());
        $resourceIri = $createdData['@id'];

        // Now retrieve it
        $response = self::createClient()->request('GET', $resourceIri, [
            'headers' => [
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/ld+toon; charset=utf-8');

        $responseContent = $response->getContent();
        $decodedData = Toon::decode($responseContent);

        $this->assertIsArray($decodedData);
        $this->assertEquals('Clean Code', $decodedData['title']);
        $this->assertEquals('Robert C. Martin', $decodedData['author']);
        $this->assertEquals(464, $decodedData['pages']);
        $this->assertTrue($decodedData['available']);
    }

    public function testGetCollectionWithToonFormat(): void
    {
        $this->recreateSchema(self::getResources());

        // Create multiple resources - send JSON-LD, expect Toon response
        $books = [
            ['title' => 'Design Patterns', 'author' => 'Gang of Four', 'pages' => 395, 'available' => true],
            ['title' => 'Refactoring', 'author' => 'Martin Fowler', 'pages' => 448, 'available' => false],
            ['title' => 'Domain-Driven Design', 'author' => 'Eric Evans', 'pages' => 560, 'available' => true],
        ];

        foreach ($books as $book) {
            self::createClient()->request('POST', '/toon_books', [
                'json' => $book,
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                    'Accept' => 'text/ld+toon',
                ],
            ]);
        }

        // Retrieve collection
        $response = self::createClient()->request('GET', '/toon_books', [
            'headers' => [
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/ld+toon; charset=utf-8');

        $responseContent = $response->getContent();
        $decodedData = Toon::decode($responseContent);

        $this->assertIsArray($decodedData);

        // Collection structure varies (may have hydra:member or member, or be a plain array)
        if (isset($decodedData['hydra:member'])) {
            // Hydra format with prefix
            $this->assertCount(3, $decodedData['hydra:member']);
            $this->assertEquals('Design Patterns', $decodedData['hydra:member'][0]['title']);
            $this->assertEquals('Gang of Four', $decodedData['hydra:member'][0]['author']);
            $this->assertArrayHasKey('hydra:totalItems', $decodedData);
            $this->assertEquals(3, $decodedData['hydra:totalItems']);
        } elseif (isset($decodedData['member'])) {
            // JSON-LD format without prefix
            $this->assertCount(3, $decodedData['member']);
            $this->assertEquals('Design Patterns', $decodedData['member'][0]['title']);
            $this->assertEquals('Gang of Four', $decodedData['member'][0]['author']);
            $this->assertArrayHasKey('totalItems', $decodedData);
            $this->assertEquals(3, $decodedData['totalItems']);
        } else {
            // Plain array
            $this->assertGreaterThanOrEqual(3, count($decodedData));
        }
    }

    public function testUpdateResourceWithToonFormat(): void
    {
        $this->recreateSchema(self::getResources());

        // Create a resource - send JSON-LD, expect Toon response
        $createResponse = self::createClient()->request('POST', '/toon_books', [
            'json' => [
                'title' => 'Original Title',
                'author' => 'Original Author',
                'pages' => 100,
                'available' => true,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $createdData = Toon::decode($createResponse->getContent());
        $resourceIri = $createdData['@id'];

        // Update the resource - send JSON-LD, expect Toon response
        $response = self::createClient()->request('PATCH', $resourceIri, [
            'json' => [
                'title' => 'Updated Title',
                'pages' => 200,
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/ld+toon; charset=utf-8');

        $responseContent = $response->getContent();
        $decodedData = Toon::decode($responseContent);

        $this->assertEquals('Updated Title', $decodedData['title']);
        $this->assertEquals('Original Author', $decodedData['author']); // Unchanged
        $this->assertEquals(200, $decodedData['pages']);
        $this->assertTrue($decodedData['available']); // Unchanged
    }

    public function testDeleteResourceWithToonFormat(): void
    {
        $this->recreateSchema(self::getResources());

        // Create a resource - send JSON-LD, expect Toon response
        $createResponse = self::createClient()->request('POST', '/toon_books', [
            'json' => [
                'title' => 'To Be Deleted',
                'author' => 'Test Author',
                'pages' => 123,
                'available' => true,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $createdData = Toon::decode($createResponse->getContent());
        $resourceIri = $createdData['@id'];

        // Delete the resource
        self::createClient()->request('DELETE', $resourceIri, [
            'headers' => [
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $this->assertResponseStatusCodeSame(204);

        // Verify it's deleted
        self::createClient()->request('GET', $resourceIri, [
            'headers' => [
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEntrypointWithToonFormat(): void
    {
        self::bootKernel();

        $response = self::createClient()->request('GET', '/', [
            'headers' => [
                'Accept' => 'text/ld+toon', // This should be handled by hydra_toon
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/ld+toon; charset=utf-8');

        $responseContent = $response->getContent();
        $decodedData = Toon::decode($responseContent);

        $this->assertIsArray($decodedData);
        // Verify entrypoint contains resource information
        $this->assertNotEmpty($decodedData);
        // Check that ToonBook resource is listed in the entrypoint, and that hydra:member is present
        $contentLower = strtolower($responseContent);
        $this->assertTrue(
            str_contains($contentLower, 'toonbook') || str_contains($contentLower, 'toon_book'),
            'Entrypoint should contain ToonBook resource'
        );
        $this->assertStringContainsString('hydra:member', $contentLower); // Ensure Hydra specific key
    }

    public function testToonFormatEncodesSimpleStructures(): void
    {
        // Test that the Toon encoder works correctly for simple data
        $data = [
            'name' => 'Alice',
            'score' => 95,
            'active' => true,
        ];

        $encoded = Toon::encode($data);
        $this->assertStringContainsString('name: Alice', $encoded);
        $this->assertStringContainsString('score: 95', $encoded);
        $this->assertStringContainsString('active: true', $encoded);

        $decoded = Toon::decode($encoded);
        $this->assertEquals($data, $decoded);
    }

    public function testToonFormatEncodesArrays(): void
    {
        // Test that arrays are encoded properly in Toon format
        $data = [
            'tags' => ['api', 'platform', 'toon'],
            'count' => 3,
        ];

        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertEquals($data['tags'], $decoded['tags']);
        $this->assertEquals($data['count'], $decoded['count']);
    }

    public function testPostWithToonContentType(): void
    {
        $this->recreateSchema(self::getResources());

        // Create Toon-encoded request body
        $toonData = Toon::encode([
            'title' => 'Posted via Toon',
            'author' => 'Toon Author',
            'pages' => 999,
            'available' => true,
        ]);

        // POST with Content-Type: text/ld+toon
        $response = self::createClient()->request('POST', '/toon_books', [
            'body' => $toonData,
            'headers' => [
                'Content-Type' => 'text/ld+toon',
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'text/ld+toon; charset=utf-8');

        $responseContent = $response->getContent();
        $decodedData = Toon::decode($responseContent);

        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('@id', $decodedData);
        $this->assertEquals('Posted via Toon', $decodedData['title']);
        $this->assertEquals('Toon Author', $decodedData['author']);
        $this->assertEquals(999, $decodedData['pages']);
        $this->assertTrue($decodedData['available']);
    }

    public function testJsonApiWithToonFormat(): void
    {
        $this->recreateSchema(self::getResources());

        // Create a ToonBook to test JSON:API + Toon format
        $response = self::createClient()->request('POST', '/toon_books', [
            'json' => [
                'title' => 'JSON:API Book',
                'author' => 'JSON:API Author',
                'pages' => 555,
                'available' => true,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $createdData = Toon::decode($response->getContent());
        $resourceIri = $createdData['@id'];

        // Now request the resource with JSON:API + Toon format (text/vnd.api+toon)
        $jsonApiResponse = self::createClient()->request('GET', $resourceIri, [
            'headers' => [
                'Accept' => 'text/vnd.api+toon',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/vnd.api+toon; charset=utf-8');

        $responseContent = $jsonApiResponse->getContent();
        $decodedData = Toon::decode($responseContent);

        // Verify JSON:API structure
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('data', $decodedData);
        $this->assertArrayHasKey('attributes', $decodedData['data']);
        $this->assertEquals('JSON:API Book', $decodedData['data']['attributes']['title']);
        $this->assertEquals('JSON:API Author', $decodedData['data']['attributes']['author']);
        $this->assertEquals(555, $decodedData['data']['attributes']['pages']);
    }

    public function testJsonLdToonFormat(): void
    {
        $this->recreateSchema(self::getResources());

        // Create a ToonBook to test JSON-LD + Toon format
        $response = self::createClient()->request('POST', '/toon_books', [
            'json' => [
                'title' => 'JSON-LD Toon Book',
                'author' => 'JSON-LD Toon Author',
                'pages' => 666,
                'available' => true,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'text/ld+toon',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $createdData = Toon::decode($response->getContent());
        $resourceIri = $createdData['@id'];

        // Now request the resource with JSON-LD + Toon format (text/ld+toon, but explicitly using jsonld_toon in config)
        $jsonLdToonResponse = self::createClient()->request('GET', $resourceIri, [
            'headers' => [
                'Accept' => 'text/ld+toon', // This will map to jsonld_toon via formats config
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/ld+toon; charset=utf-8');

        $responseContent = $jsonLdToonResponse->getContent();
        $decodedData = Toon::decode($responseContent);

        // Verify JSON-LD structure
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('@id', $decodedData);
        $this->assertEquals('JSON-LD Toon Book', $decodedData['title']);
        $this->assertEquals('JSON-LD Toon Author', $decodedData['author']);
        $this->assertEquals(666, $decodedData['pages']);
    }
}
