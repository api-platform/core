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
final class OrFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

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
    #[DataProvider('orFilterDataProvider')]
    public function testOrFilter(string $url, int $expectedCount): void
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => $expectedCount]);
    }

    public static function orFilterDataProvider(): \Generator
    {
        yield 'filter by coop 1 OR coop 2 using IRIs' => [
            'url' => '/chickens?relation[]=/chickens/1&relation[]=/chickens/2',
            'expectedCount' => 2,
        ];

        yield 'relationBis: filter by coop 1 (IRI) OR coop 2 (partial name)' => [
            'url' => '/chickens?relationBis[]=/chicken_coops/1&relationBis[]=Henri',
            'expectedCount' => 2,
        ];

        yield 'relationBis: filter by coop 1 (partial name) OR coop 2 (partial name)' => [
            'url' => '/chickens?relationBis[]=Gertrude&relationBis[]=Henri',
            'expectedCount' => 2,
        ];

        yield 'relationBis: filter by partial name matching both coops' => [
            'url' => '/chickens?relationBis[]=Coop',
            'expectedCount' => 2,
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

        $manager->persist($chicken1);
        $manager->persist($chicken2);
        $manager->persist($chickenCoop1);
        $manager->persist($chickenCoop2);
        $manager->flush();
    }
}
