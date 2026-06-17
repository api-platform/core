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

final class StartSearchFilterTest extends ApiTestCase
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

    #[DataProvider('startSearchFilterProvider')]
    public function testStartSearchFilter(string $url, int $expectedCount, array $expectedNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $names = array_map(static fn ($chicken) => $chicken['name'], $filteredItems);
        sort($names);
        sort($expectedNames);

        $this->assertSame($expectedNames, $names, 'The returned names do not match the expected values.');
    }

    public static function startSearchFilterProvider(): \Generator
    {
        yield 'filter by prefix "Gert"' => [
            '/chickens?nameStart=Gert',
            1,
            ['Gertrude'],
        ];

        yield 'filter by prefix "Hen"' => [
            '/chickens?nameStart=Hen',
            1,
            ['Henriette'],
        ];

        yield 'prefix in the middle does not match (start anchored)' => [
            '/chickens?nameStart=rude',
            0,
            [],
        ];

        yield 'filter by prefix with no matching entities' => [
            '/chickens?nameStart=Zebra',
            0,
            [],
        ];

        yield 'filter with multiple prefixes "Gert" OR "Hen"' => [
            '/chickens?nameStart[]=Gert&nameStart[]=Hen',
            2,
            ['Gertrude', 'Henriette'],
        ];

        yield 'filter by prefix "xx_"' => [
            '/chickens?nameStart=xx_',
            1,
            ['xx_%_\\_%_xx'],
        ];
    }

    public function testStartSearchFilterThrowsExceptionWhenPropertyIsMissing(): void
    {
        $response = self::createClient()->request('GET', '/chickens?nameStartNoProperty=Gert');
        $this->assertResponseStatusCodeSame(400);

        $responseData = $response->toArray(false);

        $this->assertStringContainsString(
            'The filter parameter with key "nameStartNoProperty" must specify a property',
            $responseData['detail']
        );
    }

    #[DataProvider('startSearchFilterCaseSensitiveProvider')]
    public function testStartSearchCaseSensitiveFilter(string $url, int $expectedCount, array $expectedNames): void
    {
        if ($this->isMysql() || $this->isSqlite()) {
            $this->markTestSkipped('Mysql and sqlite use case insensitive LIKE.');
        }

        $this->testStartSearchFilter($url, $expectedCount, $expectedNames);
    }

    public static function startSearchFilterCaseSensitiveProvider(): \Generator
    {
        yield 'filter by prefix "gert"' => [
            '/chickens?nameStart=gert',
            1,
            ['Gertrude'],
        ];

        yield 'filter by case sensitive prefix "Gert"' => [
            '/chickens?nameStartSensitive=Gert',
            1,
            ['Gertrude'],
        ];

        yield 'filter by case sensitive prefix "gert"' => [
            '/chickens?nameStartSensitive=gert',
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

        $manager->persist($owner1);
        $manager->flush();

        $chickenCoop1 = new $coopClass();

        $chicken1 = new $chickenClass();
        $chicken1->setName('Gertrude');
        $chicken1->setChickenCoop($chickenCoop1);
        $chicken1->setOwner($owner1);

        $chicken2 = new $chickenClass();
        $chicken2->setName('Henriette');
        $chicken2->setChickenCoop($chickenCoop1);
        $chicken2->setOwner($owner1);

        $chicken3 = new $chickenClass();
        $chicken3->setName('xx_%_\\_%_xx');
        $chicken3->setChickenCoop($chickenCoop1);
        $chicken3->setOwner($owner1);

        $chickenCoop1->addChicken($chicken1);
        $chickenCoop1->addChicken($chicken2);
        $chickenCoop1->addChicken($chicken3);

        $manager->persist($chickenCoop1);
        $manager->persist($chicken1);
        $manager->persist($chicken2);
        $manager->persist($chicken3);

        $manager->flush();
    }
}
