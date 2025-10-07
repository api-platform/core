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
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;

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

    #[DataProvider('orFilterDataProvider')]
    public function testOrFilter(string $url, int $expectedCount): void
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => $expectedCount]);
    }

    public function testOrFilterWithAnd(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
        $client = self::createClient();
        $client->enableProfiler();
        $client->request('GET', '/chickens?autocomplete=978020137962&chickenCoop=/chicken_coops/2');
        $profile = $client->getProfile();
        $this->assertResponseIsSuccessful();

        /** @var DoctrineDataCollector */
        $db = $profile->getCollector('db');
        $this->assertStringContainsString('WHERE c1_.id = ? AND (c2_.name = ? OR c2_.ean = ?))', end($db->getQueries()['default'])['sql']);
    }

    public static function orFilterDataProvider(): \Generator
    {
        yield 'ean through autocomplete' => [
            'url' => '/chickens?autocomplete=978020137962',
            'expectedCount' => 1,
        ];

        yield 'name through autocomplete' => [
            'url' => '/chickens?autocomplete=Gertrude',
            'expectedCount' => 1,
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
        $chicken1->setEan('978020137962');
        $chicken1->setChickenCoop($chickenCoop1);

        $chicken2 = new $chickenClass();
        $chicken2->setName('Henriette');
        $chicken2->setEan('978020137963');
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
