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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SuperHero;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @author Votre Nom <votre@email.com>
 */
final class PartialSearchFilterPlaceholderTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SuperHero::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->recreateSchema([SuperHero::class]);
        $this->loadFixtures();
    }

    #[DataProvider('partialFilterParameterProvider')]
    public function testPartialFilterPlaceholder(string $url, int $expectedCount, array $expectedNames): void
    {

        $response = self::createClient()->request('GET', $url);

        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['member'];


        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $names = array_map(fn ($item) => $item['name'], $filteredItems);
        sort($names);
        sort($expectedNames);

        $this->assertSame($expectedNames, $names, 'The names do not match the expected values.');
    }

    public static function partialFilterParameterProvider(): \Generator
    {
        // Fixtures Recap:
        // 1. Name: "Batman",    Description: "The Dark Knight", Secret: "Bruce"
        // 2. Name: "Batgirl",   Description: "Oracle",          Secret: "Barbara"
        // 3. Name: "Superman",  Description: "Man of Steel",    Secret: "Clark"

        yield 'partial match on name (Bat -> Batman, Batgirl)' => [
            '/super_heroes?search[name]=Bat',
            2,
            ['Batgirl', 'Batman'],
        ];

        yield 'partial match on description (Steel -> Superman)' => [
            '/super_heroes?search[description]=Steel',
            1,
            ['Superman'],
        ];

        yield 'partial match case insensitive (bat -> Batman, Batgirl)' => [
            '/super_heroes?search[name]=bat',
            2,
            ['Batgirl', 'Batman'],
        ];

        yield 'property "secret" is NOT in allow-list (filter ignored)' => [
            '/super_heroes?search[secret]=Bruce',
            3,
            ['Batgirl', 'Batman', 'Superman'],
        ];
    }

    /**
     * @throws \Throwable
     */
    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $item1 = new SuperHero();
        $item1->name = 'Batman';
        $item1->description = 'The Dark Knight';
        $item1->secret = 'Bruce';

        $item2 = new SuperHero();
        $item2->name = 'Batgirl';
        $item2->description = 'Oracle';
        $item2->secret = 'Barbara';

        $item3 = new SuperHero();
        $item3->name = 'Superman';
        $item3->description = 'Man of Steel';
        $item3->secret = 'Clark';

        $manager->persist($item1);
        $manager->persist($item2);
        $manager->persist($item3);

        $manager->flush();
    }
}
