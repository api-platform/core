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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Doctrine\Orm\EntityManager;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyPassenger;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTravel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FourthLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class EagerLoadingTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            Dummy::class,
            RelatedDummy::class,
            DummyFriend::class,
            RelatedToDummyFriend::class,
            DummyTravel::class,
            DummyCar::class,
            DummyPassenger::class,
            ThirdLevel::class,
            FourthLevel::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMongoDB()) {
            $this->markTestSkipped('Eager loading is ORM only.');
        }
    }

    public function testEagerLoadingForARelation(): void
    {
        $this->recreateSchema([
            Dummy::class, RelatedDummy::class, DummyFriend::class, RelatedToDummyFriend::class,
            ThirdLevel::class, FourthLevel::class,
        ]);
        $this->createRelatedDummyWithFriends(2);

        self::createClient()->request('GET', '/related_dummies/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertDqlEquals(<<<'DQL'
SELECT o, thirdLevel_a1, relatedToDummyFriend_a3, fourthLevel_a2, dummyFriend_a4
FROM ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy o
    LEFT JOIN o.thirdLevel thirdLevel_a1
    LEFT JOIN thirdLevel_a1.fourthLevel fourthLevel_a2
    LEFT JOIN o.relatedToDummyFriend relatedToDummyFriend_a3
    LEFT JOIN relatedToDummyFriend_a3.dummyFriend dummyFriend_a4
WHERE o.id = :id_p1
DQL);
    }

    public function testEagerLoadingForTheSearchFilter(): void
    {
        $this->recreateSchema([
            Dummy::class, RelatedDummy::class, ThirdLevel::class, FourthLevel::class,
        ]);
        $this->createDummyWithFourthLevelRelation();

        self::createClient()->request('GET', '/dummies?relatedDummy.thirdLevel.level=3', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertDqlEquals(<<<'DQL'
SELECT o
FROM ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy o
    INNER JOIN o.relatedDummy relatedDummy_a1
    INNER JOIN relatedDummy_a1.thirdLevel thirdLevel_a2
WHERE o IN(
        SELECT o_a3
        FROM ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy o_a3
            INNER JOIN o_a3.relatedDummy relatedDummy_a4
            INNER JOIN relatedDummy_a4.thirdLevel thirdLevel_a5
        WHERE thirdLevel_a5.level = :level_p1
    )
ORDER BY o.id ASC
DQL);
    }

    public function testEagerLoadingForARelationAndSearchFilter(): void
    {
        $this->recreateSchema([
            Dummy::class, RelatedDummy::class, DummyFriend::class, RelatedToDummyFriend::class,
            ThirdLevel::class, FourthLevel::class,
        ]);
        $this->createRelatedDummyWithFriends(2);

        self::createClient()->request('GET', '/related_dummies?relatedToDummyFriend.dummyFriend=2', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertDqlEquals(<<<'DQL'
SELECT o, thirdLevel_a4, relatedToDummyFriend_a1, fourthLevel_a5, dummyFriend_a6
FROM ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy o
    INNER JOIN o.relatedToDummyFriend relatedToDummyFriend_a1
    LEFT JOIN o.thirdLevel thirdLevel_a4
    LEFT JOIN thirdLevel_a4.fourthLevel fourthLevel_a5
    INNER JOIN relatedToDummyFriend_a1.dummyFriend dummyFriend_a6
WHERE o IN(
        SELECT o_a2
        FROM ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy o_a2
            INNER JOIN o_a2.relatedToDummyFriend relatedToDummyFriend_a3
        WHERE relatedToDummyFriend_a3.dummyFriend = :dummyFriend_p1
    )
ORDER BY o.id ASC
DQL);
    }

    public function testEagerLoadingForARelationAndPropertyFilterWithMultipleRelations(): void
    {
        $this->recreateSchema([
            DummyTravel::class, DummyCar::class, DummyPassenger::class,
        ]);
        $this->createDummyTravel();

        $response = self::createClient()->request(
            'GET',
            '/dummy_travels/1?properties[]=confirmed&properties[car][]=brand&properties[passenger][]=nickname',
            ['headers' => ['Accept' => 'application/ld+json']]
        );

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertTrue($data['confirmed']);
        $this->assertSame('DummyBrand', $data['car']['carBrand']);
        $this->assertSame('Tom', $data['passenger']['nickname']);
        $this->assertDqlEquals(<<<'DQL'
SELECT o, car_a1, passenger_a2
FROM ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTravel o
    LEFT JOIN o.car car_a1
    LEFT JOIN o.passenger passenger_a2
WHERE o.id = :id_p1
DQL);
    }

    public function testEagerLoadingForARelationWithComplexSubQueryFilter(): void
    {
        $this->recreateSchema([
            Dummy::class, RelatedDummy::class, DummyFriend::class, RelatedToDummyFriend::class,
            ThirdLevel::class, FourthLevel::class,
        ]);
        $this->createRelatedDummyWithFriends(2);

        self::createClient()->request('GET', '/related_dummies?complex_sub_query_filter=1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertDqlEquals(<<<'DQL'
SELECT o, thirdLevel_a3, relatedToDummyFriend_a5, fourthLevel_a4, dummyFriend_a6
FROM ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy o
    LEFT JOIN o.thirdLevel thirdLevel_a3
    LEFT JOIN thirdLevel_a3.fourthLevel fourthLevel_a4
    LEFT JOIN o.relatedToDummyFriend relatedToDummyFriend_a5
    LEFT JOIN relatedToDummyFriend_a5.dummyFriend dummyFriend_a6
WHERE o.id IN (
        SELECT related_dummy_a1.id
        FROM ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy related_dummy_a1
        INNER JOIN related_dummy_a1.relatedToDummyFriend related_to_dummy_friend_a2
        WITH related_to_dummy_friend_a2.name = :name_p1
    )
ORDER BY o.id ASC
DQL);
    }

    private function createRelatedDummyWithFriends(int $nb): void
    {
        $manager = $this->getManager();

        $relatedDummy = new RelatedDummy();
        $relatedDummy->setName('RelatedDummy with friends');
        $manager->persist($relatedDummy);
        $manager->flush();

        for ($i = 1; $i <= $nb; ++$i) {
            $friend = new DummyFriend();
            $friend->setName('Friend-'.$i);
            $manager->persist($friend);
            $manager->flush();

            $relation = new RelatedToDummyFriend();
            $relation->setName('Relation-'.$i);
            $relation->setDummyFriend($friend);
            $relation->setRelatedDummy($relatedDummy);
            $relatedDummy->addRelatedToDummyFriend($relation);

            $manager->persist($relation);
        }

        $relatedDummy2 = new RelatedDummy();
        $relatedDummy2->setName('RelatedDummy without friends');
        $manager->persist($relatedDummy2);
        $manager->flush();
        $manager->clear();
    }

    private function createDummyWithFourthLevelRelation(): void
    {
        $manager = $this->getManager();

        $fourthLevel = new FourthLevel();
        $fourthLevel->setLevel(4);
        $manager->persist($fourthLevel);

        $thirdLevel = new ThirdLevel();
        $thirdLevel->setLevel(3);
        $thirdLevel->setFourthLevel($fourthLevel);
        $manager->persist($thirdLevel);

        $namedRelatedDummy = new RelatedDummy();
        $namedRelatedDummy->setName('Hello');
        $namedRelatedDummy->setThirdLevel($thirdLevel);
        $manager->persist($namedRelatedDummy);

        $relatedDummy = new RelatedDummy();
        $relatedDummy->setThirdLevel($thirdLevel);
        $manager->persist($relatedDummy);

        $dummy = new Dummy();
        $dummy->setName('Dummy with relations');
        $dummy->setRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($relatedDummy);
        $manager->persist($dummy);

        $manager->flush();
        $manager->clear();
    }

    private function createDummyTravel(): void
    {
        $manager = $this->getManager();

        $car = new DummyCar();
        $car->setName('model x');
        $car->setCanSell(true);
        $car->setAvailableAt(new \DateTime());
        $manager->persist($car);

        $passenger = new DummyPassenger();
        $passenger->nickname = 'Tom';
        $manager->persist($passenger);

        $travel = new DummyTravel();
        $travel->car = $car;
        $travel->passenger = $passenger;
        $travel->confirmed = true;
        $manager->persist($travel);

        $manager->flush();
        $manager->clear();
    }

    private function assertDqlEquals(string $expected): void
    {
        $actual = EntityManager::$dql;
        $expected = preg_replace('/\(\R */', '(', $expected);
        $expected = preg_replace('/\R *\)/', ')', $expected);
        $expected = preg_replace('/\R */', ' ', $expected);

        $this->assertSame($expected, $actual);
    }
}
