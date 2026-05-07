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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\NonRelationResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\NonResourceContainer;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\PlainObjectResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NonResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [NonResourceContainer::class, NonRelationResource::class, PlainObjectResource::class];
    }

    public function testNonResourceObjectIsEmbeddedAsRelationship(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_non_resource_containers/1?include=nested', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'data' => [
                'id' => '/jsonapi_non_resource_containers/1',
                'type' => 'JsonApiNonResourceContainer',
                'attributes' => [
                    '_id' => '1',
                    'notAResource' => ['foo' => 'f1', 'bar' => 'b1'],
                ],
                'relationships' => [
                    'nested' => [
                        'data' => [
                            'id' => '/jsonapi_non_resource_containers/1-nested',
                            'type' => 'JsonApiNonResourceContainer',
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => '/jsonapi_non_resource_containers/1-nested',
                    'type' => 'JsonApiNonResourceContainer',
                    'attributes' => [
                        '_id' => '1-nested',
                        'notAResource' => ['foo' => 'f2', 'bar' => 'b2'],
                    ],
                ],
            ],
        ]);
    }

    public function testCreateResourceWithNonResourceRelation(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_non_relation_resources', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiNonRelationResource',
                    'attributes' => ['relation' => ['foo' => 'test']],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'data' => [
                'id' => '/jsonapi_non_relation_resources/1',
                'type' => 'JsonApiNonRelationResource',
                'attributes' => [
                    '_id' => 1,
                    'relation' => ['foo' => 'test'],
                ],
            ],
        ]);
    }

    public function testCreateResourceWithStdClass(): void
    {
        $payload = json_encode([
            'fields' => [
                'title' => ['value' => ''],
                'images' => [],
                'alternativeAudio' => new \stdClass(),
                'caption' => '',
            ],
            'showCaption' => false,
            'alternativeContent' => false,
            'alternativeAudioContent' => false,
            'blockLayout' => 'default',
        ]);

        $response = self::createClient()->request('POST', '/jsonapi_plain_object_resources', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiPlainObjectResource',
                    'attributes' => ['content' => $payload],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('/jsonapi_plain_object_resources/1', $body['data']['id']);
        $this->assertSame('JsonApiPlainObjectResource', $body['data']['type']);
        $this->assertFalse($body['data']['attributes']['data']['showCaption']);
        $this->assertSame('default', $body['data']['attributes']['data']['blockLayout']);
    }
}
