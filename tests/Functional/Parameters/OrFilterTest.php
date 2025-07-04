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

final class OrFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    public static function getResources(): array
    {
        return [Chicken::class, ChickenCoop::class];
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    protected function setUp(): void
    {
        $entities = $this->isMongoDB()
            ? [DocumentChicken::class, DocumentChickenCoop::class]
            : [Chicken::class, ChickenCoop::class];

        $this->recreateSchema($entities);
        $this->loadFixtures();
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[DataProvider('filterDataProvider')]
    public function testOrFilter(string $url, int $expectedCount, array $expectedNames): void
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => $expectedCount]);

        if ($expectedCount > 0) {
            $names = array_column($client->getResponse()->toArray()['hydra:member'], 'name');
            sort($names);
            sort($expectedNames);
            $this->assertSame($expectedNames, $names);
        }
    }

    public static function filterDataProvider(): \Generator
    {
        yield 'filtre par ID du poulailler de Gertrude' => [
            'url' => '/chickens?relation=1',
            'expectedCount' => 1,
            'expectedNames' => ['Gertrude'],
        ];

        yield 'filtre par IRI du poulailler de Gertrude' => [
            'url' => '/chickens?relation=/chicken_coops/1',
            'expectedCount' => 1,
            'expectedNames' => ['Gertrude'],
        ];

        yield 'filtre par ID du poulailler de Henriette' => [
            'url' => '/chickens?relation=2',
            'expectedCount' => 1,
            'expectedNames' => ['Henriette'],
        ];

        yield 'filtre par IRI du poulailler de Henriette' => [
            'url' => '/chickens?relation=/chicken_coops/2',
            'expectedCount' => 1,
            'expectedNames' => ['Henriette'],
        ];

        yield 'filtre avec un ID inexistant' => [
            'url' => '/chickens?relation=999',
            'expectedCount' => 0,
            'expectedNames' => [],
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
        $manager->persist($chickenCoop1);
        $manager->persist($chickenCoop2);

        $chicken1 = new $chickenClass();
        $chicken1->setName('Gertrude');
        $chicken1->setChickenCoop($chickenCoop1);
        $manager->persist($chicken1);

        $chicken2 = new $chickenClass();
        $chicken2->setName('Henriette');
        $chicken2->setChickenCoop($chickenCoop2);
        $manager->persist($chicken2);

        $manager->flush();
    }
}
