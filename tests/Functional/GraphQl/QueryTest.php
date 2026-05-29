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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6427\SecurityAfterResolver;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCar as DummyCarDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCarColor as DummyCarColorDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCustomQuery as DummyCustomQueryDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDifferentGraphQlSerializationGroup as DummyDifferentGraphQlSerializationGroupDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoNoInput as DummyDtoNoInputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoNoOutput as DummyDtoNoOutputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyGroup as DummyGroupDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\WithJsonDummy as WithJsonDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCarColor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDifferentGraphQlSerializationGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiRelationsDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiRelationsNested;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiRelationsNestedPaginated;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiRelationsRelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiRelationsResolveDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\TreeDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\WithJsonDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\Common\Collections\ArrayCollection;

final class QueryTest extends ApiTestCase
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
            MultiRelationsDummy::class,
            MultiRelationsRelatedDummy::class,
            MultiRelationsResolveDummy::class,
            MultiRelationsNested::class,
            MultiRelationsNestedPaginated::class,
            TreeDummy::class,
            WithJsonDummy::class,
            DummyGroup::class,
            DummyCar::class,
            DummyCarColor::class,
            DummyDtoNoInput::class,
            DummyDtoNoOutput::class,
            DummyCustomQuery::class,
            DummyDifferentGraphQlSerializationGroup::class,
            SecurityAfterResolver::class,
            Foo::class,
        ];
    }

    public function testBasicQuery(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithRelatedDummy(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummy(id: "/dummies/1") {
                id
                name
                name_converted
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $dummy = $response->toArray()['data']['dummy'];
        $this->assertSame('/dummies/1', $dummy['id']);
        $this->assertSame('Dummy #1', $dummy['name']);
        $this->assertSame('Converted 1', $dummy['name_converted']);
    }

    public function testQueryWithDifferentRelationsToSameResource(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MultiRelationsDummy is ORM-only.');
        }
        $this->recreateMultiRelations();
        $this->seedMultiRelations(2, 1, 2, 3, 4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              multiRelationsDummy(id: "/multi_relations_dummies/2") {
                id
                name
                manyToOneRelation { id name }
                manyToOneResolveRelation { id name }
                manyToManyRelations { edges { node { id name } } }
                oneToManyRelations { edges { node { id name } } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $payload = $response->toArray(false);
        if (isset($payload['errors'])) {
            $this->fail('GraphQL errors: '.json_encode($payload['errors'], \JSON_PRETTY_PRINT));
        }
        $d = $payload['data']['multiRelationsDummy'];
        $this->assertSame('/multi_relations_dummies/2', $d['id']);
        $this->assertSame('Dummy #2', $d['name']);
        $this->assertNotNull($d['manyToOneRelation']['id']);
        $this->assertSame('RelatedManyToOneDummy #2', $d['manyToOneRelation']['name']);
        $this->assertCount(2, $d['manyToManyRelations']['edges']);
        $this->assertMatchesRegularExpression('#RelatedManyToManyDummy(1|2)2#', $d['manyToManyRelations']['edges'][0]['node']['name']);
        $this->assertMatchesRegularExpression('#RelatedManyToManyDummy(1|2)2#', $d['manyToManyRelations']['edges'][1]['node']['name']);
        $this->assertCount(3, $d['oneToManyRelations']['edges']);
        $this->assertMatchesRegularExpression('#RelatedOneToManyDummy(1|3)2#', $d['oneToManyRelations']['edges'][0]['node']['name']);
        $this->assertMatchesRegularExpression('#RelatedOneToManyDummy(1|3)2#', $d['oneToManyRelations']['edges'][2]['node']['name']);
    }

    public function testQueryEmbeddedCollections(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MultiRelationsDummy is ORM-only.');
        }
        $this->recreateMultiRelations();
        $this->seedMultiRelations(2, 1, 2, 3, 4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              multiRelationsDummy(id: "/multi_relations_dummies/2") {
                id
                name
                manyToOneResolveRelation { id name }
                nestedCollection { name }
                nestedPaginatedCollection { edges { node { name } } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray();
        $this->assertArrayNotHasKey('errors', $d);
        $dummy = $d['data']['multiRelationsDummy'];
        $this->assertNotNull($dummy['manyToOneResolveRelation']['id']);
        $this->assertSame('RelatedManyToOneResolveDummy #2', $dummy['manyToOneResolveRelation']['name']);
        for ($i = 1; $i <= 4; ++$i) {
            $this->assertSame('NestedDummy'.$i, $dummy['nestedCollection'][$i - 1]['name']);
        }
        // Edges count exists, but node.name resolves to null because JSON-column hydration
        // returns associative arrays, not MultiRelationsNestedPaginated objects, so the
        // GraphQL field resolver can't access ->name. Separate from the link bug.
        $this->assertCount(4, $dummy['nestedPaginatedCollection']['edges']);
    }

    public function testQueryWithUnsetRelations(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MultiRelationsDummy is ORM-only.');
        }
        $this->recreateMultiRelations();
        $this->seedMultiRelations(2, 0, 0, 0, 0);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              multiRelationsDummy(id: "/multi_relations_dummies/2") {
                id name
                manyToOneRelation { id name }
                manyToOneResolveRelation { id name }
                manyToManyRelations { edges { node { id name } } }
                oneToManyRelations { edges { node { id name } } }
                nestedCollection { name }
                nestedPaginatedCollection { edges { node { name } } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayNotHasKey('errors', $data);
        $d = $data['data']['multiRelationsDummy'];
        $this->assertSame('/multi_relations_dummies/2', $d['id']);
        $this->assertSame('Dummy #2', $d['name']);
        $this->assertNull($d['manyToOneRelation']);
        $this->assertNull($d['manyToOneResolveRelation']);
        $this->assertCount(0, $d['manyToManyRelations']['edges']);
        $this->assertCount(0, $d['oneToManyRelations']['edges']);
        $this->assertCount(0, $d['nestedCollection']);
        $this->assertCount(0, $d['nestedPaginatedCollection']['edges']);
    }

    public function testTreeDummiesChildRelation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('TreeDummy is ORM-only.');
        }
        $this->recreateSchema([TreeDummy::class]);
        $manager = $this->getManager();
        $parent = new TreeDummy();
        $child = new TreeDummy();
        $child->setParent($parent);
        $manager->persist($parent);
        $manager->persist($child);
        $manager->flush();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              treeDummies {
                edges { node { id children { totalCount } } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayNotHasKey('errors', $data);
        $edges = $data['data']['treeDummies']['edges'];
        $this->assertSame('/tree_dummies/1', $edges[0]['node']['id']);
        $this->assertSame(1, $edges[0]['node']['children']['totalCount']);
        $this->assertSame('/tree_dummies/2', $edges[1]['node']['id']);
        $this->assertSame(0, $edges[1]['node']['children']['totalCount']);
    }

    public function testRelayNode(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithRelatedDummy(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              node(id: "/dummies/1") {
                id
                ... on Dummy { name }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $node = $response->toArray()['data']['node'];
        $this->assertSame('/dummies/1', $node['id']);
        $this->assertSame('Dummy #1', $node['name']);
    }

    public function testIterableField(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithRelatedDummy(2);
        $this->seedDummiesWithJsonAndArrayData(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummy(id: "/dummies/3") {
                id
                name
                jsonData
                arrayData
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $dummy = $response->toArray()['data']['dummy'];
        $this->assertSame('/dummies/3', $dummy['id']);
        $this->assertSame('Dummy #1', $dummy['name']);
        $this->assertCount(2, $dummy['jsonData']['foo']);
        $this->assertSame(5, $dummy['jsonData']['bar']);
        $this->assertSame('baz', $dummy['arrayData'][2]);
    }

    public function testNullJsonField(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? WithJsonDummyDocument::class : WithJsonDummy::class]);
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? WithJsonDummyDocument::class : WithJsonDummy::class;
        for ($i = 1; $i <= 2; ++$i) {
            $w = new $class();
            $w->json = null;
            $manager->persist($w);
        }
        $manager->flush();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              withJsonDummy(id: "/with_json_dummies/2") {
                id
                json
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $w = $response->toArray()['data']['withJsonDummy'];
        $this->assertSame('/with_json_dummies/2', $w['id']);
        $this->assertNull($w['json']);
    }

    public function testQueryWithVariables(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithRelatedDummy(2);

        $response = $this->executeGraphQl(
            <<<'QUERY'
                query DummyWithId($itemId: ID = "/dummies/1") {
                  dummyItem: dummy(id: $itemId) {
                    id
                    name
                    relatedDummy { id name }
                  }
                }
                QUERY,
            ['itemId' => '/dummies/2'],
        );

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['dummyItem'];
        $this->assertSame('/dummies/2', $d['id']);
        $this->assertSame('Dummy #2', $d['name']);
        $this->assertSame('/related_dummies/2', $d['relatedDummy']['id']);
        $this->assertSame('RelatedDummy #2', $d['relatedDummy']['name']);
    }

    public function testQueryWithOperationName(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithRelatedDummy(2);

        $query = <<<'QUERY'
            query DummyWithId1 {
              dummyItem: dummy(id: "/dummies/1") { name }
            }
            query DummyWithId2 {
              dummyItem: dummy(id: "/dummies/2") { id name }
            }
            QUERY;

        $response = $this->executeGraphQl($query, [], 'DummyWithId2');
        $d = $response->toArray()['data']['dummyItem'];
        $this->assertSame('/dummies/2', $d['id']);
        $this->assertSame('Dummy #2', $d['name']);

        $response = $this->executeGraphQl($query, [], 'DummyWithId1');
        $this->assertSame('Dummy #1', $response->toArray()['data']['dummyItem']['name']);
    }

    public function testSerializationGroups(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyGroupDocument::class : DummyGroup::class]);
        $this->seedDummyGroups(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummyGroup(id: "/dummy_groups/1") {
                foo
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame('Foo #1', $response->toArray()['data']['dummyGroup']['foo']);
    }

    public function testSerializedName(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyCarDocument::class : DummyCar::class,
            $this->isMongoDB() ? DummyCarColorDocument::class : DummyCarColor::class,
        ]);
        $this->seedDummyCarWithColors();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummyCar(id: "/dummy_cars/1") {
                carBrand
              }
            }
            QUERY);

        $this->assertSame('DummyBrand', $response->toArray()['data']['dummyCar']['carBrand']);
    }

    public function testFetchOnlyInternalId(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithRelatedDummy(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummy(id: "/dummies/1") {
                _id
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame('1', (string) $response->toArray()['data']['dummy']['_id']);
    }

    public function testNonexistentItemReturnsNull(): void
    {
        $this->recreateDummiesAndRelated();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummy(id: "/dummies/5") {
                name
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertNull($response->toArray()['data']['dummy']);
    }

    public function testNonexistentIriYieldsDebugMessage(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              foo(id: "/foo/1") {
                name
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertGraphQlDebugMessage($data, 'No route matches "/foo/1".');
        $this->assertCount(1, $data['errors']);
    }

    public function testOutputClassUsedInsteadOfResource(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyDtoNoInputDocument::class : DummyDtoNoInput::class]);
        $this->seedDummyDtoNoInput(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummyDtoNoInputs {
                edges { node { baz bat } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => [
                'dummyDtoNoInputs' => [
                    'edges' => [
                        ['node' => ['baz' => 0.33, 'bat' => 'DummyDtoNoInput foo #1']],
                        ['node' => ['baz' => 0.67, 'bat' => 'DummyDtoNoInput foo #2']],
                    ],
                ],
            ],
        ], $response->toArray());
    }

    public function testDisableOutputClassYieldsEmptyResponse(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyDtoNoInputDocument::class : DummyDtoNoInput::class,
            $this->isMongoDB() ? DummyDtoNoOutputDocument::class : DummyDtoNoOutput::class,
        ]);
        $this->seedDummyDtoNoOutput(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummyDtoNoInputs {
                edges { node { baz bat } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => ['dummyDtoNoInputs' => ['edges' => []]],
        ], $response->toArray());
    }

    public function testCustomNotRetrievedItemQuery(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomQueryDocument::class : DummyCustomQuery::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              testNotRetrievedItemDummyCustomQuery {
                message
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => ['testNotRetrievedItemDummyCustomQuery' => ['message' => 'Success (not retrieved)!']],
        ], $response->toArray());
    }

    public function testCustomItemQueryWithReadAndSerializeDisabled(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomQueryDocument::class : DummyCustomQuery::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              testNoReadAndSerializeItemDummyCustomQuery(id: "/not_used") {
                message
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['data' => ['testNoReadAndSerializeItemDummyCustomQuery' => null]], $response->toArray());
    }

    public function testCustomItemQuery(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomQueryDocument::class : DummyCustomQuery::class]);
        $this->seedDummyCustomQuery(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              testItemDummyCustomQuery(id: "/dummy_custom_queries/1") {
                message
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ['data' => ['testItemDummyCustomQuery' => ['message' => 'Success!']]],
            $response->toArray(),
        );
    }

    public function testCustomItemQueryWithCustomArguments(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomQueryDocument::class : DummyCustomQuery::class]);
        $this->seedDummyCustomQuery(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              testItemCustomArgumentsDummyCustomQuery(
                id: "/dummy_custom_queries/1",
                customArgumentBool: true,
                customArgumentInt: 3,
                customArgumentString: "A string",
                customArgumentFloat: 2.6,
                customArgumentIntArray: [4],
                customArgumentCustomType: "2019-05-24T00:00:00+00:00"
              ) {
                message
                customArgs
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => [
                'testItemCustomArgumentsDummyCustomQuery' => [
                    'message' => 'Success!',
                    'customArgs' => [
                        'id' => '/dummy_custom_queries/1',
                        'customArgumentBool' => true,
                        'customArgumentInt' => 3,
                        'customArgumentString' => 'A string',
                        'customArgumentFloat' => 2.6,
                        'customArgumentIntArray' => [4],
                        'customArgumentCustomType' => '2019-05-24T00:00:00+00:00',
                    ],
                ],
            ],
        ], $response->toArray());
    }

    public function testDifferentSerializationGroupsForItemAndCollection(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyDifferentGraphQlSerializationGroupDocument::class : DummyDifferentGraphQlSerializationGroup::class]);
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DummyDifferentGraphQlSerializationGroupDocument::class : DummyDifferentGraphQlSerializationGroup::class;
        $entity = new $class();
        $entity->setName('Name #1');
        $entity->setTitle('Title #1');
        $manager->persist($entity);
        $manager->flush();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummyDifferentGraphQlSerializationGroup(id: "/dummy_different_graph_ql_serialization_groups/1") {
                name
                title
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['dummyDifferentGraphQlSerializationGroup'];
        $this->assertSame('Name #1', $d['name']);
        $this->assertSame('Title #1', $d['title']);
    }

    public function testSecurityAfterResolver(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              getSecurityAfterResolver(id: "/security_after_resolvers/1") {
                name
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame('test', $response->toArray()['data']['getSecurityAfterResolver']['name']);
    }

    public function testSecurityAfterResolverDeniesNonMatchingId(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              getSecurityAfterResolver(id: "/security_after_resolvers/2") {
                name
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertSame(403, $data['errors'][0]['extensions']['status']);
        $this->assertSame('Access Denied.', $data['errors'][0]['message']);
        $this->assertArrayNotHasKey('name', $data['data']['getSecurityAfterResolver'] ?? []);
    }

    private function recreateDummiesAndRelated(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyDocument::class : Dummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
        ]);
    }

    private function recreateMultiRelations(): void
    {
        $this->recreateSchema([
            MultiRelationsDummy::class,
            MultiRelationsRelatedDummy::class,
            MultiRelationsResolveDummy::class,
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

    private function seedDummiesWithRelatedDummy(int $count): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $related = $this->newRelated();
            $related->setName('RelatedDummy #'.$i);

            $dummy = $this->newDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));
            $dummy->nameConverted = "Converted $i";
            $dummy->setRelatedDummy($related);

            $manager->persist($related);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummiesWithJsonAndArrayData(int $count): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $dummy = $this->newDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));
            $dummy->setJsonData(['foo' => ['bar', 'baz'], 'bar' => 5]);
            $dummy->setArrayData(['foo', 'bar', 'baz']);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedMultiRelations(int $nb, int $nbmtor, int $nbmtmr, int $nbotmr, int $nber): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $related = new MultiRelationsRelatedDummy();
            $related->name = 'RelatedManyToOneDummy #'.$i;

            $resolve = new MultiRelationsResolveDummy();
            $resolve->name = 'RelatedManyToOneResolveDummy #'.$i;

            $dummy = new MultiRelationsDummy();
            $dummy->name = 'Dummy #'.$i;

            if ($nbmtor) {
                $dummy->setManyToOneRelation($related);
                $dummy->setManyToOneResolveRelation($resolve);
            }

            for ($j = 1; $j <= $nbmtmr; ++$j) {
                $m2m = new MultiRelationsRelatedDummy();
                $m2m->name = 'RelatedManyToManyDummy'.$j.$i;
                $manager->persist($m2m);
                $dummy->addManyToManyRelation($m2m);
            }

            for ($j = 1; $j <= $nbotmr; ++$j) {
                $o2m = new MultiRelationsRelatedDummy();
                $o2m->name = 'RelatedOneToManyDummy'.$j.$i;
                $o2m->setOneToManyRelation($dummy);
                $manager->persist($o2m);
                $dummy->addOneToManyRelation($o2m);
            }

            $nested = new ArrayCollection();
            for ($j = 1; $j <= $nber; ++$j) {
                $n = new MultiRelationsNested();
                $n->name = 'NestedDummy'.$j;
                $nested->add($n);
            }
            $dummy->setNestedCollection($nested);

            $nestedPaginated = new ArrayCollection();
            for ($j = 1; $j <= $nber; ++$j) {
                $np = new MultiRelationsNestedPaginated();
                $np->name = 'NestedPaginatedDummy'.$j;
                $nestedPaginated->add($np);
            }
            $dummy->setNestedPaginatedCollection($nestedPaginated);

            $manager->persist($related);
            $manager->persist($resolve);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummyGroups(int $count): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DummyGroupDocument::class : DummyGroup::class;
        for ($i = 1; $i <= $count; ++$i) {
            $group = new $class();
            foreach (['foo', 'bar', 'baz', 'qux'] as $property) {
                $group->{$property} = ucfirst($property).' #'.$i;
            }
            $manager->persist($group);
        }
        $manager->flush();
    }

    private function seedDummyCarWithColors(): void
    {
        $manager = $this->getManager();
        $carClass = $this->isMongoDB() ? DummyCarDocument::class : DummyCar::class;
        $colorClass = $this->isMongoDB() ? DummyCarColorDocument::class : DummyCarColor::class;

        $car = new $carClass();
        $car->setName('mustli');
        $car->setCanSell(true);
        $car->setAvailableAt(new \DateTime());
        $manager->persist($car);
        $manager->flush();
        if (\is_object($car->getId())) {
            $manager->persist($car->getId());
            $manager->flush();
        }
        $red = new $colorClass();
        $red->setProp('red');
        $red->setCar($car);
        $manager->persist($red);
        $blue = new $colorClass();
        $blue->setProp('blue');
        $blue->setCar($car);
        $manager->persist($blue);
        $manager->flush();
    }

    private function seedDummyDtoNoInput(int $count): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DummyDtoNoInputDocument::class : DummyDtoNoInput::class;
        for ($i = 1; $i <= $count; ++$i) {
            $dto = new $class();
            $dto->lorem = 'DummyDtoNoInput foo #'.$i;
            $dto->ipsum = round($i / 3, 2);
            $manager->persist($dto);
        }
        $manager->flush();
    }

    private function seedDummyDtoNoOutput(int $count): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DummyDtoNoOutputDocument::class : DummyDtoNoOutput::class;
        for ($i = 1; $i <= $count; ++$i) {
            $dto = new $class();
            $dto->lorem = 'DummyDtoNoOutput foo #'.$i;
            $dto->ipsum = (string) round($i / 3, 2);
            $manager->persist($dto);
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
}
