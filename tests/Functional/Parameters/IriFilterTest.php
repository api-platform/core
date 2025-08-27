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

final class IriFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ChickenCoop::class, Chicken::class];
    }

    public function testIriFilter(): void
    {
        $client = $this->createClient();
        $res = $client->request('GET', '/chickens?chickenCoop=/chicken_coops/2')->toArray();
        $this->assertCount(1, $res['member']);
        $this->assertEquals('/chicken_coops/2', $res['member'][0]['chickenCoop']);
    }

    public function testIriFilterMultiple(): void
    {
        $client = $this->createClient();
        $res = $client->request('GET', '/chickens?chickenCoop[]=/chicken_coops/2&chickenCoop[]=/chicken_coops/1')->toArray();
        $this->assertCount(2, $res['member']);
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DocumentChicken::class : Chicken::class, $this->isMongoDB() ? DocumentChickenCoop::class : ChickenCoop::class]);
        $this->loadFixtures();
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $chickenCoop1 = new ($this->isMongoDB() ? DocumentChickenCoop::class : ChickenCoop::class)();
        $chickenCoop2 = new ($this->isMongoDB() ? DocumentChickenCoop::class : ChickenCoop::class)();

        $chicken1 = new ($this->isMongoDB() ? DocumentChicken::class : Chicken::class)();
        $chicken1->setName('Gertrude');
        $chicken1->setChickenCoop($chickenCoop1);

        $chicken2 = new ($this->isMongoDB() ? DocumentChicken::class : Chicken::class)();
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
