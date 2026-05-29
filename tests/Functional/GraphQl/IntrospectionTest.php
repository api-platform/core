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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DeprecatedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAggregateOffer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDifferentGraphQlSerializationGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VideoGame;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VoDummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VoDummyInspection;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class IntrospectionTest extends ApiTestCase
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
            DummyProduct::class,
            DummyAggregateOffer::class,
            DummyDifferentGraphQlSerializationGroup::class,
            DummyGroup::class,
            DummyProperty::class,
            DeprecatedResource::class,
            VoDummyCar::class,
            VoDummyInspection::class,
            Person::class,
            VideoGame::class,
        ];
    }

    public function testEmptyQueryReturnsBadRequest(): void
    {
        $client = self::createClient();
        $client->request('GET', '/graphql');

        $this->assertResponseStatusCodeSame(200);
        $data = $client->getResponse()->toArray(false);
        $this->assertSame(400, $data['errors'][0]['extensions']['status']);
        $this->assertSame('GraphQL query is not valid.', $data['errors'][0]['message']);
    }

    public function testIntrospectSchema(): void
    {
        $response = $this->introspectSchema();

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('types', $data['data']['__schema']);
        $this->assertSame('Query', $data['data']['__schema']['queryType']['name']);
        $this->assertSame('Mutation', $data['data']['__schema']['mutationType']['name']);
    }

    public function testIntrospectTypes(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              type1: __type(name: "DummyProduct") {
                description,
                fields { name type { name kind ofType { name kind } } }
              }
              type2: __type(name: "DummyAggregateOfferCursorConnection") {
                description,
                fields { name type { name kind ofType { name kind } } }
              }
              type3: __type(name: "DummyAggregateOfferEdge") {
                description,
                fields { name type { name kind ofType { name kind } } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data'];

        $this->assertSame('Dummy Product.', $data['type1']['description']);
        $this->assertContainsEquals(
            ['name' => 'offers', 'type' => ['name' => 'DummyAggregateOfferCursorConnection', 'kind' => 'OBJECT', 'ofType' => null]],
            $data['type1']['fields'],
        );
        $this->assertContainsEquals(
            ['name' => 'edges', 'type' => ['name' => null, 'kind' => 'LIST', 'ofType' => ['name' => 'DummyAggregateOfferEdge', 'kind' => 'OBJECT']]],
            $data['type2']['fields'],
        );
        $this->assertContainsEquals(
            ['name' => 'node', 'type' => ['name' => 'DummyAggregateOffer', 'kind' => 'OBJECT', 'ofType' => null]],
            $data['type3']['fields'],
        );
        $this->assertContainsEquals(
            ['name' => 'cursor', 'type' => ['name' => null, 'kind' => 'NON_NULL', 'ofType' => ['name' => 'String', 'kind' => 'SCALAR']]],
            $data['type3']['fields'],
        );
    }

    public function testIntrospectTypesWithDifferentSerializationGroups(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              type1: __type(name: "DummyDifferentGraphQlSerializationGroupCollection") {
                description,
                fields { name type { name kind ofType { name kind } } }
              }
              type2: __type(name: "DummyDifferentGraphQlSerializationGroupItem") {
                description,
                fields { name type { name kind ofType { name kind } } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data'];

        $this->assertSame(
            'Dummy with different serialization groups for item_query and collection_query.',
            $data['type1']['description'],
        );
        $this->assertCount(3, $data['type1']['fields']);
        $this->assertSame('title', $data['type2']['fields'][3]['name']);
    }

    public function testIntrospectDeprecatedQueries(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              __type (name: "Query") {
                name
                fields(includeDeprecated: true) {
                  name
                  isDeprecated
                  deprecationReason
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGraphQlFieldDeprecated($data, 'deprecatedResource', 'This resource is deprecated');
        $this->assertGraphQlFieldDeprecated($data, 'deprecatedResources', 'This resource is deprecated');
    }

    public function testIntrospectDeprecatedMutations(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              __type (name: "Mutation") {
                name
                fields(includeDeprecated: true) {
                  name
                  isDeprecated
                  deprecationReason
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGraphQlFieldDeprecated($data, 'deleteDeprecatedResource', 'This resource is deprecated');
        $this->assertGraphQlFieldDeprecated($data, 'updateDeprecatedResource', 'This resource is deprecated');
        $this->assertGraphQlFieldDeprecated($data, 'createDeprecatedResource', 'This resource is deprecated');
    }

    public function testIntrospectDeprecatedField(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              __type(name: "DeprecatedResource") {
                fields(includeDeprecated: true) {
                  name
                  isDeprecated
                  deprecationReason
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertGraphQlFieldDeprecated($response->toArray(), 'deprecatedField', 'This field is deprecated');
    }

    public function testRetrieveRelayNodeInterface(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              __type(name: "Node") {
                name
                kind
                fields {
                  name
                  type {
                    kind
                    ofType { name kind }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => [
                '__type' => [
                    'name' => 'Node',
                    'kind' => 'INTERFACE',
                    'fields' => [
                        [
                            'name' => 'id',
                            'type' => ['kind' => 'NON_NULL', 'ofType' => ['name' => 'ID', 'kind' => 'SCALAR']],
                        ],
                    ],
                ],
            ],
        ], $response->toArray());
    }

    public function testRetrieveRelayNodeField(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              __schema {
                queryType {
                  fields {
                    name
                    type { name kind }
                    args { name type { kind ofType { name kind } } }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $fields = $response->toArray()['data']['__schema']['queryType']['fields'];
        $this->assertSame('node', $fields[0]['name']);
        $this->assertSame('Node', $fields[0]['type']['name']);
        $this->assertSame('INTERFACE', $fields[0]['type']['kind']);
        $this->assertSame('id', $fields[0]['args'][0]['name']);
        $this->assertSame('NON_NULL', $fields[0]['args'][0]['type']['kind']);
        $this->assertSame('ID', $fields[0]['args'][0]['type']['ofType']['name']);
        $this->assertSame('SCALAR', $fields[0]['args'][0]['type']['ofType']['kind']);
    }

    public function testIntrospectIterableFieldOnDummy(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              __type(name: "Dummy") {
                description,
                fields { name type { name kind ofType { name kind } } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertContainsEquals(
            ['name' => 'jsonData', 'type' => ['name' => 'Iterable', 'kind' => 'SCALAR', 'ofType' => null]],
            $response->toArray()['data']['__type']['fields'],
        );
    }

    public function testRetrieveDummyGroupFieldsAndMutationInputs(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              typeQuery: __type(name: "DummyGroup") {
                fields { name type { name kind ofType { name kind } } }
              }
              typeCreateInput: __type(name: "createDummyGroupInput") {
                inputFields { name type { name kind ofType { name kind } } }
              }
              typeCreatePayload: __type(name: "createDummyGroupPayload") {
                fields { name type { name kind ofType { name kind } } }
              }
              typeCreatePayloadData: __type(name: "createDummyGroupPayloadData") {
                fields { name type { name kind ofType { name kind } } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data'];

        $this->assertCount(2, $data['typeQuery']['fields']);
        $this->assertSame('id', $data['typeQuery']['fields'][0]['name']);
        $this->assertSame('foo', $data['typeQuery']['fields'][1]['name']);

        $this->assertCount(3, $data['typeCreateInput']['inputFields']);
        $this->assertSame('bar', $data['typeCreateInput']['inputFields'][0]['name']);
        $this->assertSame('baz', $data['typeCreateInput']['inputFields'][1]['name']);
        $this->assertSame('clientMutationId', $data['typeCreateInput']['inputFields'][2]['name']);

        $this->assertCount(2, $data['typeCreatePayload']['fields']);
        $this->assertSame('dummyGroup', $data['typeCreatePayload']['fields'][0]['name']);
        $this->assertSame('createDummyGroupPayloadData', $data['typeCreatePayload']['fields'][0]['type']['name']);
        $this->assertSame('clientMutationId', $data['typeCreatePayload']['fields'][1]['name']);

        $this->assertCount(2, $data['typeCreatePayloadData']['fields']);
        $this->assertSame('id', $data['typeCreatePayloadData']['fields'][0]['name']);
        $this->assertSame('bar', $data['typeCreatePayloadData']['fields'][1]['name']);
    }

    public function testRetrieveNestedMutationPayloadData(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              typeCreatePayload: __type(name: "createDummyPropertyPayload") {
                fields { name type { name kind ofType { name kind } } }
              }
              typeCreatePayloadData: __type(name: "createDummyPropertyPayloadData") {
                fields { name type { name kind ofType { name kind } } }
              }
              typeCreateNestedPayload: __type(name: "createDummyGroupNestedPayload") {
                fields { name type { name kind ofType { name kind } } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data'];

        $this->assertSame([
            ['name' => 'dummyProperty', 'type' => ['name' => 'createDummyPropertyPayloadData', 'kind' => 'OBJECT', 'ofType' => null]],
            ['name' => 'clientMutationId', 'type' => ['name' => 'String', 'kind' => 'SCALAR', 'ofType' => null]],
        ], $data['typeCreatePayload']['fields']);

        $this->assertContainsEquals(
            ['name' => 'group', 'type' => ['name' => 'createDummyGroupNestedPayload', 'kind' => 'OBJECT', 'ofType' => null]],
            $data['typeCreatePayloadData']['fields'],
        );

        $this->assertContainsEquals(
            ['name' => 'id', 'type' => ['name' => null, 'kind' => 'NON_NULL', 'ofType' => ['name' => 'ID', 'kind' => 'SCALAR']]],
            $data['typeCreateNestedPayload']['fields'],
        );
    }

    public function testRetrieveTypenameViaGraphQlQuery(): void
    {
        $resources = [
            $this->isMongoDB() ? DummyDocument::class : Dummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
        ];
        $this->recreateSchema($resources);
        $this->seedDummiesWithRelatedDummy(4);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummy: dummy(id: "/dummies/3") {
                name
                relatedDummy {
                  id
                  name
                  __typename
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $dummy = $response->toArray()['data']['dummy'];
        $this->assertSame('Dummy #3', $dummy['name']);
        $this->assertSame('RelatedDummy #3', $dummy['relatedDummy']['name']);
        $this->assertSame('RelatedDummy', $dummy['relatedDummy']['__typename']);
    }

    public function testIntrospectTypeAvailableOnlyThroughRelations(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              typeNotAvailable: __type(name: "VoDummyInspectionCursorConnection") {
                description
              }
              typeOwner: __type(name: "VoDummyCar") {
                description,
                fields { name type { name } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data'];
        $this->assertNull($data['typeNotAvailable']);
        $this->assertSame('VoDummyInspectionCursorConnection', $data['typeOwner']['fields'][1]['type']['name']);
    }

    public function testIntrospectEnum(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              person: __type(name: "Person") {
                name
                fields {
                  name
                  type {
                    name
                    description
                    enumValues { name description }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $field = $response->toArray()['data']['person']['fields'][1];
        $this->assertSame('GenderTypeEnum', $field['type']['name']);
        $this->assertSame('MALE', $field['type']['enumValues'][0]['name']);
        $this->assertSame('FEMALE', $field['type']['enumValues'][1]['name']);
        $this->assertSame('The female gender.', $field['type']['enumValues'][1]['description']);
    }

    public function testIntrospectEnumResource(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              videoGame: __type(name: "VideoGame") {
                name
                fields {
                  name
                  type { name kind ofType { name kind } }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            'GamePlayMode',
            $response->toArray()['data']['videoGame']['fields'][3]['type']['ofType']['name'],
        );
    }

    private function seedDummiesWithRelatedDummy(int $count): void
    {
        $dummyClass = $this->isMongoDB() ? DummyDocument::class : Dummy::class;
        $relatedClass = $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class;
        $manager = $this->getManager();

        for ($i = 1; $i <= $count; ++$i) {
            $related = new $relatedClass();
            $related->setName('RelatedDummy #'.$i);

            $dummy = new $dummyClass();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));
            $dummy->nameConverted = "Converted $i";
            $dummy->setRelatedDummy($related);

            $manager->persist($related);
            $manager->persist($dummy);
        }
        $manager->flush();
    }
}
