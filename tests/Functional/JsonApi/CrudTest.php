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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CrudTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            ThirdLevel::class,
            RelatedDummy::class,
            Dummy::class,
            RelationEmbedder::class,
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

    public function testCreateThirdLevel(): void
    {
        $response = self::createClient()->request('POST', '/third_levels', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'third-level',
                    'attributes' => ['level' => 3],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('/third_levels/1', $body['data']['id']);
        $this->assertSame('ThirdLevel', $body['data']['type']);
    }

    public function testGetThirdLevelCollection(): void
    {
        $this->seedThirdLevel();

        $response = self::createClient()->request('GET', '/third_levels', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertCount(1, $body['data']);
    }

    public function testGetThirdLevelItem(): void
    {
        $this->seedThirdLevel();

        $response = self::createClient()->request('GET', '/third_levels/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/third_levels/1', $body['data']['id']);
    }

    public function testCreateRelatedDummyWithThirdLevelRelation(): void
    {
        $this->seedThirdLevel();

        $response = self::createClient()->request('POST', '/related_dummies', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'related-dummy',
                    'attributes' => ['name' => 'John Doe', 'age' => 23],
                    'relationships' => [
                        'thirdLevel' => [
                            'data' => ['type' => 'third-level', 'id' => '/third_levels/1'],
                        ],
                    ],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('/related_dummies/1', $body['data']['id']);
        $this->assertSame('John Doe', $body['data']['attributes']['name']);
        $this->assertSame(23, $body['data']['attributes']['age']);
    }

    public function testCreateRelatedDummyWithEmptyThirdLevel(): void
    {
        $response = self::createClient()->request('POST', '/related_dummies', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'related-dummy',
                    'attributes' => ['name' => 'John Doe'],
                    'relationships' => [
                        'thirdLevel' => ['data' => null],
                    ],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateDummyWithRelations(): void
    {
        $this->seedRelatedDummies(2);

        $response = self::createClient()->request('POST', '/dummies', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'dummy',
                    'attributes' => [
                        'name' => 'Dummy with relations',
                        'dummyDate' => '2015-03-01T10:00:00+00:00',
                    ],
                    'relationships' => [
                        'relatedDummy' => [
                            'data' => ['type' => 'related-dummy', 'id' => '/related_dummies/2'],
                        ],
                        'relatedDummies' => [
                            'data' => [
                                ['type' => 'related-dummy', 'id' => '/related_dummies/1'],
                                ['type' => 'related-dummy', 'id' => '/related_dummies/2'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertCount(2, $body['data']['relationships']['relatedDummies']['data']);
        $this->assertSame(
            '/related_dummies/2',
            $body['data']['relationships']['relatedDummy']['data']['id'],
        );
    }

    public function testPatchDummyManyToMany(): void
    {
        $this->seedRelatedDummies(2);
        $this->seedDummyWithTwoRelatedDummies();

        $response = self::createClient()->request('PATCH', '/dummies/1', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'dummy',
                    'relationships' => [
                        'relatedDummy' => [
                            'data' => ['type' => 'related-dummy', 'id' => '/related_dummies/1'],
                        ],
                        'relatedDummies' => [
                            'data' => [
                                ['type' => 'related-dummy', 'id' => '/related_dummies/2'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(1, $body['data']['relationships']['relatedDummies']['data']);
        $this->assertSame(
            '/related_dummies/1',
            $body['data']['relationships']['relatedDummy']['data']['id'],
        );
    }

    public function testGetCollectionRelatedDummiesExposesRelationships(): void
    {
        $this->seedRelatedDummiesWithThirdLevel(1);

        $response = self::createClient()->request('GET', '/related_dummies', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame(
            '/third_levels/1',
            $body['data'][0]['relationships']['thirdLevel']['data']['id'],
        );
    }

    public function testGetRelatedDummyFullBody(): void
    {
        $this->seedRelatedDummiesWithThirdLevel(1);

        $response = self::createClient()->request('GET', '/related_dummies/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'data' => [
                'id' => '/related_dummies/1',
                'type' => 'RelatedDummy',
                'attributes' => [
                    '_id' => 1,
                    'name' => 'John Doe',
                    'symfony' => 'symfony',
                    'age' => 23,
                ],
                'relationships' => [
                    'thirdLevel' => [
                        'data' => ['type' => 'ThirdLevel', 'id' => '/third_levels/1'],
                    ],
                ],
            ],
        ]);
    }

    public function testPatchRelatedDummyName(): void
    {
        $this->seedRelatedDummiesWithThirdLevel(1);

        $response = self::createClient()->request('PATCH', '/related_dummies/1', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'related-dummy',
                    'attributes' => ['name' => 'Jane Doe'],
                ],
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('Jane Doe', $body['data']['attributes']['name']);
        $this->assertSame(23, $body['data']['attributes']['age']);
    }

    public function testCreateRelationEmbedder(): void
    {
        $this->seedRelatedDummies(1);

        $response = self::createClient()->request('POST', '/relation_embedders', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'relationships' => [
                        'related' => [
                            'data' => ['type' => 'related-dummy', 'id' => '/related_dummies/1'],
                        ],
                    ],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('Krondstadt', $body['data']['attributes']['krondstadt']);
        $this->assertSame(
            '/related_dummies/1',
            $body['data']['relationships']['related']['data']['id'],
        );
    }

    private function seedThirdLevel(): void
    {
        $manager = $this->getManager();
        $thirdLevel = new ThirdLevel();
        $thirdLevel->setLevel(3);
        $manager->persist($thirdLevel);
        $manager->flush();
        $manager->clear();
    }

    private function seedRelatedDummies(int $nb): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = new RelatedDummy();
            $relatedDummy->setName("RelatedDummy #{$i}");
            $manager->persist($relatedDummy);
        }
        $manager->flush();
        $manager->clear();
    }

    private function seedRelatedDummiesWithThirdLevel(int $nb): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $thirdLevel = new ThirdLevel();
            $relatedDummy = new RelatedDummy();
            $relatedDummy->setName('John Doe');
            $relatedDummy->setAge(23);
            $relatedDummy->thirdLevel = $thirdLevel;
            $manager->persist($thirdLevel);
            $manager->persist($relatedDummy);
        }
        $manager->flush();
        $manager->clear();
    }

    private function seedDummyWithTwoRelatedDummies(): void
    {
        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('Dummy with relations');
        $relatedDummies = $manager->getRepository(RelatedDummy::class)->findBy([], ['id' => 'ASC']);
        if (\count($relatedDummies) >= 2) {
            $dummy->setRelatedDummy($relatedDummies[1]);
            $dummy->addRelatedDummy($relatedDummies[0]);
            $dummy->addRelatedDummy($relatedDummies[1]);
        }
        $manager->persist($dummy);
        $manager->flush();
        $manager->clear();
    }
}
