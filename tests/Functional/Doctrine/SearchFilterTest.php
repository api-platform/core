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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5605\MainResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5605\SubResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5648\DummyResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedOwner as ConvertedOwnerDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedRelated as ConvertedRelatedDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDate as DummyDateDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddableDummy as EmbeddableDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddedDummy as EmbeddedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FourthLevel as FourthLevelDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel as ThirdLevelDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedOwner;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCarColor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDate;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummySubEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyWithSubEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddableDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FourthLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5735\Group;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5735\Issue5735User;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class SearchFilterTest extends ApiTestCase
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
            EmbeddedDummy::class,
            ThirdLevel::class,
            FourthLevel::class,
            DummyCar::class,
            DummyCarColor::class,
            DummyDate::class,
            ConvertedOwner::class,
            ConvertedRelated::class,
            DummyResource::class,
            MainResource::class,
            SubResource::class,
            DummyWithSubEntity::class,
            DummySubEntity::class,
            Group::class,
            Issue5735User::class,
        ];
    }

    public function testManyToManyWithFilterOnJoinTable(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('HAL relation filter requires ORM join table.');
        }

        $this->recreateSchema([
            RelatedDummy::class, DummyFriend::class, RelatedToDummyFriend::class,
            ThirdLevel::class, FourthLevel::class,
        ]);
        $this->createRelatedDummyWithFriends(4);

        $response = self::createClient()->request('GET', '/related_dummies?relatedToDummyFriend.dummyFriend=/dummy_friends/4', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['_embedded']['item']);
        $this->assertSame(1, $data['_embedded']['item'][0]['id']);
        $this->assertCount(4, $data['_embedded']['item'][0]['_links']['relatedToDummyFriend']);
        $this->assertCount(4, $data['_embedded']['item'][0]['_embedded']['relatedToDummyFriend']);
    }

    public function testSearchManyToManyWithRelatedEntity(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('DummyCar/Color is ORM only in this scenario.');
        }
        $this->recreateSchema([DummyCar::class, DummyCarColor::class]);
        $this->createDummyCarWithColors();

        $response = self::createClient()->request('GET', '/dummy_cars?colors.prop=red', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(1, $data['hydra:totalItems']);
        $this->assertSame('/dummy_cars/1', $data['hydra:member'][0]['@id']);
        $this->assertCount(2, $data['hydra:member'][0]['colors']);
        $this->assertSame('red', $data['hydra:member'][0]['colors'][0]['prop']);
        $this->assertSame('blue', $data['hydra:member'][0]['colors'][1]['prop']);
    }

    public function testSearchByNamePartial(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?name=my', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            ['/dummies/1', '/dummies/2', '/dummies/3'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
    }

    public function testSearchEmbeddedByName(): void
    {
        $embeddedClass = $this->isMongoDB() ? EmbeddedDummyDocument::class : EmbeddedDummy::class;
        $embeddableClass = $this->isMongoDB() ? EmbeddableDummyDocument::class : EmbeddableDummy::class;
        $this->recreateSchema([$embeddedClass]);
        $this->createEmbeddedDummies($embeddedClass, $embeddableClass, 30);

        $response = self::createClient()->request('GET', '/embedded_dummies?embeddedDummy.dummyName=my', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            ['/embedded_dummies/1', '/embedded_dummies/2', '/embedded_dummies/3'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
    }

    public function testSearchByNameMultipleValues(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?name[]=2&name[]=3', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids, \SORT_NATURAL);
        $this->assertSame(['/dummies/2', '/dummies/3', '/dummies/12'], $ids);
    }

    public function testSearchByDummyCaseInsensitive(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?dummy=somedummytest1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        foreach ($response->toArray()['hydra:member'] as $member) {
            $this->assertMatchesRegularExpression('/^SomeDummyTest\d{1,2}$/', $member['dummy']);
        }
    }

    public function testSearchByAliasStart(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?alias=Ali', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $response->toArray()['hydra:member']);
    }

    public function testSearchByDescriptionMultipleStart(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?description[]=Sma&description[]=Not', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $response->toArray()['hydra:member']);
    }

    public function testSearchByDescriptionWordStartSqlite(): void
    {
        if (!$this->isSqlite()) {
            $this->markTestSkipped('SQLite-specific: case-insensitive default LIKE.');
        }

        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?description=smart', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            ['/dummies/1', '/dummies/2', '/dummies/3'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
    }

    public function testSearchByDescriptionWordStartMultipleSqlite(): void
    {
        if (!$this->isSqlite()) {
            $this->markTestSkipped('SQLite-specific: case-insensitive default LIKE.');
        }

        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?description[]=smart&description[]=so', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            ['/dummies/1', '/dummies/2', '/dummies/3'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
    }

    public function testSearchByDescriptionWordStartPostgres(): void
    {
        if (!$this->isPostgres()) {
            $this->markTestSkipped('Postgres-specific: case-sensitive default LIKE.');
        }

        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?description=smart', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids, \SORT_NATURAL);
        $this->assertSame(['/dummies/2', '/dummies/4', '/dummies/6'], $ids);
    }

    public function testSearchEmptyResult(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?name=MuYm', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $response->toArray()['hydra:member']);
    }

    public function testSearchByExistingCollectionRouteNameSqlite(): void
    {
        if (!$this->isSqlite()) {
            $this->markTestSkipped('SQLite-specific.');
        }

        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?relatedDummies=dummy_cars', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertIsArray($response->toArray()['hydra:member']);
    }

    public function testSearchRelatedCollectionByName(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('HAL relation filter requires ORM join table.');
        }

        $resource = $this->dummyClass();
        $relatedResource = $this->relatedDummyClass();
        $this->recreateSchema([$resource, $relatedResource]);
        $this->createDummiesEachWithRelatedDummies($resource, $relatedResource, 3, 3);

        $response = self::createClient()->request('GET', '/dummies?relatedDummies.name=RelatedDummy1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(3, $data['_embedded']['item']);
        foreach ($data['_embedded']['item'] as $item) {
            $this->assertCount(3, $item['_links']['relatedDummies']);
        }
    }

    public function testSearchByRelatedCollectionId(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('HAL relation filter requires ORM join table.');
        }

        $resource = $this->dummyClass();
        $relatedResource = $this->relatedDummyClass();
        $this->recreateSchema([$resource, $relatedResource]);
        $this->createDummiesEachWithRelatedDummies($resource, $relatedResource, 2, 2);

        $response = self::createClient()->request('GET', '/dummies?relatedDummies=3', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['_links']['item']);
        $this->assertSame('/dummies/2', $data['_links']['item'][0]['href']);
    }

    public function testCollectionByIdNonInteger(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?id=9.99', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ['/dummies/1', '/dummies/2', '/dummies/3'],
            array_map(static fn (array $i): string => $i['@id'], $response->toArray()['hydra:member'])
        );
    }

    public function testCollectionById(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?id=10', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['hydra:member']);
        $this->assertSame('/dummies/10', $data['hydra:member'][0]['@id']);
    }

    public function testCollectionFilteredByUnknownProperty(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?unknown=0', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $response->toArray()['hydra:member']);

        $response = self::createClient()->request('GET', '/dummies?unknown=1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $response->toArray()['hydra:member']);
    }

    public function testSearchAtThirdLevel(): void
    {
        $resource = $this->dummyClass();
        $relatedResource = $this->relatedDummyClass();
        $this->recreateSchema([$resource, $relatedResource, $this->thirdLevelClass(), $this->fourthLevelClass()]);
        $this->createDummiesEachWithRelatedDummies($resource, $relatedResource, 30, 0);
        $this->createDummyWithFourthLevelRelation();

        $response = self::createClient()->request('GET', '/dummies?relatedDummy.thirdLevel.level=3', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(['/dummies/31'], array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']));
    }

    public function testSearchAtFourthLevel(): void
    {
        $resource = $this->dummyClass();
        $relatedResource = $this->relatedDummyClass();
        $this->recreateSchema([$resource, $relatedResource, $this->thirdLevelClass(), $this->fourthLevelClass()]);
        $this->createDummiesEachWithRelatedDummies($resource, $relatedResource, 30, 0);
        $this->createDummyWithFourthLevelRelation();

        $response = self::createClient()->request('GET', '/dummies?relatedDummy.thirdLevel.fourthLevel.level=4', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(['/dummies/31'], array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']));
    }

    public function testSearchUsingNameConverter(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?name_converted=Converted 3', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['hydra:member']);
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids, \SORT_NATURAL);
        $this->assertSame(['/dummies/3', '/dummies/30'], $ids);
    }

    public function testSearchUsingNestedNameConverter(): void
    {
        $ownerClass = $this->isMongoDB() ? ConvertedOwnerDocument::class : ConvertedOwner::class;
        $relatedClass = $this->isMongoDB() ? ConvertedRelatedDocument::class : ConvertedRelated::class;
        $this->recreateSchema([$ownerClass, $relatedClass]);

        $manager = $this->getManager();
        for ($i = 1; $i <= 30; ++$i) {
            $related = new $relatedClass();
            $related->nameConverted = 'Converted '.$i;
            $owner = new $ownerClass();
            $owner->nameConverted = $related;
            $manager->persist($related);
            $manager->persist($owner);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/converted_owners?name_converted.name_converted=Converted 3', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['hydra:member']);
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids, \SORT_NATURAL);
        $this->assertSame(['/converted_owners/3', '/converted_owners/30'], $ids);
    }

    public function testSearchByDate(): void
    {
        $resource = $this->isMongoDB() ? DummyDateDocument::class : DummyDate::class;
        $this->recreateSchema([$resource]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 3; ++$i) {
            $dummy = new $resource();
            $dummy->dummyDate = new \DateTime(\sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));
            $manager->persist($dummy);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/dummy_dates?dummyDate=2015-04-01', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $response->toArray()['hydra:totalItems']);
    }

    public function testCustomSearchFilterUsingDoctrineExpressions(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Custom Doctrine expression filter is ORM only.');
        }

        $this->recreateSchema([Dummy::class, RelatedDummy::class, ThirdLevel::class, FourthLevel::class]);
        $this->createDummyWithRelatedDummiesAndThirdLevel(3);

        $response = self::createClient()->request('GET', '/dummy_resource_with_custom_filter?custom=3', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $response->toArray()['hydra:totalItems']);
    }

    public function testSearchOnSubEntityWithStringIdentifier(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('DummySubEntity is ORM only.');
        }

        $this->recreateSchema([DummyWithSubEntity::class, DummySubEntity::class]);
        $manager = $this->getManager();
        $subEntity = new DummySubEntity('stringId', 'someName');
        $mainEntity = new DummyWithSubEntity();
        $mainEntity->setSubEntity($subEntity);
        $mainEntity->setName('main');
        $manager->persist($subEntity);
        $manager->persist($mainEntity);
        $manager->flush();

        $response = self::createClient()->request('GET', '/dummy_with_subresource?subEntity=/dummy_subresource/stringId', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $response->toArray()['hydra:totalItems']);
    }

    public function testFiltersCanUseUuids(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Issue5735 fixture is ORM only.');
        }

        $this->recreateSchema([Group::class, Issue5735User::class]);
        $manager = $this->getManager();

        $group1 = new Group();
        $group1->setUuid(SymfonyUuid::fromString('61817181-0ecc-42fb-a6e7-d97f2ddcb344'));
        $manager->persist($group1);
        for ($i = 0; $i < 2; ++$i) {
            $user = new Issue5735User();
            $user->addGroup($group1);
            $manager->persist($user);
        }
        $manager->persist(new Issue5735User());

        $group2 = new Group();
        $group2->setUuid(SymfonyUuid::fromString('32510d53-f737-4e70-8d9d-58e292c871f8'));
        $manager->persist($group2);
        $user = new Issue5735User();
        $user->addGroup($group2);
        $manager->persist($user);
        $manager->persist(new Issue5735User());

        $manager->flush();

        $response = self::createClient()->request(
            'GET',
            '/issue5735/issue5735_users?groups[]=/issue5735/groups/61817181-0ecc-42fb-a6e7-d97f2ddcb344&groups[]=/issue5735/groups/32510d53-f737-4e70-8d9d-58e292c871f8',
            ['headers' => ['Accept' => 'application/ld+json']]
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $response->toArray()['hydra:totalItems']);
    }

    /**
     * @return class-string
     */
    private function dummyClass(): string
    {
        return $this->isMongoDB() ? DummyDocument::class : Dummy::class;
    }

    /**
     * @return class-string
     */
    private function relatedDummyClass(): string
    {
        return $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class;
    }

    /**
     * @return class-string
     */
    private function thirdLevelClass(): string
    {
        return $this->isMongoDB() ? ThirdLevelDocument::class : ThirdLevel::class;
    }

    /**
     * @return class-string
     */
    private function fourthLevelClass(): string
    {
        return $this->isMongoDB() ? FourthLevelDocument::class : FourthLevel::class;
    }

    /**
     * @param class-string $resource
     */
    private function createDummies(string $resource, int $nb): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDummy('SomeDummyTest'.$i);
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->nameConverted = 'Converted '.$i;
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    /**
     * @param class-string $embeddedClass
     * @param class-string $embeddableClass
     */
    private function createEmbeddedDummies(string $embeddedClass, string $embeddableClass, int $nb): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $embeddedClass();
            $dummy->setName('Dummy #'.$i);
            $embeddable = new $embeddableClass();
            $embeddable->setDummyName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddable);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    /**
     * @param class-string $resource
     * @param class-string $relatedResource
     */
    private function createDummiesEachWithRelatedDummies(string $resource, string $relatedResource, int $nb, int $nbRelated): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            for ($j = 1; $j <= $nbRelated; ++$j) {
                $relatedDummy = new $relatedResource();
                $relatedDummy->setName('RelatedDummy'.$j.$i);
                $relatedDummy->setAge((int) ($j.$i));
                $manager->persist($relatedDummy);
                $dummy->addRelatedDummy($relatedDummy);
            }
            $manager->persist($dummy);
        }
        $manager->flush();
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
        $manager->flush();
        $manager->clear();
    }

    private function createDummyCarWithColors(): void
    {
        $manager = $this->getManager();
        $car = new DummyCar();
        $car->setName('mustli');
        $car->setCanSell(true);
        $car->setAvailableAt(new \DateTime());
        $manager->persist($car);
        $manager->flush();

        $color1 = new DummyCarColor();
        $color1->setProp('red');
        $color1->setCar($car);
        $manager->persist($color1);

        $color2 = new DummyCarColor();
        $color2->setProp('blue');
        $color2->setCar($car);
        $manager->persist($color2);
        $manager->flush();

        $car->setColors(new ArrayCollection([$color1, $color2]));
        $manager->persist($car);
        $manager->flush();
    }

    private function createDummyWithFourthLevelRelation(): void
    {
        $manager = $this->getManager();

        $fourthLevelClass = $this->fourthLevelClass();
        $thirdLevelClass = $this->thirdLevelClass();
        $relatedDummyClass = $this->relatedDummyClass();
        $dummyClass = $this->dummyClass();

        $fourthLevel = new $fourthLevelClass();
        $fourthLevel->setLevel(4);
        $manager->persist($fourthLevel);

        $thirdLevel = new $thirdLevelClass();
        $thirdLevel->setLevel(3);
        $thirdLevel->setFourthLevel($fourthLevel);
        $manager->persist($thirdLevel);

        $namedRelatedDummy = new $relatedDummyClass();
        $namedRelatedDummy->setName('Hello');
        $namedRelatedDummy->setThirdLevel($thirdLevel);
        $manager->persist($namedRelatedDummy);

        $relatedDummy = new $relatedDummyClass();
        $relatedDummy->setThirdLevel($thirdLevel);
        $manager->persist($relatedDummy);

        $dummy = new $dummyClass();
        $dummy->setName('Dummy with relations');
        $dummy->setRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($relatedDummy);
        $manager->persist($dummy);

        $manager->flush();
        $manager->clear();
    }

    private function createDummyWithRelatedDummiesAndThirdLevel(int $nb): void
    {
        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('Dummy with relations');
        for ($i = 1; $i <= $nb; ++$i) {
            $thirdLevel = new ThirdLevel();
            $relatedDummy = new RelatedDummy();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setThirdLevel($thirdLevel);
            $dummy->addRelatedDummy($relatedDummy);
            $manager->persist($thirdLevel);
            $manager->persist($relatedDummy);
        }
        $manager->persist($dummy);
        $manager->flush();
    }
}
