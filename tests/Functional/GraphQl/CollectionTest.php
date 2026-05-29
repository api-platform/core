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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\GraphQl\Test\GraphQlTestTrait;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\CompositePrimitiveItem as CompositePrimitiveItemDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCustomQuery as DummyCustomQueryDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDifferentGraphQlSerializationGroup as DummyDifferentGraphQlSerializationGroupDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyGroup as DummyGroupDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Foo as FooDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FooDummy as FooDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MusicGroup as MusicGroupDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SoMany as SoManyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel as ThirdLevelDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\VideoGame as VideoGameDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeLabel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositePrimitiveItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDifferentGraphQlSerializationGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FooDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MusicGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SoMany;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VideoGame;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CollectionTest extends ApiTestCase
{
    use GraphQlTestTrait;
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
            ThirdLevel::class,
            DummyGroup::class,
            DummyCustomQuery::class,
            DummyDifferentGraphQlSerializationGroup::class,
            Foo::class,
            FooDummy::class,
            SoMany::class,
            MusicGroup::class,
            VideoGame::class,
            CompositeRelation::class,
            CompositeItem::class,
            CompositeLabel::class,
            CompositePrimitiveItem::class,
        ];
    }

    public function testRetrieveCollectionWithRelations(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithRelatedDummyAndThirdLevel(4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies {
                ...dummyFields
              }
            }
            fragment dummyFields on DummyCursorConnection {
              edges {
                node {
                  id
                  name
                  relatedDummy {
                    name
                    thirdLevel { id level }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertSame('Dummy #3', $edges[2]['node']['name']);
        $this->assertSame('RelatedDummy #3', $edges[2]['node']['relatedDummy']['name']);
        $this->assertSame(3, $edges[2]['node']['relatedDummy']['thirdLevel']['level']);
    }

    public function testRetrieveEmptyCollection(): void
    {
        $this->recreateDummiesAndRelated();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies {
                edges { node { name } }
                pageInfo {
                  startCursor
                  endCursor
                  hasNextPage
                  hasPreviousPage
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['dummies'];
        $this->assertCount(0, $data['edges']);
        $this->assertNull($data['pageInfo']['endCursor']);
        $this->assertNull($data['pageInfo']['startCursor']);
        $this->assertFalse($data['pageInfo']['hasNextPage']);
        $this->assertFalse($data['pageInfo']['hasPreviousPage']);
    }

    public function testRetrieveCollectionWithNestedCollection(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesEachWithRelatedDummies(4, 3);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies {
                edges {
                  node {
                    name
                    relatedDummies {
                      edges { node { name } }
                    }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertSame('Dummy #3', $edges[2]['node']['name']);
        $this->assertSame('RelatedDummy23', $edges[2]['node']['relatedDummies']['edges'][1]['node']['name']);
    }

    public function testRetrieveInverseSideNestedCollection(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? VideoGameDocument::class : VideoGame::class,
            $this->isMongoDB() ? MusicGroupDocument::class : MusicGroup::class,
        ]);
        $this->seedVideoGameWithMusicGroups();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              musicGroups {
                edges {
                  node {
                    name
                    videoGames { edges { node { name } } }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $edges = $response->toArray()['data']['musicGroups']['edges'];
        $this->assertSame('Sum 41', $edges[0]['node']['name']);
        $this->assertSame('Guitar Hero', $edges[0]['node']['videoGames']['edges'][0]['node']['name']);
        $this->assertSame('Franz Ferdinand', $edges[1]['node']['name']);
        $this->assertSame('Guitar Hero', $edges[1]['node']['videoGames']['edges'][0]['node']['name']);
    }

    public function testRetrieveCollectionAndItemTogether(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyDocument::class : Dummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
            $this->isMongoDB() ? ThirdLevelDocument::class : ThirdLevel::class,
            $this->isMongoDB() ? DummyGroupDocument::class : DummyGroup::class,
        ]);
        $this->seedDummiesWithDate(3);
        $this->seedDummyGroups(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies {
                edges { node { name dummyDate } }
              }
              dummyGroup(id: "/dummy_groups/2") {
                foo
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data'];
        $this->assertSame('Dummy #2', $data['dummies']['edges'][1]['node']['name']);
        $this->assertSame('2015-04-02', $data['dummies']['edges'][1]['node']['dummyDate']);
        $this->assertSame('Foo #2', $data['dummyGroup']['foo']);
    }

    public function testFirstNItems(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummies(4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(first: 2) {
                edges { node { name } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $response->toArray()['data']['dummies']['edges']);
    }

    public function testFirstNItemsOnNestedCollection(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesEachWithRelatedDummies(2, 5);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(first: 1) {
                edges {
                  node {
                    name
                    relatedDummies(first: 2) {
                      edges { node { name } }
                    }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertCount(1, $edges);
        $this->assertCount(2, $edges[0]['node']['relatedDummies']['edges']);
    }

    public function testPaginationCursorsForward(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummies(4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(first: 2) {
                edges { cursor node { name } }
                totalCount
                pageInfo { startCursor endCursor hasNextPage hasPreviousPage }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['dummies'];
        $this->assertCount(2, $data['edges']);
        $this->assertSame(4, $data['totalCount']);
        $this->assertSame('MQ==', $data['pageInfo']['endCursor']);
        $this->assertTrue($data['pageInfo']['hasNextPage']);
        $this->assertFalse($data['pageInfo']['hasPreviousPage']);
        $this->assertSame('MQ==', $data['edges'][1]['cursor']);
        $this->assertSame('Dummy #2', $data['edges'][1]['node']['name']);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(first: 2, after: "MQ==") {
                edges { cursor node { name } }
                pageInfo { endCursor hasNextPage }
              }
            }
            QUERY);

        $data = $response->toArray()['data']['dummies'];
        $this->assertCount(2, $data['edges']);
        $this->assertSame('Dummy #3', $data['edges'][0]['node']['name']);
        $this->assertSame('Mg==', $data['edges'][0]['cursor']);
        $this->assertFalse($data['pageInfo']['hasNextPage']);
    }

    public function testPaginationCursorsBackward(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummies(4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(last: 2) {
                edges { cursor node { name } }
                totalCount
                pageInfo { startCursor hasPreviousPage hasNextPage }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['dummies'];
        $this->assertCount(2, $data['edges']);
        $this->assertSame(4, $data['totalCount']);
        $this->assertSame('Mg==', $data['pageInfo']['startCursor']);
        $this->assertTrue($data['pageInfo']['hasPreviousPage']);
        $this->assertSame('Dummy #4', $data['edges'][1]['node']['name']);
        $this->assertSame('Mw==', $data['edges'][1]['cursor']);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(last: 2, before: "Mw==") {
                edges { cursor node { name } }
                pageInfo { startCursor hasPreviousPage }
              }
            }
            QUERY);

        $data = $response->toArray()['data']['dummies'];
        $this->assertCount(2, $data['edges']);
        $this->assertSame('Dummy #2', $data['edges'][0]['node']['name']);
        $this->assertSame('MQ==', $data['edges'][0]['cursor']);
    }

    public function testSoManyPartialPagination(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('SoMany scenario @!mongodb');
        }
        $this->recreateSchema([SoMany::class]);
        $this->seedSoManies(4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              soManies(first: 2) {
                edges { cursor node { content } }
                totalCount
                pageInfo { startCursor endCursor hasNextPage hasPreviousPage }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['soManies'];
        $this->assertSame('MA==', $data['pageInfo']['startCursor']);
        $this->assertSame('MQ==', $data['pageInfo']['endCursor']);
        $this->assertFalse($data['pageInfo']['hasNextPage']);
        $this->assertFalse($data['pageInfo']['hasPreviousPage']);
        $this->assertSame(0, $data['totalCount']);
        $this->assertSame('Many #2', $data['edges'][1]['node']['content']);
        $this->assertSame('MQ==', $data['edges'][1]['cursor']);
    }

    public function testCollectionWithPaginationDisabled(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? FooDocument::class : Foo::class]);
        $this->seedFoosWithFakeNames(4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              foos {
                id
                name
                bar
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $foos = $response->toArray()['data']['foos'];
        $this->assertSame('/foos/4', $foos[3]['id']);
        $this->assertSame('Separativeness', $foos[3]['name']);
        $this->assertSame('Sit', $foos[3]['bar']);
    }

    public function testCustomCollectionQuery(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomQueryDocument::class : DummyCustomQuery::class]);
        $this->seedDummyCustomQuery(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              testCollectionDummyCustomQueries {
                edges { node { message } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => [
                'testCollectionDummyCustomQueries' => [
                    'edges' => [
                        ['node' => ['message' => 'Success!']],
                        ['node' => ['message' => 'Success!']],
                    ],
                ],
            ],
        ], $response->toArray());
    }

    public function testCustomCollectionQueryReadAndSerializeFalse(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomQueryDocument::class : DummyCustomQuery::class]);
        $this->seedDummyCustomQuery(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              testCollectionNoReadAndSerializeDummyCustomQueries {
                edges { node { message } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => ['testCollectionNoReadAndSerializeDummyCustomQueries' => ['edges' => []]],
        ], $response->toArray());
    }

    public function testCustomCollectionQueryWithCustomArguments(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomQueryDocument::class : DummyCustomQuery::class]);
        $this->seedDummyCustomQuery(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              testCollectionCustomArgumentsDummyCustomQueries(customArgumentString: "A string") {
                edges { node { message customArgs } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => [
                'testCollectionCustomArgumentsDummyCustomQueries' => [
                    'edges' => [
                        ['node' => ['message' => 'Success!', 'customArgs' => ['customArgumentString' => 'A string']]],
                        ['node' => ['message' => 'Success!', 'customArgs' => ['customArgumentString' => 'A string']]],
                    ],
                ],
            ],
        ], $response->toArray());
    }

    public function testRetrieveCompositePrimitiveIdentifierItem(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Composite identifiers @!mongodb');
        }
        $this->recreateSchema([CompositePrimitiveItem::class]);
        $manager = $this->getManager();
        $foo = new CompositePrimitiveItem('Foo', 2016);
        $foo->setDescription('This is foo.');
        $manager->persist($foo);
        $bar = new CompositePrimitiveItem('Bar', 2017);
        $bar->setDescription('This is bar.');
        $manager->persist($bar);
        $manager->flush();
        $manager->clear();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              compositePrimitiveItem(id: "/composite_primitive_items/name=Bar;year=2017") {
                description
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame('This is bar.', $response->toArray()['data']['compositePrimitiveItem']['description']);
    }

    public function testRetrieveCompositeIdentifierItem(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Composite identifiers @!mongodb');
        }
        $this->recreateSchema([CompositeRelation::class, CompositeItem::class, CompositeLabel::class]);
        $this->seedCompositeIdentifierObjects();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              compositeRelation(id: "/composite_relations/compositeItem=1;compositeLabel=1") {
                value
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame('somefoobardummy', $response->toArray()['data']['compositeRelation']['value']);
    }

    public function testCollectionWithNameConverter(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummies(4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies {
                edges { node { name_converted } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            'Converted 2',
            $response->toArray()['data']['dummies']['edges'][1]['node']['name_converted'],
        );
    }

    public function testCollectionWithDifferentSerializationGroups(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyDifferentGraphQlSerializationGroupDocument::class : DummyDifferentGraphQlSerializationGroup::class]);
        $this->seedDummyDifferentGroups(3);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummyDifferentGraphQlSerializationGroups {
                edges { node { name } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $edges = $response->toArray()['data']['dummyDifferentGraphQlSerializationGroups']['edges'];
        $this->assertCount(3, $edges);
        $this->assertArrayHasKey('name', $edges[0]['node']);
        $this->assertArrayNotHasKey('title', $edges[0]['node']);
    }

    public function testPageBasedPagination(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('FooDummy + SoMany scenario @!mongodb');
        }
        $this->recreateSchema([Dummy::class, FooDummy::class, SoMany::class]);
        $this->seedFooDummies(5);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              fooDummies(page: 1) {
                collection { id name }
                paginationInfo { itemsPerPage lastPage totalCount hasNextPage }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['fooDummies'];
        $this->assertCount(3, $data['collection']);
        $this->assertSame(3, $data['paginationInfo']['itemsPerPage']);
        $this->assertSame(2, $data['paginationInfo']['lastPage']);
        $this->assertSame(5, $data['paginationInfo']['totalCount']);
        $this->assertTrue($data['paginationInfo']['hasNextPage']);

        $response = $this->executeGraphQl(<<<'QUERY'
            { fooDummies(page: 2) { collection { id name } } }
            QUERY);
        $this->assertCount(2, $response->toArray()['data']['fooDummies']['collection']);

        $response = $this->executeGraphQl(<<<'QUERY'
            { fooDummies(page: 3) { collection { id name } } }
            QUERY);
        $this->assertCount(0, $response->toArray()['data']['fooDummies']['collection']);
    }

    public function testPageBasedPaginationWithItemsPerPage(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('FooDummy + SoMany scenario @!mongodb');
        }
        $this->recreateSchema([Dummy::class, FooDummy::class, SoMany::class]);
        $this->seedFooDummies(5);

        $response = $this->executeGraphQl(<<<'QUERY'
            { fooDummies(page: 1, itemsPerPage: 2) { collection { id name } } }
            QUERY);
        $this->assertCount(2, $response->toArray()['data']['fooDummies']['collection']);

        $response = $this->executeGraphQl(<<<'QUERY'
            { fooDummies(page: 2, itemsPerPage: 2) { collection { id name } } }
            QUERY);
        $this->assertCount(2, $response->toArray()['data']['fooDummies']['collection']);

        $response = $this->executeGraphQl(<<<'QUERY'
            { fooDummies(page: 3, itemsPerPage: 2) { collection { id name } } }
            QUERY);
        $this->assertCount(1, $response->toArray()['data']['fooDummies']['collection']);
    }

    public function testMixedPagination(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('FooDummy + SoMany scenario @!mongodb');
        }
        $this->recreateSchema([Dummy::class, FooDummy::class, SoMany::class]);
        $this->seedFooDummies(5);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              fooDummies(page: 1) {
                collection {
                  id name
                  soManies(first: 2) {
                    edges { cursor node { content } }
                    pageInfo { startCursor endCursor hasNextPage hasPreviousPage }
                  }
                }
                paginationInfo { itemsPerPage lastPage totalCount hasNextPage }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['fooDummies'];
        $this->assertCount(3, $data['collection']);
        $this->assertCount(2, $data['collection'][2]['soManies']['edges']);
        $this->assertSame('So many 1', $data['collection'][2]['soManies']['edges'][1]['node']['content']);
        $this->assertSame('MA==', $data['collection'][2]['soManies']['pageInfo']['startCursor']);
        $this->assertTrue($data['paginationInfo']['hasNextPage']);
    }

    public function testPaginationOnlyHasNextPage(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('FooDummy + SoMany scenario @!mongodb');
        }
        $this->recreateSchema([Dummy::class, FooDummy::class, SoMany::class]);
        $this->seedFooDummies(4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              fooDummies(page: 1, itemsPerPage: 2) {
                collection {
                  id name
                  soManies(first: 2) {
                    edges { node { content } cursor }
                    pageInfo { startCursor endCursor hasNextPage hasPreviousPage }
                  }
                }
                paginationInfo { hasNextPage }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['fooDummies'];
        $this->assertCount(2, $data['collection']);
        $this->assertArrayHasKey('id', $data['collection'][1]);
        $this->assertArrayHasKey('name', $data['collection'][1]);
        $this->assertCount(2, $data['collection'][1]['soManies']['edges']);
        $this->assertSame('So many 1', $data['collection'][1]['soManies']['edges'][1]['node']['content']);
        $this->assertSame('MA==', $data['collection'][1]['soManies']['pageInfo']['startCursor']);
        $this->assertTrue($data['paginationInfo']['hasNextPage']);

        $response = $this->executeGraphQl(<<<'QUERY'
            { fooDummies(page: 2) { paginationInfo { hasNextPage } } }
            QUERY);
        $this->assertFalse($response->toArray()['data']['fooDummies']['paginationInfo']['hasNextPage']);
    }

    private function recreateDummiesAndRelated(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyDocument::class : Dummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
            $this->isMongoDB() ? ThirdLevelDocument::class : ThirdLevel::class,
        ]);
    }

    private function newDummy(): object
    {
        $class = $this->isMongoDB() ? DummyDocument::class : Dummy::class;

        return new $class();
    }

    private function newRelated(): object
    {
        $class = $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class;

        return new $class();
    }

    private function newThirdLevel(): object
    {
        $class = $this->isMongoDB() ? ThirdLevelDocument::class : ThirdLevel::class;

        return new $class();
    }

    private function seedDummies(int $count): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $dummy = $this->newDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));
            $dummy->setDummy('SomeDummyTest'.$i);
            $dummy->nameConverted = 'Converted '.$i;
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummiesWithDate(int $count): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $dummy = $this->newDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));
            if ($count !== $i) {
                $dummy->setDummyDate(new \DateTime(\sprintf('2015-04-%d', $i), new \DateTimeZone('UTC')));
            }
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummiesEachWithRelatedDummies(int $count, int $nbRelated): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $dummy = $this->newDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));

            for ($j = 1; $j <= $nbRelated; ++$j) {
                $related = $this->newRelated();
                $related->setName('RelatedDummy'.$j.$i);
                $related->setAge((int) ($j.$i));
                $manager->persist($related);
                $dummy->addRelatedDummy($related);
            }
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummiesWithRelatedDummyAndThirdLevel(int $count): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $third = $this->newThirdLevel();

            $related = $this->newRelated();
            $related->setName('RelatedDummy #'.$i);
            $related->setThirdLevel($third);

            $dummy = $this->newDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));
            $dummy->setRelatedDummy($related);

            $manager->persist($third);
            $manager->persist($related);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummyGroups(int $count): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DummyGroupDocument::class : DummyGroup::class;
        for ($i = 1; $i <= $count; ++$i) {
            $g = new $class();
            foreach (['foo', 'bar', 'baz', 'qux'] as $p) {
                $g->{$p} = ucfirst($p).' #'.$i;
            }
            $manager->persist($g);
        }
        $manager->flush();
    }

    private function seedDummyCustomQuery(int $count): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DummyCustomQueryDocument::class : DummyCustomQuery::class;
        for ($i = 1; $i <= $count; ++$i) {
            $manager->persist(new $class());
        }
        $manager->flush();
    }

    private function seedDummyDifferentGroups(int $count): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DummyDifferentGraphQlSerializationGroupDocument::class : DummyDifferentGraphQlSerializationGroup::class;
        for ($i = 1; $i <= $count; ++$i) {
            $d = new $class();
            $d->setName('Name #'.$i);
            $d->setTitle('Title #'.$i);
            $manager->persist($d);
        }
        $manager->flush();
    }

    private function seedFoosWithFakeNames(int $count): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? FooDocument::class : Foo::class;
        $names = ['Hawsepipe', 'Sthenelus', 'Ephesian', 'Separativeness', 'Balbo'];
        $bars = ['Lorem', 'Ipsum', 'Dolor', 'Sit', 'Amet'];
        for ($i = 0; $i < $count; ++$i) {
            $foo = new $class();
            $foo->setName($names[$i]);
            $foo->setBar($bars[$i]);
            $manager->persist($foo);
        }
        $manager->flush();
    }

    private function seedFooDummies(int $count): void
    {
        $manager = $this->getManager();
        $fooClass = $this->isMongoDB() ? FooDummyDocument::class : FooDummy::class;
        $dummyClass = $this->isMongoDB() ? DummyDocument::class : Dummy::class;
        $soManyClass = $this->isMongoDB() ? SoManyDocument::class : SoMany::class;
        $names = ['Hawsepipe', 'Ephesian', 'Sthenelus', 'Separativeness', 'Balbo'];
        $dummies = ['Lorem', 'Ipsum', 'Dolor', 'Sit', 'Amet'];

        for ($i = 0; $i < $count; ++$i) {
            $dummy = new $dummyClass();
            $dummy->setName($dummies[$i]);

            $foo = new $fooClass();
            $foo->setName($names[$i]);
            $foo->setDummy($dummy);
            for ($j = 0; $j < 3; ++$j) {
                $soMany = new $soManyClass();
                $soMany->content = "So many $j";
                $soMany->fooDummy = $foo;
                $foo->soManies->add($soMany);
            }
            $manager->persist($foo);
        }
        $manager->flush();
    }

    private function seedSoManies(int $count): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? SoManyDocument::class : SoMany::class;
        for ($i = 1; $i <= $count; ++$i) {
            $s = new $class();
            $s->content = 'Many #'.$i;
            $manager->persist($s);
        }
        $manager->flush();
    }

    private function seedVideoGameWithMusicGroups(): void
    {
        $manager = $this->getManager();
        $musicClass = $this->isMongoDB() ? MusicGroupDocument::class : MusicGroup::class;
        $videoClass = $this->isMongoDB() ? VideoGameDocument::class : VideoGame::class;

        $sum41 = new $musicClass();
        $sum41->name = 'Sum 41';
        $manager->persist($sum41);

        $franz = new $musicClass();
        $franz->name = 'Franz Ferdinand';
        $manager->persist($franz);

        $videoGame = new $videoClass();
        $videoGame->name = 'Guitar Hero';
        $videoGame->addMusicGroup($sum41);
        $videoGame->addMusicGroup($franz);
        $manager->persist($videoGame);
        $manager->flush();
    }

    private function seedCompositeIdentifierObjects(): void
    {
        $manager = $this->getManager();
        $item = new CompositeItem();
        $item->setField1('foobar');
        $manager->persist($item);
        $manager->flush();

        for ($i = 0; $i < 4; ++$i) {
            $label = new CompositeLabel();
            $label->setValue('foo-'.$i);
            $manager->persist($label);
            $manager->flush();

            $rel = new CompositeRelation();
            $rel->setCompositeLabel($label);
            $rel->setCompositeItem($item);
            $rel->setValue('somefoobardummy');
            $manager->persist($rel);
        }
        $manager->flush();
        $manager->clear();
    }
}
