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

final class WordStartSearchFilterTest extends ApiTestCase
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

    #[DataProvider('wordStartSearchFilterProvider')]
    public function testWordStartSearchFilter(string $url, int $expectedCount, array $expectedNames): void
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

    public static function wordStartSearchFilterProvider(): \Generator
    {
        // Fixtures: "Gertrude the Hen", "Henriette", "Red Rooster"
        yield 'matches word at the very start' => [
            '/chickens?nameWordStart=Gert',
            1,
            ['Gertrude the Hen'],
        ];

        yield 'matches a word starting in the middle of the string' => [
            '/chickens?nameWordStart=Hen',
            2,
            ['Gertrude the Hen', 'Henriette'],
        ];

        yield 'does not match a substring inside a word' => [
            '/chickens?nameWordStart=ette',
            0,
            [],
        ];

        yield 'does not match a substring inside a non-leading word' => [
            '/chickens?nameWordStart=ooster',
            0,
            [],
        ];

        yield 'matches the leading word of a multi-word value' => [
            '/chickens?nameWordStart=Red',
            1,
            ['Red Rooster'],
        ];

        yield 'matches a trailing word' => [
            '/chickens?nameWordStart=Roo',
            1,
            ['Red Rooster'],
        ];

        yield 'no match' => [
            '/chickens?nameWordStart=Zebra',
            0,
            [],
        ];

        yield 'multiple values "Gert" OR "Red"' => [
            '/chickens?nameWordStart[]=Gert&nameWordStart[]=Red',
            2,
            ['Gertrude the Hen', 'Red Rooster'],
        ];
    }

    public function testWordStartSearchFilterThrowsExceptionWhenPropertyIsMissing(): void
    {
        $response = self::createClient()->request('GET', '/chickens?nameWordStartNoProperty=Gert');
        $this->assertResponseStatusCodeSame(400);

        $responseData = $response->toArray(false);

        $this->assertStringContainsString(
            'The filter parameter with key "nameWordStartNoProperty" must specify a property',
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

        $manager->persist($owner1);
        $manager->flush();

        $chickenCoop1 = new $coopClass();

        $chicken1 = new $chickenClass();
        $chicken1->setName('Gertrude the Hen');
        $chicken1->setChickenCoop($chickenCoop1);
        $chicken1->setOwner($owner1);

        $chicken2 = new $chickenClass();
        $chicken2->setName('Henriette');
        $chicken2->setChickenCoop($chickenCoop1);
        $chicken2->setOwner($owner1);

        $chicken3 = new $chickenClass();
        $chicken3->setName('Red Rooster');
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
