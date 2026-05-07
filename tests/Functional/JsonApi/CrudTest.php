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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\CrudDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\CrudRelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\CrudRelationEmbedder;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\CrudThirdLevel;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CrudTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            CrudThirdLevel::class,
            CrudRelatedDummy::class,
            CrudDummy::class,
            CrudRelationEmbedder::class,
        ];
    }

    public function testCreateThirdLevel(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_third_levels', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiThirdLevel',
                    'attributes' => ['level' => 3],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertNotEmpty($body['data']['id']);
        $this->assertSame('JsonApiThirdLevel', $body['data']['type']);
    }

    public function testGetThirdLevelCollection(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_third_levels', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertCount(1, $body['data']);
    }

    public function testGetThirdLevelItem(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_third_levels/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/jsonapi_third_levels/1', $body['data']['id']);
        $this->assertSame('JsonApiThirdLevel', $body['data']['type']);
    }

    public function testCreateRelatedDummyWithThirdLevel(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_crud_related_dummies', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiCrudRelatedDummy',
                    'attributes' => ['name' => 'John Doe', 'age' => 23],
                    'relationships' => [
                        'thirdLevel' => [
                            'data' => ['type' => 'JsonApiThirdLevel', 'id' => '/jsonapi_third_levels/1'],
                        ],
                    ],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertNotEmpty($body['data']['id']);
        $this->assertSame('John Doe', $body['data']['attributes']['name']);
        $this->assertSame(23, $body['data']['attributes']['age']);
    }

    public function testCreateRelatedDummyWithEmptyThirdLevel(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_crud_related_dummies', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiCrudRelatedDummy',
                    'attributes' => ['name' => 'John Doe'],
                    'relationships' => [
                        'thirdLevel' => ['data' => null],
                    ],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testGetRelatedDummyCollectionExposesRelationships(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_crud_related_dummies', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame(
            '/jsonapi_third_levels/1',
            $body['data'][0]['relationships']['thirdLevel']['data']['id'],
        );
    }

    public function testGetRelatedDummyItemFullBody(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_crud_related_dummies/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'data' => [
                'id' => '/jsonapi_crud_related_dummies/1',
                'type' => 'JsonApiCrudRelatedDummy',
                'attributes' => [
                    'name' => 'John Doe',
                    'age' => 23,
                ],
                'relationships' => [
                    'thirdLevel' => [
                        'data' => ['type' => 'JsonApiThirdLevel', 'id' => '/jsonapi_third_levels/1'],
                    ],
                ],
            ],
        ]);
    }

    public function testPatchRelatedDummyName(): void
    {
        $response = self::createClient()->request('PATCH', '/jsonapi_crud_related_dummies/1', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiCrudRelatedDummy',
                    'attributes' => ['name' => 'Jane Doe'],
                ],
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('Jane Doe', $body['data']['attributes']['name']);
        $this->assertSame(23, $body['data']['attributes']['age']);
    }

    public function testCreateDummyWithRelations(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_crud_dummies', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiCrudDummy',
                    'attributes' => [
                        'name' => 'Dummy with relations',
                        'dummyDate' => '2015-03-01T10:00:00+00:00',
                    ],
                    'relationships' => [
                        'relatedDummy' => [
                            'data' => ['type' => 'JsonApiCrudRelatedDummy', 'id' => '/jsonapi_crud_related_dummies/2'],
                        ],
                        'relatedDummies' => [
                            'data' => [
                                ['type' => 'JsonApiCrudRelatedDummy', 'id' => '/jsonapi_crud_related_dummies/1'],
                                ['type' => 'JsonApiCrudRelatedDummy', 'id' => '/jsonapi_crud_related_dummies/2'],
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
            '/jsonapi_crud_related_dummies/2',
            $body['data']['relationships']['relatedDummy']['data']['id'],
        );
    }

    public function testPatchDummyManyToMany(): void
    {
        $response = self::createClient()->request('PATCH', '/jsonapi_crud_dummies/1', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiCrudDummy',
                    'relationships' => [
                        'relatedDummy' => [
                            'data' => ['type' => 'JsonApiCrudRelatedDummy', 'id' => '/jsonapi_crud_related_dummies/1'],
                        ],
                        'relatedDummies' => [
                            'data' => [
                                ['type' => 'JsonApiCrudRelatedDummy', 'id' => '/jsonapi_crud_related_dummies/2'],
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
            '/jsonapi_crud_related_dummies/1',
            $body['data']['relationships']['relatedDummy']['data']['id'],
        );
    }

    public function testCreateRelationEmbedder(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_crud_relation_embedders', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiCrudRelationEmbedder',
                    'relationships' => [
                        'related' => [
                            'data' => ['type' => 'JsonApiCrudRelatedDummy', 'id' => '/jsonapi_crud_related_dummies/1'],
                        ],
                    ],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertNotEmpty($body['data']['id']);
        $this->assertSame('Krondstadt', $body['data']['attributes']['krondstadt']);
        $this->assertSame(
            '/jsonapi_crud_related_dummies/1',
            $body['data']['relationships']['related']['data']['id'],
        );
    }
}
