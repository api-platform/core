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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Chicken;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ChickenCoop;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Owner;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class ComparisonFilterTest extends ApiTestCase
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

    protected function setUp(): void
    {
        $this->recreateSchema([Chicken::class, ChickenCoop::class, Owner::class]);
        $this->loadFixtures();
    }

    #[DataProvider('comparisonFilterProvider')]
    public function testComparisonFilter(string $url, int $expectedCount, array $expectedNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $names = array_map(static fn ($chicken) => $chicken['name'], $filteredItems);
        sort($names);
        sort($expectedNames);

        $this->assertSame($expectedNames, $names, 'The names do not match the expected values.');
    }

    public static function comparisonFilterProvider(): \Generator
    {
        // We create 4 chickens with IDs 1-4: Alpha, Bravo, Charlie, Delta
        yield 'gt: id > 2 returns chickens 3,4' => [
            '/chickens?idComparison[gt]=2',
            2,
            ['Charlie', 'Delta'],
        ];

        yield 'gte: id >= 2 returns chickens 2,3,4' => [
            '/chickens?idComparison[gte]=2',
            3,
            ['Bravo', 'Charlie', 'Delta'],
        ];

        yield 'lt: id < 3 returns chickens 1,2' => [
            '/chickens?idComparison[lt]=3',
            2,
            ['Alpha', 'Bravo'],
        ];

        yield 'lte: id <= 3 returns chickens 1,2,3' => [
            '/chickens?idComparison[lte]=3',
            3,
            ['Alpha', 'Bravo', 'Charlie'],
        ];

        yield 'between: id between 2..3 returns chickens 2,3' => [
            '/chickens?idComparison[between]=2..3',
            2,
            ['Bravo', 'Charlie'],
        ];

        yield 'between equal values: id between 2..2 returns chicken 2 (equality)' => [
            '/chickens?idComparison[between]=2..2',
            1,
            ['Bravo'],
        ];

        yield 'combined gt and lt: id > 1 AND id < 4 returns chickens 2,3' => [
            '/chickens?idComparison[gt]=1&idComparison[lt]=4',
            2,
            ['Bravo', 'Charlie'],
        ];

        yield 'gt with no results: id > 100 returns nothing' => [
            '/chickens?idComparison[gt]=100',
            0,
            [],
        ];

        yield 'gte with large range returns all' => [
            '/chickens?idComparison[gte]=1&itemsPerPage=10',
            4,
            ['Alpha', 'Bravo', 'Charlie', 'Delta'],
        ];
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $owner = new Owner();
        $owner->setName('TestOwner');
        $manager->persist($owner);

        $coop = new ChickenCoop();
        $manager->persist($coop);

        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
            $chicken = new Chicken();
            $chicken->setName($name);
            $chicken->setEan('000000000000');
            $chicken->setChickenCoop($coop);
            $chicken->setOwner($owner);
            $coop->addChicken($chicken);
            $manager->persist($chicken);
        }

        $manager->flush();
    }
}
