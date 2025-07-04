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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Chicken;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ChickenCoop;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class PartialSearchFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Chicken::class, ChickenCoop::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        self::$alwaysBootKernel = false;

        $entities = $this->isMongoDB()
            ? [DocumentChicken::class, DocumentChickenCoop::class]
            : [Chicken::class, ChickenCoop::class];

        $this->recreateSchema($entities);
        $this->loadFixtures();
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     */
    #[DataProvider('partialSearchFilterProvider')]
    public function testPartialSearchFilter(string $url, int $expectedCount, array $expectedNames): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $names = array_map(fn ($chicken) => $chicken['name'], $filteredItems);
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

        $chickenCoop1 = new $coopClass();
        $chickenCoop2 = new $coopClass();

        $chicken1 = new $chickenClass();
        $chicken1->setName('Gertrude');
        $chicken1->setChickenCoop($chickenCoop1);

        $chicken2 = new $chickenClass();
        $chicken2->setName('Henriette');
        $chicken2->setChickenCoop($chickenCoop2);

        $chickenCoop1->addChicken($chicken1);
        $chickenCoop2->addChicken($chicken2);

        $manager->persist($chickenCoop1);
        $manager->persist($chickenCoop2);
        $manager->persist($chicken1);
        $manager->persist($chicken2);

        $manager->flush();
    }
}
