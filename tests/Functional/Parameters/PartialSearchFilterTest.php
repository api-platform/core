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
final class PartialSearchFilterTest extends ApiTestCase
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

    #[DataProvider('partialSearchFilterProvider')]
    public function testPartialSearchFilter(string $url, int $expectedCount, array $expectedNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $names = array_map(static fn (array $chicken) => $chicken['name'], $filteredItems);
        sort($names);
        sort($expectedNames);

        $this->assertSame($expectedNames, $names, 'The returned names do not match the expected values.');
    }

    public static function partialSearchFilterProvider(): \Generator
    {
        yield 'filter by partial name "ertrude"' => [
            '/chickens?namePartial=ertrude',
            1,
            ['Gertrude'],
        ];

        yield 'filter by partial name "riette"' => [
            '/chickens?namePartial=riette',
            1,
            ['Henriette'],
        ];

        yield 'filter by partial name "e" (should match both)' => [
            '/chickens?namePartial=e',
            2,
            ['Gertrude', 'Henriette'],
        ];

        yield 'filter by partial name with no matching entities' => [
            '/chickens?namePartial=Zebra',
            0,
            [],
        ];

        yield 'filter with multiple partial names "rude" OR "iette"' => [
            '/chickens?namePartial[]=rude&namePartial[]=iette',
            2,
            ['Gertrude', 'Henriette'],
        ];

        yield 'filter with multiple partial names, one matching "Gert," the other not matching "Zebra"' => [
            '/chickens?namePartial[]=Gert&namePartial[]=Zebra',
            1,
            ['Gertrude'],
        ];

        yield 'filter with multiple partial names without matches' => [
            '/chickens?namePartial[]=Toto&namePartial[]=Match',
            0,
            [],
        ];

        yield 'filter by partial name "%"' => [
            '/chickens?namePartial=%25',
            1,
            ['xx_%_\\_%_xx'],
        ];

        yield 'filter by partial name "_"' => [
            '/chickens?namePartial=%5F',
            1,
            ['xx_%_\\_%_xx'],
        ];

        yield 'filter by partial name "\"' => [
            '/chickens?namePartial=%5C',
            1,
            ['xx_%_\\_%_xx'],
        ];

        yield 'filter by partial name "\_"' => [
            '/chickens?namePartial=%5C%5F',
            1,
            ['xx_%_\\_%_xx'],
        ];
    }

    #[DataProvider('partialSearchFilterWithOneToManyRelationProvider')]
    public function testPartialSearchFilterWithOneToManyRelation(string $url, int $expectedCount, array $expectedChickenNames): void
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

    public static function partialSearchFilterWithOneToManyRelationProvider(): \Generator
    {
        yield 'filter coops by chicken name (chickens.name) containing "ertrude"' => [
            '/chicken_coops?chickenNamePartial=ertrude',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) containing "riette"' => [
            '/chicken_coops?chickenNamePartial=riette',
            1,
            ['Henriette'],
        ];

        yield 'filter coops by chicken name (chickens.name) containing "e"' => [
            '/chicken_coops?chickenNamePartial=e',
            2,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù', 'Henriette'],
        ];

        yield 'filter coops by chicken name (chickens.name) with no matching entities' => [
            '/chicken_coops?chickenNamePartial=Zebra',
            0,
            [],
        ];

        yield 'filter coops by chicken name (chickens.name) "xx"' => [
            '/chicken_coops?chickenNamePartial=xx',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) with multiple partial names "rude" OR "iette"' => [
            '/chicken_coops?chickenNamePartial[]=rude&chickenNamePartial[]=iette',
            2,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù', 'Henriette'],
        ];

        yield 'filter coops by chicken name (chickens.name) with multiple partial names, one matching "Gert," the other not matching "Zebra"' => [
            '/chicken_coops?chickenNamePartial[]=Gert&chickenNamePartial[]=Zebra',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) with multiple partial names without matches' => [
            '/chicken_coops?chickenNamePartial[]=Toto&chickenNamePartial[]=Match',
            0,
            [],
        ];

        yield 'filter coops by chicken name (chickens.name) "%"' => [
            '/chicken_coops?chickenNamePartial=%25',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) "_"' => [
            '/chicken_coops?chickenNamePartial=%5F',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) "\\"' => [
            '/chicken_coops?chickenNamePartial=%5C',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) "\\_"' => [
            '/chicken_coops?chickenNamePartial=%5C%5F',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];
    }

    #[DataProvider('partialSearchFilterWithOneToManyRelationWithPropertyPlaceholderProvider')]
    public function testPartialSearchFilterWithOneToManyRelationWithPropertyPlaceholder(string $url, int $expectedCount, array $expectedChickenNames): void
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

    public static function partialSearchFilterWithOneToManyRelationWithPropertyPlaceholderProvider(): \Generator
    {
        yield 'filter coops by chicken name (chickens.name) containing "ertrude" using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name]=ertrude',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) containing "riette" using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name]=riette',
            1,
            ['Henriette'],
        ];

        yield 'filter coops by chicken name (chickens.name) containing "e" using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name]=e',
            2,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù', 'Henriette'],
        ];

        yield 'filter coops by chicken name (chickens.name) with no matching entities using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name]=Zebra',
            0,
            [],
        ];

        yield 'filter coops by chicken name (chickens.name) "xx" using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name]=xx',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) with multiple partial names "rude" OR "iette" using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name][]=rude&searchChickenNamePartial[chickens.name][]=iette',
            2,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù', 'Henriette'],
        ];

        yield 'filter coops by chicken name (chickens.name) with multiple partial names, one matching "Gert," the other not matching "Zebra" using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name][]=Gert&searchChickenNamePartial[chickens.name][]=Zebra',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) with multiple partial names without matches using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name][]=Toto&searchChickenNamePartial[chickens.name][]=Match',
            0,
            [],
        ];

        yield 'filter coops by chicken name (chickens.name) "%" using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name]=%25',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) "_" using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name]=%5F',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) "\\" using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name]=%5C',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken name (chickens.name) "\\_" using :property placeholder' => [
            '/chicken_coops?searchChickenNamePartial[chickens.name]=%5C%5F',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];
    }

    #[DataProvider('partialSearchMultiByteFilterProvider')]
    public function testPartialSearchMultiByteFilter(string $url, int $expectedCount, array $expectedNames): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('Multibyte LIKE are not properly handled with sqlite.');
        }

        $this->testPartialSearchFilter($url, $expectedCount, $expectedNames);
    }

    public static function partialSearchMultiByteFilterProvider(): \Generator
    {
        yield 'filter by partial name "gà"' => [
            '/chickens?namePartial[]=gà',
            1,
            ['GÀgù'],
        ];

        yield 'filter by partial name "gù"' => [
            '/chickens?namePartial[]=gù',
            1,
            ['GÀgù'],
        ];

        yield 'filter by partial name "gÀ"' => [
            '/chickens?namePartial[]=g%C3%80',
            1,
            ['GÀgù'],
        ];

        yield 'filter by partial name "gÙ"' => [
            '/chickens?namePartial[]=g%C3%99',
            1,
            ['GÀgù'],
        ];
    }

    #[DataProvider('partialSearchFilterWithTwoLevelTraversalProvider')]
    public function testPartialSearchFilterWithTwoLevelTraversal(string $url, int $expectedCount, array $expectedChickenNames): void
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

    public static function partialSearchFilterWithTwoLevelTraversalProvider(): \Generator
    {
        yield 'filter coops by chicken owner name (chickens.owner.name) containing "Alice"' => [
            '/chicken_coops?chickenOwnerNamePartial=Alice',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken owner name (chickens.owner.name) containing "Bob"' => [
            '/chicken_coops?chickenOwnerNamePartial=Bob',
            2,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù', 'Henriette'],
        ];

        yield 'filter coops by chicken owner name (chickens.owner.name) containing "b"' => [
            '/chicken_coops?chickenOwnerNamePartial=b',
            2,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù', 'Henriette'],
        ];
    }

    #[DataProvider('partialSearchFilterWithTwoLevelTraversalWithPropertyPlaceholderProvider')]
    public function testPartialSearchFilterWithTwoLevelTraversalWithPropertyPlaceholder(string $url, int $expectedCount, array $expectedChickenNames): void
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

    public static function partialSearchFilterWithTwoLevelTraversalWithPropertyPlaceholderProvider(): \Generator
    {
        yield 'filter coops by chicken owner name (chickens.owner.name) containing "Alice" using :property placeholder' => [
            '/chicken_coops?searchChickenOwnerNamePartial[chickens.owner.name]=Alice',
            1,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù'],
        ];

        yield 'filter coops by chicken owner name (chickens.owner.name) containing "Bob" using :property placeholder' => [
            '/chicken_coops?searchChickenOwnerNamePartial[chickens.owner.name]=Bob',
            2,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù', 'Henriette'],
        ];

        yield 'filter coops by chicken owner name (chickens.owner.name) containing "b" using :property placeholder' => [
            '/chicken_coops?searchChickenOwnerNamePartial[chickens.owner.name]=b',
            2,
            ['Gertrude', 'xx_%_\\_%_xx', 'GÀgù', 'Henriette'],
        ];
    }

    #[DataProvider('partialSearchFilterWithManyToOneRelationProvider')]
    public function testPartialSearchFilterWithManyToOneRelation(string $url, int $expectedCount, array $expectedChickenNames): void
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

    public static function partialSearchFilterWithManyToOneRelationProvider(): \Generator
    {
        yield 'filter chickens by owner name (owner.name) containing "Alice"' => [
            '/chickens?ownerNamePartial=Alice',
            2,
            ['Gertrude', 'xx_%_\\_%_xx'],
        ];

        yield 'filter chickens by owner name (owner.name) containing "Bob"' => [
            '/chickens?ownerNamePartial=Bob',
            2,
            ['Henriette', 'GÀgù'],
        ];

        yield 'filter chickens by owner name (owner.name) containing "b"' => [
            '/chickens?ownerNamePartial=b',
            2,
            ['Henriette', 'GÀgù'],
        ];
    }

    #[DataProvider('partialSearchFilterWithManyToOneRelationWithPropertyPlaceholderProvider')]
    public function testPartialSearchFilterWithManyToOneRelationWithPropertyPlaceholder(string $url, int $expectedCount, array $expectedChickenNames): void
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

    public static function partialSearchFilterWithManyToOneRelationWithPropertyPlaceholderProvider(): \Generator
    {
        yield 'filter chickens by owner name (owner.name) containing "Alice" using :property placeholder' => [
            '/chickens?searchOwnerNamePartial[owner.name]=Alice',
            2,
            ['Gertrude', 'xx_%_\\_%_xx'],
        ];

        yield 'filter chickens by owner name (owner.name) containing "Bob" using :property placeholder' => [
            '/chickens?searchOwnerNamePartial[owner.name]=Bob',
            2,
            ['Henriette', 'GÀgù'],
        ];

        yield 'filter chickens by owner name (owner.name) containing "b" using :property placeholder' => [
            '/chickens?searchOwnerNamePartial[owner.name]=b',
            2,
            ['Henriette', 'GÀgù'],
        ];
    }

    public function testPartialSearchFilterThrowsExceptionWhenPropertyIsMissing(): void
    {
        $response = self::createClient()->request('GET', '/chickens?namePartialNoProperty=ertrude');
        $this->assertResponseStatusCodeSame(400);

        $responseData = $response->toArray(false);

        $this->assertSame('An error occurred', $responseData['hydra:title']);
        $this->assertSame($responseData['hydra:description'], $responseData['detail']);
        $this->assertStringContainsString(
            'The filter parameter with key "namePartialNoProperty" must specify a property',
            $responseData['detail']
        );
    }

    #[DataProvider('partialSearchFilterCaseSensitiveProvider')]
    public function testPartialSearchCaseSensitiveFilter(string $url, int $expectedCount, array $expectedNames): void
    {
        if ($this->isMysql() || $this->isSqlite()) {
            $this->markTestSkipped('Mysql and sqlite use case insensitive LIKE.');
        }

        $this->testPartialSearchFilter($url, $expectedCount, $expectedNames);
    }

    public static function partialSearchFilterCaseSensitiveProvider(): \Generator
    {
        yield 'filter by partial name "tru"' => [
            '/chickens?namePartial=tru',
            1,
            ['Gertrude'],
        ];

        yield 'filter by partial name "TRU"' => [
            '/chickens?namePartial=TRU',
            1,
            ['Gertrude'],
        ];

        yield 'filter by case sensitive partial name "tru"' => [
            '/chickens?namePartialSensitive=tru',
            1,
            ['Gertrude'],
        ];

        yield 'filter by case sensitive partial name "TRU"' => [
            '/chickens?namePartialSensitive=TRU',
            0,
            [],
        ];
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
        $chicken3->setChickenCoop($chickenCoop1);
        $chicken3->setOwner($owner1);

        $chicken4 = new $chickenClass();
        $chicken4->setName('GÀgù');
        $chicken4->setChickenCoop($chickenCoop1);
        $chicken4->setOwner($owner2);

        $chickenCoop1->addChicken($chicken1);
        $chickenCoop1->addChicken($chicken3);
        $chickenCoop1->addChicken($chicken4);
        $chickenCoop2->addChicken($chicken2);

        $manager->persist($chickenCoop1);
        $manager->persist($chickenCoop2);
        $manager->persist($chicken1);
        $manager->persist($chicken2);
        $manager->persist($chicken3);
        $manager->persist($chicken4);

        $manager->flush();
    }
}
