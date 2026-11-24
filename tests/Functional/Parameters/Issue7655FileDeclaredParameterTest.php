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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7655\Author;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7655\Book;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Parameters declared in resource configuration files (YAML here) must get the same metadata as
 * parameters declared with attributes: property defaults, filter schema and OpenAPI parameters,
 * and Doctrine nested property information.
 *
 * @see https://github.com/api-platform/core/issues/7655
 *
 * @group issue-7655
 */
final class Issue7655FileDeclaredParameterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Book::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('The Book resource is declared in the ORM resource configuration only.');
        }

        $this->recreateSchema([Book::class, Author::class]);
        $this->loadFixtures();
    }

    public function testParameterPropertyDefaultsToTheParameterKey(): void
    {
        $response = self::createClient()->request('GET', '/issue7655_books?title=Dune');
        $this->assertResponseIsSuccessful();

        $titles = array_map(static fn (array $book): string => $book['title'], $response->toArray()['hydra:member']);
        $this->assertSame(['Dune'], $titles);
    }

    public function testFilterOnNestedProperty(): void
    {
        $response = self::createClient()->request('GET', '/issue7655_books?authorName=Ursula');
        $this->assertResponseIsSuccessful();

        $titles = array_map(static fn (array $book): string => $book['title'], $response->toArray()['hydra:member']);
        $this->assertSame(['The Dispossessed'], $titles);
    }

    public function testOpenApiDocumentsTheFilterParameters(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $parameterNames = array_column($response->toArray()['paths']['/issue7655_books']['get']['parameters'], 'name');
        $this->assertContains('authorName', $parameterNames);
        $this->assertContains('authorName[]', $parameterNames);
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $frank = (new Author())->setName('Frank');
        $ursula = (new Author())->setName('Ursula');
        $manager->persist($frank);
        $manager->persist($ursula);

        $manager->persist((new Book())->setTitle('Dune')->setAuthor($frank));
        $manager->persist((new Book())->setTitle('The Dispossessed')->setAuthor($ursula));
        $manager->flush();
    }
}
