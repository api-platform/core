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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCustomMutation as DummyCustomMutationDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyGroup as DummyGroupDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Foo as FooDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FooDummy as FooDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Person as PersonDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\WritableId as WritableIdDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6354\ActivityLog;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeLabel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomMutation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FooDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FooEmbeddable;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FourthLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VideoGame;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\WritableId;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GamePlayMode;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\MediaObject;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MutationTest extends ApiTestCase
{
    use GraphQlTestTrait;
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private const FIXTURES_DIR = __DIR__.'/../../../features/files';

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            Foo::class,
            Dummy::class,
            RelatedDummy::class,
            Person::class,
            FooDummy::class,
            FooEmbeddable::class,
            CompositeRelation::class,
            CompositeItem::class,
            CompositeLabel::class,
            WritableId::class,
            DummyGroup::class,
            DummyCustomMutation::class,
            ActivityLog::class,
            GamePlayMode::class,
            VideoGame::class,
            ThirdLevel::class,
            FourthLevel::class,
            DummyFriend::class,
            RelatedToDummyFriend::class,
            MediaObject::class,
        ];
    }

    public function testCreateItem(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? FooDocument::class : Foo::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createFoo(input: {name: "A new one", bar: "new", clientMutationId: "myId"}) {
                foo { id _id __typename name bar }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['createFoo'];
        $this->assertSame('/foos/1', $data['foo']['id']);
        $this->assertSame(1, $data['foo']['_id']);
        $this->assertSame('Foo', $data['foo']['__typename']);
        $this->assertSame('A new one', $data['foo']['name']);
        $this->assertSame('new', $data['foo']['bar']);
        $this->assertSame('myId', $data['clientMutationId']);
    }

    public function testCreateItemWithoutClientMutationId(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? FooDocument::class : Foo::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createFoo(input: {name: "Created without mutation id", bar: "works"}) {
                foo { id name bar }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['createFoo']['foo'];
        $this->assertSame('/foos/1', $data['id']);
        $this->assertSame('Created without mutation id', $data['name']);
        $this->assertSame('works', $data['bar']);
    }

    public function testCreateItemWithRelationToExisting(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyDocument::class : Dummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
        ]);
        $this->seedDummiesWithRelatedDummy(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createDummy(input: {name: "A dummy", foo: [], relatedDummy: "/related_dummies/1", name_converted: "Converted" clientMutationId: "myId"}) {
                dummy {
                  id
                  name
                  foo
                  relatedDummy { name __typename }
                  name_converted
                }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['createDummy'];
        $this->assertSame('/dummies/2', $d['dummy']['id']);
        $this->assertSame('A dummy', $d['dummy']['name']);
        $this->assertCount(0, $d['dummy']['foo']);
        $this->assertSame('RelatedDummy #1', $d['dummy']['relatedDummy']['name']);
        $this->assertSame('RelatedDummy', $d['dummy']['relatedDummy']['__typename']);
        $this->assertSame('Converted', $d['dummy']['name_converted']);
        $this->assertSame('myId', $d['clientMutationId']);
    }

    public function testCreateItemWithIterableField(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyDocument::class : Dummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
        ]);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createDummy(input: {name: "A dummy", foo: [], jsonData: {bar:{baz:3,qux:[7.6,false,null]}}, arrayData: ["bar", "baz"], clientMutationId: "myId"}) {
                dummy {
                  id name foo jsonData arrayData
                }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['createDummy'];
        $this->assertSame('/dummies/1', $d['dummy']['id']);
        $this->assertSame('A dummy', $d['dummy']['name']);
        $this->assertSame(3, $d['dummy']['jsonData']['bar']['baz']);
        $this->assertSame(7.6, $d['dummy']['jsonData']['bar']['qux'][0]);
        $this->assertFalse($d['dummy']['jsonData']['bar']['qux'][1]);
        $this->assertNull($d['dummy']['jsonData']['bar']['qux'][2]);
        $this->assertSame('baz', $d['dummy']['arrayData'][1]);
    }

    public function testCreateItemWithEnum(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? PersonDocument::class : Person::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createPerson(input: {name: "Mob", genderType: FEMALE}) {
                person { id name genderType }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $p = $response->toArray()['data']['createPerson']['person'];
        $this->assertSame('/people/1', $p['id']);
        $this->assertSame('Mob', $p['name']);
        $this->assertSame('FEMALE', $p['genderType']);
    }

    public function testCreateItemWithEnumCollection(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Enum collection scenario @!mongodb');
        }
        $this->recreateSchema([Person::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createPerson(input: {name: "Harry", academicGrades: [BACHELOR, MASTER]}) {
                person { id name genderType academicGrades }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $p = $response->toArray()['data']['createPerson']['person'];
        $this->assertSame('/people/1', $p['id']);
        $this->assertSame('Harry', $p['name']);
        $this->assertCount(2, $p['academicGrades']);
        $this->assertSame('BACHELOR', $p['academicGrades'][0]);
        $this->assertSame('MASTER', $p['academicGrades'][1]);
    }

    public function testDeleteItem(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? FooDocument::class : Foo::class]);
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? FooDocument::class : Foo::class;
        $foo = new $class();
        $foo->setName('Existing');
        $foo->setBar('value');
        $manager->persist($foo);
        $manager->flush();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              deleteFoo(input: {id: "/foos/1", clientMutationId: "anotherId"}) {
                foo { id }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['deleteFoo'];
        $this->assertSame('/foos/1', $data['foo']['id']);
        $this->assertSame('anotherId', $data['clientMutationId']);
    }

    public function testDeleteWithWrongResourceTypeYieldsError(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? FooDocument::class : Foo::class,
            $this->isMongoDB() ? DummyDocument::class : Dummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
        ]);
        $this->seedDummiesWithRelatedDummy(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              deleteFoo(input: {id: "/dummies/1", clientMutationId: "myId"}) {
                foo { id }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            'Item "/dummies/1" did not match expected type "Foo".',
            $response->toArray(false)['errors'][0]['message'],
        );
    }

    public function testDeleteItemWithCompositeIdentifiers(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Composite identifiers @!mongodb');
        }
        $this->recreateSchema([CompositeRelation::class, CompositeItem::class, CompositeLabel::class]);
        $this->seedCompositeIdentifierObjects();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              deleteCompositeRelation(input: {id: "/composite_relations/compositeItem=1;compositeLabel=1", clientMutationId: "myId"}) {
                compositeRelation { id }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['deleteCompositeRelation'];
        $this->assertSame('/composite_relations/compositeItem=1;compositeLabel=1', $data['compositeRelation']['id']);
        $this->assertSame('myId', $data['clientMutationId']);
    }

    public function testModifyItem(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyDocument::class : Dummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
        ]);
        $this->seedDummiesWithRelatedDummy(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateDummy(input: {id: "/dummies/1", description: "Modified description.", dummyDate: "2018-06-05T00:00:00+00:00", clientMutationId: "myId"}) {
                dummy { id name description dummyDate }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['updateDummy'];
        $this->assertSame('/dummies/1', $d['dummy']['id']);
        $this->assertSame('Dummy #1', $d['dummy']['name']);
        $this->assertSame('Modified description.', $d['dummy']['description']);
        $this->assertSame('2018-06-05', $d['dummy']['dummyDate']);
        $this->assertSame('myId', $d['clientMutationId']);
    }

    public function testModifyItemWithEmbeddedObject(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Embedded object scenario @!mongodb');
        }
        $this->recreateSchema([Dummy::class, FooDummy::class]);
        $this->seedFooDummyWithEmbeddable();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateFooDummy(input: {id: "/foo_dummies/1", name: "modifiedName", embeddedFoo: {dummyName: "Embedded name"}, clientMutationId: "myId"}) {
                fooDummy {
                  id
                  name
                  embeddedFoo { dummyName }
                }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['updateFooDummy'];
        $this->assertSame('modifiedName', $d['fooDummy']['name']);
        $this->assertSame('Embedded name', $d['fooDummy']['embeddedFoo']['dummyName']);
        $this->assertSame('myId', $d['clientMutationId']);
    }

    public function testModifyNonWritablePropertyRejected(): void
    {
        $this->recreateSchema([Dummy::class, FooDummy::class]);
        $this->seedFooDummyWithEmbeddable();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateFooDummy(input: {id: "/foo_dummies/1", name: "modifiedName", nonWritableProp: "written", embeddedFoo: {dummyName: "Embedded name"}, clientMutationId: "myId"}) {
                fooDummy { id name }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesRegularExpression(
            '/^Field "nonWritableProp" is not defined by type "?updateFooDummyInput"?\.$/',
            $response->toArray(false)['errors'][0]['message'],
        );
    }

    public function testModifyNonWritableEmbeddedPropertyRejected(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Embedded object scenario @!mongodb');
        }
        $this->recreateSchema([Dummy::class, FooDummy::class]);
        $this->seedFooDummyWithEmbeddable();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateFooDummy(input: {id: "/foo_dummies/1", name: "modifiedName", embeddedFoo: {dummyName: "Embedded name", nonWritableProp: "written"}, clientMutationId: "myId"}) {
                fooDummy { id name }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesRegularExpression(
            '/^Field "nonWritableProp" is not defined by type "?FooEmbeddableNestedInput"?\.$/',
            $response->toArray(false)['errors'][0]['message'],
        );
    }

    public function testModifyItemWithCompositeIdentifiers(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Composite identifiers @!mongodb');
        }
        $this->recreateSchema([CompositeRelation::class, CompositeItem::class, CompositeLabel::class]);
        $this->seedCompositeIdentifierObjects();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateCompositeRelation(input: {id: "/composite_relations/compositeItem=1;compositeLabel=2", value: "Modified value.", clientMutationId: "myId"}) {
                compositeRelation { id value }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['updateCompositeRelation'];
        $this->assertSame('/composite_relations/compositeItem=1;compositeLabel=2', $d['compositeRelation']['id']);
        $this->assertSame('Modified value.', $d['compositeRelation']['value']);
        $this->assertSame('myId', $d['clientMutationId']);
    }

    public function testCreateWithCustomUuid(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? WritableIdDocument::class : WritableId::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createWritableId(input: {_id: "c6b722fe-0331-48c4-a214-f81f9f1ca082", name: "Foo", clientMutationId: "m"}) {
                writableId { id _id name }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['createWritableId'];
        $this->assertSame('/writable_ids/c6b722fe-0331-48c4-a214-f81f9f1ca082', $d['writableId']['id']);
        $this->assertSame('c6b722fe-0331-48c4-a214-f81f9f1ca082', $d['writableId']['_id']);
        $this->assertSame('Foo', $d['writableId']['name']);
        $this->assertSame('m', $d['clientMutationId']);
    }

    public function testUpdateWithCustomUuid(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('WritableId update @!mongodb');
        }
        $this->recreateSchema([WritableId::class]);
        $manager = $this->getManager();
        $w = new WritableId();
        $w->id = 'c6b722fe-0331-48c4-a214-f81f9f1ca082';
        $w->name = 'Foo';
        $manager->persist($w);
        $manager->flush();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateWritableId(input: {id: "/writable_ids/c6b722fe-0331-48c4-a214-f81f9f1ca082", _id: "f8a708b2-310f-416c-9aef-b1b5719dfa47", name: "Foo", clientMutationId: "m"}) {
                writableId { id _id name }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['updateWritableId'];
        $this->assertSame('/writable_ids/f8a708b2-310f-416c-9aef-b1b5719dfa47', $d['writableId']['id']);
        $this->assertSame('f8a708b2-310f-416c-9aef-b1b5719dfa47', $d['writableId']['_id']);
        $this->assertSame('Foo', $d['writableId']['name']);
    }

    public function testUseSerializationGroups(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyGroupDocument::class : DummyGroup::class]);
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DummyGroupDocument::class : DummyGroup::class;
        $g = new $class();
        foreach (['foo', 'bar', 'baz', 'qux'] as $p) {
            $g->{$p} = ucfirst($p).' #1';
        }
        $manager->persist($g);
        $manager->flush();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createDummyGroup(input: {bar: "Bar", baz: "Baz", clientMutationId: "myId"}) {
                dummyGroup { id bar __typename }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['createDummyGroup'];
        $this->assertSame('/dummy_groups/2', $d['dummyGroup']['id']);
        $this->assertSame('Bar', $d['dummyGroup']['bar']);
        $this->assertSame('createDummyGroupPayloadData', $d['dummyGroup']['__typename']);
        $this->assertSame('myId', $d['clientMutationId']);
    }

    public function testTriggerValidationError(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyDocument::class : Dummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
        ]);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createDummy(input: {name: "", foo: [], clientMutationId: "myId"}) {
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertSame('422', (string) $data['errors'][0]['extensions']['status']);
        $this->assertSame('name: This value should not be blank.', $data['errors'][0]['message']);
        $this->assertArrayHasKey('violations', $data['errors'][0]['extensions']);
        $this->assertSame('name', $data['errors'][0]['extensions']['violations'][0]['path']);
        $this->assertSame('This value should not be blank.', $data['errors'][0]['extensions']['violations'][0]['message']);
    }

    public function testCustomMutation(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomMutationDocument::class : DummyCustomMutation::class]);
        $this->seedDummyCustomMutation(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              sumDummyCustomMutation(input: {id: "/dummy_custom_mutations/1", operandB: 5}) {
                dummyCustomMutation { id result }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            '8',
            (string) $response->toArray()['data']['sumDummyCustomMutation']['dummyCustomMutation']['result'],
        );
    }

    public function testCustomMutationNotPersisted(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomMutationDocument::class : DummyCustomMutation::class]);
        $this->seedDummyCustomMutation(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              sumNotPersistedDummyCustomMutation(input: {id: "/dummy_custom_mutations/1", operandB: 5}) {
                dummyCustomMutation { id result }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertNull($response->toArray()['data']['sumNotPersistedDummyCustomMutation']['dummyCustomMutation']);
    }

    public function testCustomMutationNoWriteCustomResult(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomMutationDocument::class : DummyCustomMutation::class]);
        $this->seedDummyCustomMutation(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              sumNoWriteCustomResultDummyCustomMutation(input: {id: "/dummy_custom_mutations/1", operandB: 5}) {
                dummyCustomMutation { id result }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            '1234',
            (string) $response->toArray()['data']['sumNoWriteCustomResultDummyCustomMutation']['dummyCustomMutation']['result'],
        );
    }

    public function testCustomMutationOnlyPersist(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomMutationDocument::class : DummyCustomMutation::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              sumOnlyPersistDummyCustomMutation(input: {id: "/dummy_custom_mutations/1", operandB: 5}) {
                dummyCustomMutation { id result }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertNull($response->toArray()['data']['sumOnlyPersistDummyCustomMutation']['dummyCustomMutation']);
    }

    public function testCustomMutationCustomArguments(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DummyCustomMutationDocument::class : DummyCustomMutation::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              testCustomArgumentsDummyCustomMutation(input: {operandC: 18, clientMutationId: "myId"}) {
                dummyCustomMutation { result }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $d = $response->toArray()['data']['testCustomArgumentsDummyCustomMutation'];
        $this->assertSame('18', (string) $d['dummyCustomMutation']['result']);
        $this->assertSame('myId', $d['clientMutationId']);
    }

    public function testCreateItemWithEnumAsResource(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('VideoGame ORM-only.');
        }

        $this->recreateSchema([VideoGame::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              gamePlayModes { id name }
              gamePlayMode(id: "/game_play_modes/SINGLE_PLAYER") { name }
            }
            QUERY);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data'];
        $this->assertCount(3, $data['gamePlayModes']);
        $this->assertSame('/game_play_modes/SINGLE_PLAYER', $data['gamePlayModes'][2]['id']);
        $this->assertSame('SINGLE_PLAYER', $data['gamePlayModes'][2]['name']);
        $this->assertSame('SINGLE_PLAYER', $data['gamePlayMode']['name']);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createVideoGame(input: {name: "Baten Kaitos", playMode: "/game_play_modes/SINGLE_PLAYER"}) {
                videoGame { id name playMode { id name } }
              }
            }
            QUERY);
        $this->assertResponseIsSuccessful();
        $vg = $response->toArray()['data']['createVideoGame']['videoGame'];
        $this->assertSame('/video_games/1', $vg['id']);
        $this->assertSame('Baten Kaitos', $vg['name']);
        $this->assertSame('/game_play_modes/SINGLE_PLAYER', $vg['playMode']['id']);
        $this->assertSame('SINGLE_PLAYER', $vg['playMode']['name']);
    }

    public function testDeleteInvalidItem(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ActivityLog @!mongodb.');
        }

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              deleteActivityLog(input: {id: "/activity_logs/1"}) {
                activityLog { id }
              }
            }
            QUERY);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayNotHasKey('errors', $data);
        $this->assertArrayHasKey('activityLog', $data['data']['deleteActivityLog']);
    }

    public function testUploadFileWithCustomMutation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MediaObject @!mongodb.');
        }

        $file = new UploadedFile(self::FIXTURES_DIR.'/test.gif', 'test.gif', null, \UPLOAD_ERR_OK, true);
        $response = $this->executeGraphQlMultipart(
            '{"query": "mutation($file: Upload!) { uploadMediaObject(input: {file: $file}) { mediaObject { id contentUrl } } }", "variables": {"file": null}}',
            '{"file": ["variables.file"]}',
            ['file' => $file],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame('test.gif', $response->toArray()['data']['uploadMediaObject']['mediaObject']['contentUrl']);
    }

    public function testUploadMultipleFilesWithCustomMutation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MediaObject @!mongodb.');
        }

        $files = [
            '0' => new UploadedFile(self::FIXTURES_DIR.'/test.gif', 'test.gif', null, \UPLOAD_ERR_OK, true),
            '1' => new UploadedFile(self::FIXTURES_DIR.'/test.gif', 'test.gif', null, \UPLOAD_ERR_OK, true),
            '2' => new UploadedFile(self::FIXTURES_DIR.'/test.gif', 'test.gif', null, \UPLOAD_ERR_OK, true),
        ];
        $response = $this->executeGraphQlMultipart(
            '{"query": "mutation($files: [Upload!]!) { uploadMultipleMediaObject(input: {files: $files}) { mediaObject { id contentUrl } } }", "variables": {"files": [null, null, null]}}',
            '{"0": ["variables.files.0"], "1": ["variables.files.1"], "2": ["variables.files.2"]}',
            $files,
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame('test.gif', $response->toArray()['data']['uploadMultipleMediaObject']['mediaObject']['contentUrl']);
    }

    public function testUseSerializationGroupsWithRelations(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('FourthLevel + RelatedToDummyFriend @!mongodb.');
        }

        $this->recreateSchema([
            Dummy::class, RelatedDummy::class, ThirdLevel::class, FourthLevel::class,
            DummyFriend::class, RelatedToDummyFriend::class,
        ]);
        $this->seedDummyWithRelatedDummyAndThirdLevel();
        $this->seedRelatedDummyWithFriends(2);
        $this->seedDummyWithFourthLevelRelation();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateRelatedDummy(input: {
                id: "/related_dummies/2",
                symfony: "laravel",
                thirdLevel: { fourthLevel: "/fourth_levels/1" }
              }) {
                relatedDummy {
                  id symfony
                  thirdLevel { id fourthLevel { id __typename } __typename }
                  relatedToDummyFriend {
                    edges { node { name } }
                    __typename
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $rel = $response->toArray()['data']['updateRelatedDummy']['relatedDummy'];
        $this->assertSame('/related_dummies/2', $rel['id']);
        $this->assertSame('laravel', $rel['symfony']);
        $this->assertSame('/third_levels/3', $rel['thirdLevel']['id']);
        $this->assertSame('updateThirdLevelNestedPayload', $rel['thirdLevel']['__typename']);
        $this->assertSame('/fourth_levels/1', $rel['thirdLevel']['fourthLevel']['id']);
        $this->assertSame('updateFourthLevelNestedPayload', $rel['thirdLevel']['fourthLevel']['__typename']);
        $this->assertSame('updateRelatedToDummyFriendNestedPayloadCursorConnection', $rel['relatedToDummyFriend']['__typename']);
        $this->assertSame('Relation-1', $rel['relatedToDummyFriend']['edges'][0]['node']['name']);
        $this->assertSame('Relation-2', $rel['relatedToDummyFriend']['edges'][1]['node']['name']);
    }

    public function testMutationRunsBeforeValidation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ActivityLog @!mongodb.');
        }

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createActivityLog(input: {name: ""}) {
                activityLog { name }
              }
            }
            QUERY);
        $this->assertResponseIsSuccessful();
        $this->assertSame('hi', $response->toArray()['data']['createActivityLog']['activityLog']['name']);
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
            $dummy->setRelatedDummy($related);

            $manager->persist($related);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedFooDummyWithEmbeddable(): void
    {
        $manager = $this->getManager();
        $dummyClass = $this->isMongoDB() ? DummyDocument::class : Dummy::class;
        $fooClass = $this->isMongoDB() ? FooDummyDocument::class : FooDummy::class;

        $dummy = new $dummyClass();
        $dummy->setName('Lorem');

        $foo = new $fooClass();
        $foo->setName('Hawsepipe');

        $embedded = new FooEmbeddable();
        $embedded->setDummyName('embeddedHawsepipe');
        $foo->setEmbeddedFoo($embedded);
        $foo->setDummy($dummy);

        $manager->persist($foo);
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

    private function seedDummyCustomMutation(int $count): void
    {
        $manager = $this->getManager();
        $class = $this->isMongoDB() ? DummyCustomMutationDocument::class : DummyCustomMutation::class;
        for ($i = 1; $i <= $count; ++$i) {
            $m = new $class();
            $m->setOperandA(3);
            $manager->persist($m);
        }
        $manager->flush();
    }

    private function seedDummyWithRelatedDummyAndThirdLevel(): void
    {
        $manager = $this->getManager();
        $thirdLevel = new ThirdLevel();
        $relatedDummy = new RelatedDummy();
        $relatedDummy->setName('RelatedDummy #1');
        $relatedDummy->setThirdLevel($thirdLevel);
        $dummy = new Dummy();
        $dummy->setName('Dummy #1');
        $dummy->setAlias('Alias #0');
        $dummy->setRelatedDummy($relatedDummy);
        $manager->persist($thirdLevel);
        $manager->persist($relatedDummy);
        $manager->persist($dummy);
        $manager->flush();
    }

    private function seedRelatedDummyWithFriends(int $nb): void
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

        $other = new RelatedDummy();
        $other->setName('RelatedDummy without friends');
        $manager->persist($other);
        $manager->flush();
        $manager->clear();
    }

    private function seedDummyWithFourthLevelRelation(): void
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
    }
}
