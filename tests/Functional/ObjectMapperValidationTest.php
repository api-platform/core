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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\UniqueBookResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UniqueBook;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Test entity-level validation after ObjectMapper DTO transformation.
 *
 * @group issue-7725
 */
class ObjectMapperValidationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [UniqueBookResource::class];
    }

    /**
     * Core test for issue #7725: UniqueEntity constraint should be validated
     * after ObjectMapper transforms DTO to Entity, preventing database errors.
     */
    public function testUniqueEntityValidationOnCreate(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('UniqueEntity validation is not supported with MongoDB.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([UniqueBook::class]);

        $client = self::createClient();

        // First book creation should succeed
        $client->request('POST', '/unique_book_resources', [
            'json' => [
                'isbn' => '978-0-13-468599-1',
                'title' => 'Clean Code',
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(201);

        // Ensure the first book is actually persisted
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $entityManager->clear(); // Clear to force fresh queries

        // Verify first book exists in database
        $bookCount = $entityManager->getRepository(UniqueBook::class)->count(['isbn' => '978-0-13-468599-1']);
        $this->assertEquals(1, $bookCount, 'First book should be in database');

        // Second book with same ISBN should return 422 validation error, NOT 500 database error
        $client->request('POST', '/unique_book_resources', [
            'json' => [
                'isbn' => '978-0-13-468599-1',
                'title' => 'Another Book',
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        // Should return 422 validation error, NOT 500 database error
        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');

        // Verify we got the UniqueEntity validation error
        $this->assertJsonContains([
            'status' => 422,
            'hydra:title' => 'An error occurred',
        ]);

        $content = $client->getResponse()->getContent(false);
        $this->assertStringContainsString('This ISBN already exists', $content);
    }

    /**
     * Ensure DTO validation still works (input DTO constraints are checked).
     */
    public function testDtoValidationStillWorks(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('UniqueEntity validation is not supported with MongoDB.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([UniqueBook::class]);

        $client = self::createClient();

        // POST with blank ISBN should trigger DTO validation
        $client->request('POST', '/unique_book_resources', [
            'json' => [
                'isbn' => '',
                'title' => 'Some Book',
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(422);

        // Should have ISBN violation
        $content = $client->getResponse()->getContent(false);
        $this->assertStringContainsString('isbn', $content);
    }

    /**
     * Verify entity-level constraints (not just UniqueEntity) are validated.
     */
    public function testEntityConstraintsValidated(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('UniqueEntity validation is not supported with MongoDB.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([UniqueBook::class]);

        $client = self::createClient();

        // Invalid ISBN format should be caught by entity validation
        $client->request('POST', '/unique_book_resources', [
            'json' => [
                'isbn' => 'not-a-valid-isbn',
                'title' => 'Test Book',
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(422);

        // Should have ISBN format violation
        $content = $client->getResponse()->getContent(false);
        $this->assertStringContainsString('isbn', $content);
        $this->assertStringContainsString('ISBN', $content);
    }
}
