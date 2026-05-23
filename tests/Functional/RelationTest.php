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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Address;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Customer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Order;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PersonToPet;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Pet;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class RelationTest extends ApiTestCase
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
            ThirdLevel::class,
            DummyFriend::class,
            RelatedDummy::class,
            RelatedToDummyFriend::class,
            RelationEmbedder::class,
            Dummy::class,
            Order::class,
            Customer::class,
            Address::class,
            Person::class,
            Pet::class,
        ];
    }

    private function seedBasics(): void
    {
        $client = self::createClient();
        $headers = ['Content-Type' => 'application/ld+json'];
        $client->request('POST', '/third_levels', ['headers' => $headers, 'json' => ['level' => 3]]);
        $client->request('POST', '/dummy_friends', ['headers' => $headers, 'json' => ['name' => 'Zoidberg']]);
        $client->request('POST', '/related_dummies', ['headers' => $headers, 'json' => ['thirdLevel' => '/third_levels/1']]);
    }

    public function testCreateThirdLevel(): void
    {
        $this->recreateSchema([ThirdLevel::class]);

        self::createClient()->request('POST', '/third_levels', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['level' => 3],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            '@context' => '/contexts/ThirdLevel',
            '@id' => '/third_levels/1',
            '@type' => 'ThirdLevel',
            'fourthLevel' => null,
            'badFourthLevel' => null,
            'id' => 1,
            'level' => 3,
            'test' => true,
            'relatedDummies' => [],
        ]);
    }

    public function testCreateDummyFriend(): void
    {
        $this->recreateSchema([DummyFriend::class]);

        self::createClient()->request('POST', '/dummy_friends', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Zoidberg'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            '@context' => '/contexts/DummyFriend',
            '@id' => '/dummy_friends/1',
            '@type' => 'DummyFriend',
            'id' => 1,
            'name' => 'Zoidberg',
        ]);
    }

    public function testCreateRelatedDummyWithThirdLevel(): void
    {
        $this->recreateSchema([ThirdLevel::class, RelatedDummy::class]);
        self::createClient()->request('POST', '/third_levels', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['level' => 3],
        ]);

        self::createClient()->request('POST', '/related_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['thirdLevel' => '/third_levels/1'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/RelatedDummy',
            '@id' => '/related_dummies/1',
            '@type' => 'https://schema.org/Product',
            'id' => 1,
            'symfony' => 'symfony',
            'thirdLevel' => [
                '@id' => '/third_levels/1',
                '@type' => 'ThirdLevel',
                'fourthLevel' => null,
            ],
        ]);
    }

    public function testCreateFriendRelationship(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([ThirdLevel::class, DummyFriend::class, RelatedDummy::class, RelatedToDummyFriend::class]);
        $this->seedBasics();

        self::createClient()->request('POST', '/related_to_dummy_friends', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Friends relation',
                'dummyFriend' => '/dummy_friends/1',
                'relatedDummy' => '/related_dummies/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            '@context' => '/contexts/RelatedToDummyFriend',
            '@id' => '/related_to_dummy_friends/dummyFriend=1;relatedDummy=1',
            '@type' => 'RelatedToDummyFriend',
            'name' => 'Friends relation',
            'description' => null,
            'dummyFriend' => [
                '@id' => '/dummy_friends/1',
                '@type' => 'DummyFriend',
                'name' => 'Zoidberg',
            ],
        ]);
    }

    public function testGetFriendRelationship(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([ThirdLevel::class, DummyFriend::class, RelatedDummy::class, RelatedToDummyFriend::class]);
        $this->seedBasics();
        self::createClient()->request('POST', '/related_to_dummy_friends', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Friends relation',
                'dummyFriend' => '/dummy_friends/1',
                'relatedDummy' => '/related_dummies/1',
            ],
        ]);

        self::createClient()->request('GET', '/related_to_dummy_friends/dummyFriend=1;relatedDummy=1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/RelatedToDummyFriend',
            '@id' => '/related_to_dummy_friends/dummyFriend=1;relatedDummy=1',
            '@type' => 'RelatedToDummyFriend',
            'name' => 'Friends relation',
            'description' => null,
            'dummyFriend' => [
                '@id' => '/dummy_friends/1',
                '@type' => 'DummyFriend',
                'name' => 'Zoidberg',
            ],
        ]);
    }

    public function testCreateDummyWithRelations(): void
    {
        $this->recreateSchema([ThirdLevel::class, RelatedDummy::class, Dummy::class]);
        self::createClient()->request('POST', '/third_levels', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['level' => 3],
        ]);
        self::createClient()->request('POST', '/related_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['thirdLevel' => '/third_levels/1'],
        ]);

        self::createClient()->request('POST', '/dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Dummy with relations',
                'relatedDummy' => 'http://example.com/related_dummies/1',
                'relatedDummies' => ['/related_dummies/1'],
                'name_converted' => null,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@id' => '/dummies/1',
            '@type' => 'Dummy',
            'name' => 'Dummy with relations',
            'relatedDummy' => '/related_dummies/1',
            'relatedDummies' => ['/related_dummies/1'],
        ]);
    }

    public function testFilterOnRelation(): void
    {
        $this->testCreateDummyWithRelations();

        $response = self::createClient()->request('GET', '/dummies?relatedDummy=%2Frelated_dummies%2F1');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame('hydra:Collection', $data['@type']);
        $this->assertSame(1, $data['hydra:totalItems']);
        $this->assertSame('/dummies/1', $data['hydra:member'][0]['@id']);
    }

    public function testFilterOnToManyRelation(): void
    {
        $this->testCreateDummyWithRelations();

        $response = self::createClient()->request('GET', '/dummies?relatedDummies[]=%2Frelated_dummies%2F1');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame(1, $data['hydra:totalItems']);
        $this->assertSame('/dummies/1', $data['hydra:member'][0]['@id']);
    }

    public function testEmbedRelationInParent(): void
    {
        $this->recreateSchema([ThirdLevel::class, RelatedDummy::class, RelationEmbedder::class]);
        self::createClient()->request('POST', '/third_levels', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['level' => 3],
        ]);
        self::createClient()->request('POST', '/related_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['thirdLevel' => '/third_levels/1'],
        ]);

        self::createClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['related' => '/related_dummies/1'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/RelationEmbedder',
            '@id' => '/relation_embedders/1',
            '@type' => 'RelationEmbedder',
            'krondstadt' => 'Krondstadt',
            'anotherRelated' => null,
            'related' => [
                '@id' => '/related_dummies/1',
                '@type' => 'https://schema.org/Product',
                'symfony' => 'symfony',
                'thirdLevel' => [
                    '@id' => '/third_levels/1',
                    '@type' => 'ThirdLevel',
                    'level' => 3,
                    'fourthLevel' => null,
                ],
            ],
        ]);
    }

    public function testPostWrongRelationReturns400(): void
    {
        $this->recreateSchema([ThirdLevel::class, RelatedDummy::class, RelationEmbedder::class]);

        self::createClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'anotherRelated' => [
                    '@id' => '/related_dummies/123',
                    '@type' => 'https://schema.org/Product',
                    'symfony' => 'phalcon',
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testPostRelationWithNotExistingIriReturns400(): void
    {
        $this->recreateSchema([ThirdLevel::class, RelatedDummy::class, RelationEmbedder::class]);

        self::createClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['related' => '/related_dummies/123'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testInvalidIriReturns400(): void
    {
        $this->recreateSchema([ThirdLevel::class, RelatedDummy::class, RelationEmbedder::class]);

        self::createClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['related' => 'certainly not an IRI'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains(['detail' => 'Invalid IRI "certainly not an IRI".']);
    }

    public function testInvalidTypeReturns400(): void
    {
        $this->recreateSchema([ThirdLevel::class, RelatedDummy::class, RelationEmbedder::class]);

        $response = self::createClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['related' => 8],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $linkHeader = $response->getHeaders(false)['link'][0] ?? '';
        $this->assertStringContainsString('<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"', $linkHeader);
        $data = $response->toArray(false);
        $this->assertMatchesRegularExpression(
            '/The type of the "ApiPlatform\\\\Tests\\\\Fixtures\\\\TestBundle\\\\(Document|Entity)\\\\RelatedDummy" resource must be "array" \(nested document\) or "string" \(IRI\), "integer" given\./',
            $data['detail']
        );
    }

    public function testEagerLoadOrdersAreNotDuplicated(): void
    {
        $this->recreateSchema([Order::class, Customer::class, Address::class]);

        $manager = $this->getManager();
        $customer = new Customer();
        $customer->name = 'customer_name';
        $a1 = new Address();
        $a1->name = 'foo';
        $a2 = new Address();
        $a2->name = 'bar';
        $customer->addresses->add($a1);
        $customer->addresses->add($a2);
        $manager->persist($a1);
        $manager->persist($a2);
        $manager->persist($customer);
        $manager->flush();

        $order = new Order();
        $order->customer = $customer;
        $order->recipient = $customer;
        $manager->persist($order);
        $manager->flush();

        self::createClient()->request('GET', '/orders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/Order',
            '@id' => '/orders',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/orders/1',
                '@type' => 'Order',
                'id' => 1,
                'customer' => [
                    '@id' => '/customers/1',
                    '@type' => 'Customer',
                    'id' => 1,
                    'name' => 'customer_name',
                    'addresses' => [
                        ['@id' => '/addresses/1', '@type' => 'Address', 'id' => 1, 'name' => 'foo'],
                        ['@id' => '/addresses/2', '@type' => 'Address', 'id' => 2, 'name' => 'bar'],
                    ],
                ],
                'recipient' => [
                    '@id' => '/customers/1',
                    '@type' => 'Customer',
                    'id' => 1,
                    'name' => 'customer_name',
                    'addresses' => [
                        ['@id' => '/addresses/1', '@type' => 'Address', 'id' => 1, 'name' => 'foo'],
                        ['@id' => '/addresses/2', '@type' => 'Address', 'id' => 2, 'name' => 'bar'],
                    ],
                ],
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testIssue1222PeopleWithPets(): void
    {
        $this->recreateSchema([Person::class, Pet::class, PersonToPet::class]);

        $manager = $this->getManager();
        $person = new Person();
        $person->name = 'foo';
        $manager->persist($person);
        $pet = new Pet();
        $pet->name = 'bar';
        $manager->persist($pet);
        $manager->flush();
        $personToPet = new PersonToPet();
        $personToPet->person = $person;
        $personToPet->pet = $pet;
        $manager->persist($personToPet);
        $manager->flush();
        $manager->clear();

        self::createClient()->request('GET', '/people', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@context' => '/contexts/Person',
            '@id' => '/people',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/people/1',
                '@type' => 'Person',
                'name' => 'foo',
                'pets' => [[
                    '@type' => 'PersonToPet',
                    'pet' => [
                        '@id' => '/pets/1',
                        '@type' => 'Pet',
                        'name' => 'bar',
                    ],
                ]],
            ]],
            'hydra:totalItems' => 1,
        ]);
    }
}
