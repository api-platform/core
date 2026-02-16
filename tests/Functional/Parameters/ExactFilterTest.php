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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Chicken as DocumentChicken;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ChickenCoop as DocumentChickenCoop;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Owner as DocumentOwner;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Chicken;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ChickenCoop;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Owner;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class ExactFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Chicken::class, ChickenCoop::class, Owner::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $entities = $this->isMongoDB()
            ? [DocumentChicken::class, DocumentChickenCoop::class, DocumentOwner::class]
            : [Chicken::class, ChickenCoop::class, Owner::class];

        $this->recreateSchema($entities);
        $this->loadFixtures();
    }

    #[DataProvider('exactSearchFilterProvider')]
    public function testExactSearchFilter(string $url, int $expectedCount, array $expectedNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $names = array_map(static fn (array $chicken) => $chicken['name'], $filteredItems);
        sort($names);
        sort($expectedNames);

        $this->assertSame($expectedNames, $names, 'The names do not match the expected values.');
    }

    public static function exactSearchFilterProvider(): \Generator
    {
        yield 'filter by exact name "Gertrude"' => [
            '/chickens?name=Gertrude',
            1,
            ['Gertrude'],
        ];

        yield 'filter by a non-existent name' => [
            '/chickens?name=Kevin',
            0,
            [],
        ];

        yield 'filter by exact coop id' => [
            '/chickens?chickenCoopId=1',
            1,
            ['Gertrude'],
        ];

        yield 'filter by coop id and correct name' => [
            '/chickens?chickenCoopId=1&name=Gertrude',
            1,
            ['Gertrude'],
        ];

        yield 'filter by coop id and incorrect name' => [
            '/chickens?chickenCoopId=1&name=Henriette',
            0,
            [],
        ];
    }

    #[DataProvider('exactSearchFilterWithOneToManyRelationProvider')]
    public function testExactSearchFilterWithOneToManyRelation(string $url, int $expectedCount, array $expectedChickenNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount($expectedCount, $filteredCoops, \sprintf('Expected %d coops for URL %s', $expectedCount, $url));

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $chickenIri) {
                $chickenResponse = self::createClient()->request('GET', $chickenIri);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        sort($expectedChickenNames);

        $this->assertSame($expectedChickenNames, $allChickenNames, 'The chicken names in coops do not match the expected values.');
    }

    public static function exactSearchFilterWithOneToManyRelationProvider(): \Generator
    {
        yield 'filter coops by exact chicken name (chickens.name) "Gertrude"' => [
            '/chicken_coops?chickenNameExact=Gertrude',
            1,
            ['Gertrude'],
        ];

        yield 'filter coops by a non-existent chicken name (chickens.name)' => [
            '/chicken_coops?chickenNameExact=Kevin',
            0,
            [],
        ];
    }

    #[DataProvider('exactSearchFilterWithOneToManyRelationWithPropertyPlaceholderProvider')]
    public function testExactSearchFilterWithOneToManyRelationWithPropertyPlaceholder(string $url, int $expectedCount, array $expectedChickenNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount($expectedCount, $filteredCoops, \sprintf('Expected %d coops for URL %s', $expectedCount, $url));

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $chickenIri) {
                $chickenResponse = self::createClient()->request('GET', $chickenIri);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        sort($expectedChickenNames);

        $this->assertSame($expectedChickenNames, $allChickenNames, 'The chicken names in coops do not match the expected values.');
    }

    public static function exactSearchFilterWithOneToManyRelationWithPropertyPlaceholderProvider(): \Generator
    {
        yield 'filter coops by exact chicken name (chickens.name) "Gertrude" using :property placeholder' => [
            '/chicken_coops?searchChickenNameExact[chickens.name]=Gertrude',
            1,
            ['Gertrude'],
        ];

        yield 'filter coops by a non-existent chicken name (chickens.name) using :property placeholder' => [
            '/chicken_coops?searchChickenNameExact[chickens.name]=Kevin',
            0,
            [],
        ];
    }

    #[DataProvider('exactFilterWithTwoLevelTraversalProvider')]
    public function testExactFilterWithTwoLevelTraversal(string $url, int $expectedCount, array $expectedChickenNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount($expectedCount, $filteredCoops, \sprintf('Expected %d coops for URL %s', $expectedCount, $url));

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $chickenIri) {
                $chickenResponse = self::createClient()->request('GET', $chickenIri);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        sort($expectedChickenNames);

        $this->assertSame($expectedChickenNames, $allChickenNames, 'The chicken names in coops do not match the expected values.');
    }

    public static function exactFilterWithTwoLevelTraversalProvider(): \Generator
    {
        yield 'filter coops by exact chicken owner name (chickens.owner.name) matching "Alice"' => [
            '/chicken_coops?chickenOwnerNameExact=Alice',
            2,
            ['Gertrude', 'xx_%_\\_%_xx'],
        ];

        yield 'filter coops by exact chicken owner name (chickens.owner.name) matching "Bob"' => [
            '/chicken_coops?chickenOwnerNameExact=Bob',
            2,
            ['Henriette', 'GÀgù'],
        ];

        yield 'filter coops by exact chicken owner name (chickens.owner.name) matching "b"' => [
            '/chicken_coops?chickenOwnerNameExact=b',
            0,
            [],
        ];
    }

    #[DataProvider('exactFilterWithTwoLevelTraversalWithPropertyPlaceholderProvider')]
    public function testExactFilterWithTwoLevelTraversalWithPropertyPlaceholder(string $url, int $expectedCount, array $expectedChickenNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount($expectedCount, $filteredCoops, \sprintf('Expected %d coops for URL %s', $expectedCount, $url));

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $chickenIri) {
                $chickenResponse = self::createClient()->request('GET', $chickenIri);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        sort($expectedChickenNames);

        $this->assertSame($expectedChickenNames, $allChickenNames, 'The chicken names in coops do not match the expected values.');
    }

    public static function exactFilterWithTwoLevelTraversalWithPropertyPlaceholderProvider(): \Generator
    {
        yield 'filter coops by exact chicken owner name (chickens.owner.name) matching "Alice" using :property placeholder' => [
            '/chicken_coops?searchChickenOwnerNameExact[chickens.owner.name]=Alice',
            2,
            ['Gertrude', 'xx_%_\\_%_xx'],
        ];

        yield 'filter coops by exact chicken owner name (chickens.owner.name) matching "Bob" using :property placeholder' => [
            '/chicken_coops?searchChickenOwnerNameExact[chickens.owner.name]=Bob',
            2,
            ['Henriette', 'GÀgù'],
        ];

        yield 'filter coops by exact chicken owner name (chickens.owner.name) matching "b" using :property placeholder' => [
            '/chicken_coops?searchChickenOwnerNameExact[chickens.owner.name]=b',
            0,
            [],
        ];
    }

    #[DataProvider('exactFilterWithManyToOneRelationProvider')]
    public function testExactFilterWithManyToOneRelation(string $url, int $expectedCount, array $expectedChickenNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredChickens = $responseData['member'];

        $this->assertCount($expectedCount, $filteredChickens, \sprintf('Expected %d chickens for URL %s', $expectedCount, $url));

        $names = array_map(static fn (array $chicken) => $chicken['name'], $filteredChickens);
        sort($names);
        sort($expectedChickenNames);

        $this->assertSame($expectedChickenNames, $names, 'The chicken names do not match the expected values.');
    }

    public static function exactFilterWithManyToOneRelationProvider(): \Generator
    {
        yield 'filter chickens by exact owner name (owner.name) matching "Alice"' => [
            '/chickens?ownerNameExact=Alice',
            2,
            ['Gertrude', 'xx_%_\\_%_xx'],
        ];

        yield 'filter chickens by exact owner name (owner.name) matching "Bob"' => [
            '/chickens?ownerNameExact=Bob',
            2,
            ['Henriette', 'GÀgù'],
        ];

        yield 'filter chickens by exact owner name (owner.name) matching "b"' => [
            '/chickens?ownerNameExact=b',
            0,
            [],
        ];
    }

    #[DataProvider('exactFilterWithManyToOneRelationWithPropertyPlaceholderProvider')]
    public function testExactFilterWithManyToOneRelationWithPropertyPlaceholder(string $url, int $expectedCount, array $expectedChickenNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredChickens = $responseData['member'];

        $this->assertCount($expectedCount, $filteredChickens, \sprintf('Expected %d chickens for URL %s', $expectedCount, $url));

        $names = array_map(static fn (array $chicken) => $chicken['name'], $filteredChickens);
        sort($names);
        sort($expectedChickenNames);

        $this->assertSame($expectedChickenNames, $names, 'The chicken names do not match the expected values.');
    }

    public static function exactFilterWithManyToOneRelationWithPropertyPlaceholderProvider(): \Generator
    {
        yield 'filter chickens by exact owner name (owner.name) matching "Alice" using :property placeholder' => [
            '/chickens?searchOwnerNameExact[owner.name]=Alice',
            2,
            ['Gertrude', 'xx_%_\\_%_xx'],
        ];

        yield 'filter chickens by exact owner name (owner.name) matching "Bob" using :property placeholder' => [
            '/chickens?searchOwnerNameExact[owner.name]=Bob',
            2,
            ['Henriette', 'GÀgù'],
        ];

        yield 'filter chickens by exact owner name (owner.name) matching "b" using :property placeholder' => [
            '/chickens?searchOwnerNameExact[owner.name]=b',
            0,
            [],
        ];
    }

    public function testExactSearchFilterThrowsExceptionWhenPropertyIsMissing(): void
    {
        $response = self::createClient()->request('GET', '/chickens?nameExactNoProperty=Gertrude');
        $this->assertResponseStatusCodeSame(400);

        $responseData = $response->toArray(false);

        $this->assertSame('An error occurred', $responseData['hydra:title']);
        $this->assertSame($responseData['hydra:description'], $responseData['detail']);
        $this->assertStringContainsString(
            'The filter parameter with key "nameExactNoProperty" must specify a property',
            $responseData['detail']
        );
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $chickenClass = $this->isMongoDB() ? DocumentChicken::class : Chicken::class;
        $coopClass = $this->isMongoDB() ? DocumentChickenCoop::class : ChickenCoop::class;
        $ownerClass = $this->isMongoDB() ? DocumentOwner::class : Owner::class;

        $owner1 = new $ownerClass();
        $owner1->setName('Alice');

        $owner2 = new $ownerClass();
        $owner2->setName('Bob');

        $manager->persist($owner1);
        $manager->persist($owner2);
        $manager->flush();

        $chickenCoop1 = new $coopClass();
        $chickenCoop2 = new $coopClass();
        $chickenCoop3 = new $coopClass();
        $chickenCoop4 = new $coopClass();

        $chicken1 = new $chickenClass();
        $chicken1->setName('Gertrude');
        $chicken1->setChickenCoop($chickenCoop1);
        $chicken1->setOwner($owner1);

        $chicken2 = new $chickenClass();
        $chicken2->setName('Henriette');
        $chicken2->setChickenCoop($chickenCoop2);
        $chicken2->setOwner($owner2);

        $chicken3 = new $chickenClass();
        $chicken3->setName('xx_%_\\_%_xx');
        $chicken3->setChickenCoop($chickenCoop3);
        $chicken3->setOwner($owner1);

        $chicken4 = new $chickenClass();
        $chicken4->setName('GÀgù');
        $chicken4->setChickenCoop($chickenCoop4);
        $chicken4->setOwner($owner2);

        $chickenCoop1->addChicken($chicken1);
        $chickenCoop2->addChicken($chicken2);
        $chickenCoop3->addChicken($chicken3);
        $chickenCoop4->addChicken($chicken4);

        $manager->persist($chickenCoop1);
        $manager->persist($chickenCoop2);
        $manager->persist($chickenCoop3);
        $manager->persist($chickenCoop4);
        $manager->persist($chicken1);
        $manager->persist($chicken2);
        $manager->persist($chicken3);
        $manager->persist($chicken4);

        $manager->flush();
    }
}
