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

final class FreeTextQueryFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ChickenCoop::class, Chicken::class, Owner::class];
    }

    public function testFreeTextQueryFilter(): void
    {
        $client = $this->createClient();
        $client->request('GET', '/chickens?q=9780')->toArray();
        $this->assertJsonContains(['totalItems' => 1]);
    }

    public function testFreeTextQueryFilterWithOneToManyRelation(): void
    {
        $client = $this->createClient();

        $response = $client->request('GET', '/chicken_coops?q=Gertrude')->toArray();

        $this->assertJsonContains(['totalItems' => 1]);
        $this->assertCount(1, $response['member']);
        $this->assertArrayHasKey('chickens', $response['member'][0]);
    }

    public function testFreeTextQueryFilterWithOneToManyRelationNoMatch(): void
    {
        $client = $this->createClient();

        $client->request('GET', '/chicken_coops?q=nonExistentChicken')->toArray();

        $this->assertJsonContains(['totalItems' => 0]);
    }

    public function testFreeTextQueryFilterWithOneToManyRelationMultipleMatches(): void
    {
        $client = $this->createClient();

        $response = $client->request('GET', '/chicken_coops?q=ette')->toArray();

        $this->assertJsonContains(['totalItems' => 2]);
        $this->assertCount(2, $response['member']);
    }

    public function testFreeTextQueryFilterWithTwoLevelTraversalPartial(): void
    {
        $client = $this->createClient();

        $response = $client->request('GET', '/chicken_coops?qOwner=Alice')->toArray();

        $this->assertJsonContains(['totalItems' => 2]);
        $this->assertCount(2, $response['member']);

        $client = $this->createClient();

        $response = $client->request('GET', '/chicken_coops?qOwner=Bob')->toArray();

        $this->assertJsonContains(['totalItems' => 2]);
        $this->assertCount(2, $response['member']);

        $client = $this->createClient();

        $response = $client->request('GET', '/chicken_coops?qOwner=ob')->toArray();

        $this->assertJsonContains(['totalItems' => 2]);
        $this->assertCount(2, $response['member']);
    }

    public function testFreeTextQueryFilterWithTwoLevelTraversalPartialWithPropertyPlaceholder(): void
    {
        $client = $this->createClient();

        $response = $client->request('GET', '/chicken_coops?searchQOwner[chickens.owner.name]=Alice')->toArray();

        $this->assertJsonContains(['totalItems' => 2]);
        $this->assertCount(2, $response['member']);

        $client = $this->createClient();

        $response = $client->request('GET', '/chicken_coops?searchQOwner[chickens.owner.name]=Bob')->toArray();

        $this->assertJsonContains(['totalItems' => 2]);
        $this->assertCount(2, $response['member']);

        $client = $this->createClient();

        $response = $client->request('GET', '/chicken_coops?searchQOwner[chickens.owner.name]=ob')->toArray();

        $this->assertJsonContains(['totalItems' => 2]);
        $this->assertCount(2, $response['member']);
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DocumentChicken::class : Chicken::class, $this->isMongoDB() ? DocumentChickenCoop::class : ChickenCoop::class, $this->isMongoDB() ? DocumentOwner::class : Owner::class]);
        $this->loadFixtures();
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
        $chickenCoop3 = new $coopClass();

        $chicken1 = new $chickenClass();
        $chicken1->setName('978020137962');
        $chicken1->setEan('978020137962');
        $chicken1->setChickenCoop($chickenCoop1);
        $chicken1->setOwner($owner1);

        $chicken2 = new $chickenClass();
        $chicken2->setName('Henriette');
        $chicken2->setEan('978020137963');
        $chicken2->setChickenCoop($chickenCoop2);
        $chicken2->setOwner($owner2);

        $chicken3 = new $chickenClass();
        $chicken3->setName('Gertrude');
        $chicken3->setEan('978020137964');
        $chicken3->setChickenCoop($chickenCoop3);
        $chicken3->setOwner($owner1);

        $chicken4 = new $chickenClass();
        $chicken4->setName('Violette');
        $chicken4->setEan('978020137965');
        $chicken4->setChickenCoop($chickenCoop3);
        $chicken4->setOwner($owner2);

        $chickenCoop1->addChicken($chicken1);
        $chickenCoop2->addChicken($chicken2);
        $chickenCoop3->addChicken($chicken3);
        $chickenCoop3->addChicken($chicken4);

        $manager->persist($chicken1);
        $manager->persist($chicken2);
        $manager->persist($chicken3);
        $manager->persist($chicken4);
        $manager->persist($chickenCoop1);
        $manager->persist($chickenCoop2);
        $manager->persist($chickenCoop3);
        $manager->flush();
    }
}
