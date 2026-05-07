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

namespace ApiPlatform\Tests\Functional\JsonApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FourthLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwnedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwningDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class RelatedResourcesInclusionTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            Dummy::class,
            DummyProperty::class,
            DummyGroup::class,
            RelatedDummy::class,
            ThirdLevel::class,
            FourthLevel::class,
            RelatedOwningDummy::class,
            RelatedOwnedDummy::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
        $this->recreateSchema(self::getResources());
    }

    public function testIncludeManyToOneRelation(): void
    {
        $this->seedDummyPropertyObjects(3);

        $response = self::createClient()->request('GET', '/dummy_properties/1?include=group', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonEquals([
            'data' => [
                'id' => '/dummy_properties/1',
                'type' => 'DummyProperty',
                'attributes' => [
                    '_id' => 1,
                    'foo' => 'Foo #1',
                    'bar' => 'Bar #1',
                    'baz' => 'Baz #1',
                    'name_converted' => 'NameConverted #1',
                ],
                'relationships' => [
                    'group' => [
                        'data' => ['type' => 'DummyGroup', 'id' => '/dummy_groups/1'],
                    ],
                    'groups' => ['data' => []],
                ],
            ],
            'included' => [
                [
                    'id' => '/dummy_groups/1',
                    'type' => 'DummyGroup',
                    'attributes' => [
                        '_id' => 1,
                        'foo' => 'Foo #1',
                        'bar' => 'Bar #1',
                        'baz' => 'Baz #1',
                    ],
                ],
            ],
        ]);
    }

    public function testIncludeNonExistingRelation(): void
    {
        $this->seedDummyPropertyObjects(3);

        $response = self::createClient()->request('GET', '/dummy_properties/1?include=foo', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/dummy_properties/1', $body['data']['id']);
        $this->assertArrayNotHasKey('included', $body);
    }

    public function testIncludeKeepsMainAttributesUnfiltered(): void
    {
        $this->seedDummyPropertyObjects(3);

        $response = self::createClient()->request(
            'GET',
            '/dummy_properties/1?include=group&fields[group]=id,foo&fields[DummyProperty]=bar,baz',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            'data' => [
                'id' => '/dummy_properties/1',
                'type' => 'DummyProperty',
                'attributes' => ['bar' => 'Bar #1', 'baz' => 'Baz #1'],
                'relationships' => [
                    'group' => ['data' => ['type' => 'DummyGroup', 'id' => '/dummy_groups/1']],
                ],
            ],
            'included' => [
                [
                    'id' => '/dummy_groups/1',
                    'type' => 'DummyGroup',
                    'attributes' => ['_id' => 1, 'foo' => 'Foo #1'],
                ],
            ],
        ]);
    }

    public function testIncludeWithSparseFieldsForRelationOnly(): void
    {
        $this->seedDummyPropertyObjects(3);

        $response = self::createClient()->request(
            'GET',
            '/dummy_properties/1?include=group&fields[group]=id,foo',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            'data' => [
                'id' => '/dummy_properties/1',
                'type' => 'DummyProperty',
                'relationships' => [
                    'group' => ['data' => ['type' => 'DummyGroup', 'id' => '/dummy_groups/1']],
                ],
            ],
            'included' => [
                [
                    'id' => '/dummy_groups/1',
                    'type' => 'DummyGroup',
                    'attributes' => ['_id' => 1, 'foo' => 'Foo #1'],
                ],
            ],
        ]);
    }

    public function testIncludeManyToMany(): void
    {
        $this->seedDummyPropertyObjectsWithGroups(1, 3);

        $response = self::createClient()->request('GET', '/dummy_properties/1?include=groups', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(3, $body['data']['relationships']['groups']['data']);
        $this->assertCount(3, $body['included']);
        $includedIds = array_column($body['included'], 'id');
        $this->assertContains('/dummy_groups/2', $includedIds);
        $this->assertContains('/dummy_groups/3', $includedIds);
        $this->assertContains('/dummy_groups/4', $includedIds);
    }

    public function testIncludeManyToManyAndManyToOne(): void
    {
        $this->seedDummyPropertyObjectsWithGroups(1, 3);

        $response = self::createClient()->request('GET', '/dummy_properties/1?include=groups,group', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        // 1 group (manyToOne) + 3 groups (manyToMany) = 4 included.
        $this->assertCount(4, $body['included']);
        $includedIds = array_column($body['included'], 'id');
        $this->assertContains('/dummy_groups/1', $includedIds);
        $this->assertContains('/dummy_groups/2', $includedIds);
        $this->assertContains('/dummy_groups/3', $includedIds);
        $this->assertContains('/dummy_groups/4', $includedIds);
    }

    public function testIncludeRelatedDummyAndItsThirdLevel(): void
    {
        $this->seedDummiesWithRelatedDummyAndThirdLevel(1);

        $response = self::createClient()->request('GET', '/dummies/1?include=relatedDummy', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/dummies/1', $body['data']['id']);
        $this->assertSame('Dummy', $body['data']['type']);
        $this->assertSame(
            '/related_dummies/1',
            $body['data']['relationships']['relatedDummy']['data']['id'],
        );
        $this->assertCount(1, $body['included']);
        $this->assertSame('/related_dummies/1', $body['included'][0]['id']);
        $this->assertSame('RelatedDummy', $body['included'][0]['type']);
        $this->assertSame('RelatedDummy #1', $body['included'][0]['attributes']['name']);
        $this->assertSame(
            '/third_levels/1',
            $body['included'][0]['relationships']['thirdLevel']['data']['id'],
        );
    }

    public function testIncludeFromPath(): void
    {
        $this->seedDummyWithFourthLevel();

        $response = self::createClient()->request(
            'GET',
            '/dummies/1?include=relatedDummy.thirdLevel.fourthLevel',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/dummies/1', $body['data']['id']);
        $includedIds = array_column($body['included'], 'id');
        // relatedDummy + thirdLevel + fourthLevel
        $this->assertContains('/related_dummies/1', $includedIds);
        $this->assertContains('/third_levels/1', $includedIds);
        $this->assertContains('/fourth_levels/1', $includedIds);
        $this->assertCount(3, $body['included']);
    }

    public function testIncludeFromPathWithCollection(): void
    {
        $this->seedDummyWithRelatedDummiesAndTheirThirdLevel(3);

        $response = self::createClient()->request(
            'GET',
            '/dummies/1?include=relatedDummies.thirdLevel',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(3, $body['data']['relationships']['relatedDummies']['data']);
        $includedIds = array_column($body['included'], 'id');
        // 3 related_dummies + 3 third_levels (each its own) = 6
        $this->assertCount(6, $body['included']);
        $this->assertContains('/related_dummies/1', $includedIds);
        $this->assertContains('/related_dummies/2', $includedIds);
        $this->assertContains('/related_dummies/3', $includedIds);
        $this->assertContains('/third_levels/1', $includedIds);
        $this->assertContains('/third_levels/2', $includedIds);
        $this->assertContains('/third_levels/3', $includedIds);
    }

    public function testIncludeDoesNotIncludeRequestedResource(): void
    {
        $this->seedRelatedOwningDummyOneToOne();

        $response = self::createClient()->request(
            'GET',
            '/dummies/1?include=relatedOwningDummy.ownedDummy',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/dummies/1', $body['data']['id']);
        $this->assertSame(
            '/related_owning_dummies/1',
            $body['data']['relationships']['relatedOwningDummy']['data']['id'],
        );
        // Path leads back to the requested resource — only RelatedOwningDummy stays in included, not Dummy itself.
        $includedIds = array_column($body['included'], 'id');
        $this->assertCount(1, $body['included']);
        $this->assertSame('/related_owning_dummies/1', $body['included'][0]['id']);
        $this->assertNotContains('/dummies/1', $includedIds);
    }

    public function testIncludeDoesNotDuplicateSharedThirdLevel(): void
    {
        $this->seedDummyWithRelatedDummiesSharingThirdLevel(3);

        $response = self::createClient()->request(
            'GET',
            '/dummies/1?include=relatedDummies.thirdLevel',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(3, $body['data']['relationships']['relatedDummies']['data']);
        $includedIds = array_column($body['included'], 'id');
        // 3 related_dummies + 1 shared third_level = 4 included entries.
        $this->assertCount(4, $body['included']);
        $thirdLevelEntries = array_filter($body['included'], static fn (array $e): bool => 'ThirdLevel' === $e['type']);
        $this->assertCount(1, $thirdLevelEntries);
    }

    public function testIncludeRelationOnCollection(): void
    {
        $this->seedDummyPropertyObjects(3);

        $response = self::createClient()->request('GET', '/dummy_properties?include=group', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(3, $body['data']);
        // Each property has its own group → 3 included.
        $this->assertCount(3, $body['included']);
        $includedIds = array_column($body['included'], 'id');
        $this->assertContains('/dummy_groups/1', $includedIds);
        $this->assertContains('/dummy_groups/2', $includedIds);
        $this->assertContains('/dummy_groups/3', $includedIds);
    }

    public function testIncludeOnCollectionDeduplicatesSharedRelation(): void
    {
        $this->seedDummyPropertyObjectsWithSharedGroup(3);

        $response = self::createClient()->request('GET', '/dummy_properties?include=group', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(3, $body['data']);
        // All 3 properties share 1 group → only 1 included entry.
        $this->assertCount(1, $body['included']);
        $this->assertSame('/dummy_groups/1', $body['included'][0]['id']);
    }

    public function testIncludeOnCollectionWithDifferingNumberOfGroupsDeduplicates(): void
    {
        $this->seedDummyPropertyObjectsWithDifferentNumberOfRelatedGroups(2);

        $response = self::createClient()->request('GET', '/dummy_properties?include=groups', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(2, $body['data']);
        // Property 1 has [group1]; property 2 has [group1, group2]. Dedup → 2 unique groups.
        $this->assertCount(2, $body['included']);
        $includedIds = array_column($body['included'], 'id');
        $this->assertContains('/dummy_groups/1', $includedIds);
        $this->assertContains('/dummy_groups/2', $includedIds);
    }

    public function testIncludeFromPathOnCollection(): void
    {
        $this->seedDummiesWithRelatedDummyAndThirdLevel(3);

        $response = self::createClient()->request(
            'GET',
            '/dummies?include=relatedDummy.thirdLevel',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(3, $body['data']);
        $includedIds = array_column($body['included'], 'id');
        // 3 related dummies + 3 distinct thirdLevels = 6.
        $this->assertCount(6, $body['included']);
        $this->assertContains('/related_dummies/1', $includedIds);
        $this->assertContains('/third_levels/1', $includedIds);
    }

    private function seedDummyPropertyObjects(int $nb): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyProperty = new DummyProperty();
            $dummyGroup = new DummyGroup();
            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = $dummyGroup->{$property} = ucfirst($property)." #{$i}";
            }
            $dummyProperty->nameConverted = "NameConverted #{$i}";
            $dummyProperty->group = $dummyGroup;
            $manager->persist($dummyGroup);
            $manager->persist($dummyProperty);
        }
        $manager->flush();
    }

    private function seedDummyPropertyObjectsWithGroups(int $nb, int $nb2): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyProperty = new DummyProperty();
            $dummyGroup = new DummyGroup();
            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = $dummyGroup->{$property} = ucfirst($property)." #{$i}";
            }
            $dummyProperty->group = $dummyGroup;
            $manager->persist($dummyGroup);

            $dummyProperty->groups = [];
            for ($j = 1; $j <= $nb2; ++$j) {
                $extraGroup = new DummyGroup();
                foreach (['foo', 'bar', 'baz'] as $property) {
                    $extraGroup->{$property} = ucfirst($property).' #'.$i.$j;
                }
                $dummyProperty->groups[] = $extraGroup;
                $manager->persist($extraGroup);
            }
            $manager->persist($dummyProperty);
        }
        $manager->flush();
    }

    private function seedDummyPropertyObjectsWithSharedGroup(int $nb): void
    {
        $manager = $this->getManager();
        $dummyGroup = new DummyGroup();
        foreach (['foo', 'bar', 'baz'] as $property) {
            $dummyGroup->{$property} = ucfirst($property).' #shared';
        }
        $manager->persist($dummyGroup);

        for ($i = 1; $i <= $nb; ++$i) {
            $dummyProperty = new DummyProperty();
            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = ucfirst($property)." #{$i}";
            }
            $dummyProperty->group = $dummyGroup;
            $manager->persist($dummyProperty);
        }
        $manager->flush();
    }

    private function seedDummyPropertyObjectsWithDifferentNumberOfRelatedGroups(int $nb): void
    {
        $manager = $this->getManager();
        $dummyGroups = [];
        for ($i = 1; $i <= $nb; ++$i) {
            $dummyGroup = new DummyGroup();
            $dummyProperty = new DummyProperty();
            foreach (['foo', 'bar', 'baz'] as $property) {
                $dummyProperty->{$property} = $dummyGroup->{$property} = ucfirst($property)." #{$i}";
            }
            $manager->persist($dummyGroup);
            $dummyGroups[$i] = $dummyGroup;

            $dummyProperty->groups = [];
            for ($j = 1; $j <= $i; ++$j) {
                $dummyProperty->groups[] = $dummyGroups[$j];
            }
            $manager->persist($dummyProperty);
        }
        $manager->flush();
    }

    private function seedDummiesWithRelatedDummyAndThirdLevel(int $nb): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $thirdLevel = new ThirdLevel();
            $relatedDummy = new RelatedDummy();
            $relatedDummy->setName("RelatedDummy #{$i}");
            $relatedDummy->thirdLevel = $thirdLevel;

            $dummy = new Dummy();
            $dummy->setName("Dummy #{$i}");
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setRelatedDummy($relatedDummy);

            $manager->persist($thirdLevel);
            $manager->persist($relatedDummy);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummyWithFourthLevel(): void
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
        $namedRelatedDummy->thirdLevel = $thirdLevel;
        $manager->persist($namedRelatedDummy);

        $relatedDummy = new RelatedDummy();
        $relatedDummy->thirdLevel = $thirdLevel;
        $manager->persist($relatedDummy);

        $dummy = new Dummy();
        $dummy->setName('Dummy with relations');
        $dummy->setRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($relatedDummy);
        $manager->persist($dummy);

        $manager->flush();
        // Detach so the request side hydrates from DB instead of seeing in-memory
        // FourthLevel.badThirdLevel left at its declared null default.
        $manager->clear();
    }

    private function seedDummyWithRelatedDummiesAndTheirThirdLevel(int $nb): void
    {
        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('Dummy with relations');

        for ($i = 1; $i <= $nb; ++$i) {
            $thirdLevel = new ThirdLevel();
            $relatedDummy = new RelatedDummy();
            $relatedDummy->setName("RelatedDummy #{$i}");
            $relatedDummy->thirdLevel = $thirdLevel;
            $dummy->addRelatedDummy($relatedDummy);

            $manager->persist($thirdLevel);
            $manager->persist($relatedDummy);
        }
        $manager->persist($dummy);
        $manager->flush();
    }

    private function seedDummyWithRelatedDummiesSharingThirdLevel(int $nb): void
    {
        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('Dummy with relations');
        $thirdLevel = new ThirdLevel();

        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = new RelatedDummy();
            $relatedDummy->setName("RelatedDummy #{$i}");
            $relatedDummy->thirdLevel = $thirdLevel;
            $dummy->addRelatedDummy($relatedDummy);
            $manager->persist($relatedDummy);
        }
        $manager->persist($thirdLevel);
        $manager->persist($dummy);
        $manager->flush();
    }

    private function seedRelatedOwningDummyOneToOne(): void
    {
        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('plop');
        $manager->persist($dummy);

        $relatedOwningDummy = new RelatedOwningDummy();
        $relatedOwningDummy->setOwnedDummy($dummy);
        $manager->persist($relatedOwningDummy);
        $manager->flush();
    }
}
