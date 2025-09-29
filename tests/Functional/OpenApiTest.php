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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Crud;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\CrudOpenApiApiPlatformTag;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DummyWebhook;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6151\OverrideOpenApiResponses;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AbstractDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CircularReference;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeLabel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConcreteDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomIdentifierDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomNormalizedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomWritableIdentifierDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DeprecatedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyBoolean;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritance;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceChild;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JsonSchemaResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JsonSchemaResourceRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\NoCollectionDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OverriddenOperationDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RamseyUuidDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwnedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwningDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\User;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UuidIdentifierDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VideoGame;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\WrappedResponseEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\DummyAddress;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class OpenApiTest extends ApiTestCase
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
            Crud::class,
            CrudOpenApiApiPlatformTag::class,
            AbstractDummy::class,
            CircularReference::class,
            CompositeItem::class,
            CompositeLabel::class,
            ConcreteDummy::class,
            CustomIdentifierDummy::class,
            CustomNormalizedDummy::class,
            CustomWritableIdentifierDummy::class,
            Dummy::class,
            DummyBoolean::class,
            RelatedDummy::class,
            DummyTableInheritance::class,
            DummyTableInheritanceChild::class,
            OverriddenOperationDummy::class,
            Person::class,
            NoCollectionDummy::class,
            RelatedToDummyFriend::class,
            DummyFriend::class,
            RelationEmbedder::class,
            User::class,
            UuidIdentifierDummy::class,
            ThirdLevel::class,
            DummyCar::class,
            DummyWebhook::class,
            VideoGame::class,
            DeprecatedResource::class,
            OverrideOpenApiResponses::class,
            DummyAddress::class,
            RamseyUuidDummy::class,
            JsonSchemaResource::class,
            JsonSchemaResourceRelated::class,
            WrappedResponseEntity::class,
        ];
    }

    public function testErrorsAreDocumented(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $res = $response->toArray();
        $this->assertTrue(isset($res['paths']['/cruds/{id}']['patch']['responses']));
        $responses = $res['paths']['/cruds/{id}']['patch']['responses'];

        foreach ($responses as $status => $response) {
            if ($status < 400) {
                continue;
            }

            $this->assertArrayHasKey('application/problem+json', $response['content']);
            $this->assertArrayHasKey('application/ld+json', $response['content']);
            $this->assertArrayHasKey('application/vnd.api+json', $response['content']);

            match ($status) {
                422 => $this->assertStringStartsWith('#/components/schemas/ConstraintViolation', $response['content']['application/problem+json']['schema']['$ref']),
                default => $this->assertStringStartsWith('#/components/schemas/Error', $response['content']['application/problem+json']['schema']['$ref']),
            };
        }

        // problem detail https://datatracker.ietf.org/doc/html/rfc7807#section-3.1
        foreach (['title', 'detail', 'instance', 'type', 'status'] as $key) {
            $this->assertArrayHasKey($key, $res['components']['schemas']['Error']['properties']);
        }

        foreach (['title', 'detail', 'instance', 'type', 'status', '@id', '@type', '@context'] as $key) {
            $this->assertSame(['allOf' => [
                ['$ref' => '#/components/schemas/HydraItemBaseSchema'],
                [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'readOnly' => true,
                            'description' => 'A short, human-readable summary of the problem.',
                            'type' => [
                                0 => 'string',
                                1 => 'null',
                            ],
                        ],
                        'detail' => [
                            'readOnly' => true,
                            'description' => 'A human-readable explanation specific to this occurrence of the problem.',
                            'type' => [
                                0 => 'string',
                                1 => 'null',
                            ],
                        ],
                        'status' => [
                            'type' => 'number',
                            'examples' => [
                                0 => 404,
                            ],
                            'default' => 400,
                        ],
                        'instance' => [
                            'readOnly' => true,
                            'description' => 'A URI reference that identifies the specific occurrence of the problem. It may or may not yield further information if dereferenced.',
                            'type' => [
                                0 => 'string',
                                1 => 'null',
                            ],
                        ],
                        'type' => [
                            'readOnly' => true,
                            'description' => 'A URI reference that identifies the problem type',
                            'type' => 'string',
                        ],
                        'description' => [
                            'readOnly' => true,
                            'type' => [
                                0 => 'string',
                                1 => 'null',
                            ],
                        ],
                    ],
                ],
            ], 'description' => 'A representation of common errors.'], $res['components']['schemas']['Error.jsonld']);
        }

        foreach (['id', 'title', 'detail', 'instance', 'type', 'status', 'meta', 'source'] as $key) {
            $this->assertSame(['allOf' => [
                ['$ref' => '#/components/schemas/Error'],
                ['type' => 'object', 'properties' => [
                    'source' => [
                        'type' => 'object',
                    ],
                    'status' => [
                        'type' => 'string',
                    ],
                ]],
            ]], $res['components']['schemas']['Error.jsonapi']['properties']['errors']['items']);
        }
    }

    public function testFilterExtensionTags(): void
    {
        $response = self::createClient()->request('GET', '/docs?filter_tags[]=internal', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $res = $response->toArray();
        $this->assertArrayNotHasKey('CrudOpenApiApiPlatformTag', $res['components']['schemas']);
        $this->assertArrayNotHasKey('/cruds/{id}', $res['paths']);
        $this->assertArrayHasKey('/cruds', $res['paths']);
        $this->assertArrayHasKey('post', $res['paths']['/cruds']);
        $this->assertArrayHasKey('get', $res['paths']['/cruds']);
        $this->assertEquals([['name' => 'Crud', 'description' => 'A resource used for OpenAPI tests.']], $res['tags']);

        $response = self::createClient()->request('GET', '/docs?filter_tags[]=anotherone', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $res = $response->toArray();
        $this->assertArrayHasKey('CrudOpenApiApiPlatformTag', $res['components']['schemas']);
        $this->assertArrayHasKey('Crud', $res['components']['schemas']);
        $this->assertArrayNotHasKey('/cruds/{id}', $res['paths']);
        $this->assertArrayHasKey('/cruds', $res['paths']);
        $this->assertArrayNotHasKey('post', $res['paths']['/cruds']);
        $this->assertArrayHasKey('get', $res['paths']['/cruds']);
        $this->assertArrayHasKey('/crud_open_api_api_platform_tags/{id}', $res['paths']);
        $this->assertEquals([['name' => 'Crud', 'description' => 'A resource used for OpenAPI tests.'], ['name' => 'CrudOpenApiApiPlatformTag', 'description' => 'Something nice']], $res['tags']);
    }

    public function testHasSchemasForMultipleFormats(): void
    {
        $response = self::createClient()->request('GET', '/docs?filter_tags[]=internal', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $res = $response->toArray();
        $this->assertArrayHasKey('Crud.jsonld', $res['components']['schemas']);
        $this->assertSame(['allOf' => [
            ['$ref' => '#/components/schemas/HydraItemBaseSchema'],
            [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string'],
                ],
            ],
        ], 'description' => 'A resource used for OpenAPI tests.'], $res['components']['schemas']['Crud.jsonld']);
    }

    public function testRetrieveTheOpenApiDocumentation(): void
    {
        $response = self::createClient()->request('GET', '/docs', ['headers' => ['accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.openapi+json; charset=utf-8');
        $json = $response->toArray();

        // Context
        $this->assertSame('3.1.0', $json['openapi']);
        // Root properties
        $this->assertSame('My Dummy API', $json['info']['title']);
        $this->assertStringContainsString('This is a test API.', $json['info']['description']);
        $this->assertStringContainsString('Made with love', $json['info']['description']);
        // Security Schemes
        $this->assertEquals([
            'oauth' => [
                'type' => 'oauth2',
                'description' => 'OAuth 2.0 implicit Grant',
                'flows' => [
                    'implicit' => [
                        'authorizationUrl' => 'http://my-custom-server/openid-connect/auth',
                        'scopes' => [],
                    ],
                ],
            ],
            'Some_Authorization_Name' => [
                'type' => 'apiKey',
                'description' => 'Value for the Authorization header parameter.',
                'name' => 'Authorization',
                'in' => 'header',
            ],
        ], $json['components']['securitySchemes']);

        // Supported classes
        $this->assertArrayHasKey('AbstractDummy', $json['components']['schemas']);
        $this->assertArrayHasKey('CircularReference', $json['components']['schemas']);
        $this->assertArrayHasKey('CircularReference-circular', $json['components']['schemas']);
        $this->assertArrayHasKey('CompositeItem', $json['components']['schemas']);
        $this->assertArrayHasKey('CompositeLabel', $json['components']['schemas']);
        $this->assertArrayHasKey('ConcreteDummy', $json['components']['schemas']);
        $this->assertArrayHasKey('CustomIdentifierDummy', $json['components']['schemas']);
        $this->assertArrayHasKey('CustomNormalizedDummy-input', $json['components']['schemas']);
        $this->assertArrayHasKey('CustomNormalizedDummy-output', $json['components']['schemas']);
        $this->assertArrayHasKey('CustomWritableIdentifierDummy', $json['components']['schemas']);
        $this->assertArrayHasKey('Dummy', $json['components']['schemas']);
        $this->assertArrayHasKey('DummyBoolean', $json['components']['schemas']);
        $this->assertArrayHasKey('RelatedDummy', $json['components']['schemas']);
        $this->assertArrayHasKey('DummyTableInheritance', $json['components']['schemas']);
        $this->assertArrayHasKey('DummyTableInheritanceChild', $json['components']['schemas']);
        $this->assertArrayHasKey('OverriddenOperationDummy-overridden_operation_dummy_get', $json['components']['schemas']);
        $this->assertArrayHasKey('OverriddenOperationDummy-overridden_operation_dummy_put', $json['components']['schemas']);
        $this->assertArrayHasKey('OverriddenOperationDummy-overridden_operation_dummy_read', $json['components']['schemas']);
        $this->assertArrayHasKey('OverriddenOperationDummy-overridden_operation_dummy_write', $json['components']['schemas']);
        $this->assertArrayHasKey('Person', $json['components']['schemas']);
        $this->assertArrayHasKey('NoCollectionDummy', $json['components']['schemas']);
        $this->assertArrayHasKey('RelatedToDummyFriend', $json['components']['schemas']);
        $this->assertArrayHasKey('RelatedToDummyFriend-fakemanytomany', $json['components']['schemas']);
        $this->assertArrayHasKey('DummyFriend', $json['components']['schemas']);
        $this->assertArrayHasKey('RelationEmbedder-barcelona', $json['components']['schemas']);
        $this->assertArrayHasKey('RelationEmbedder-chicago', $json['components']['schemas']);
        $this->assertArrayHasKey('User-user_user-read', $json['components']['schemas']);
        $this->assertArrayHasKey('User-user_user-write', $json['components']['schemas']);
        $this->assertArrayHasKey('UuidIdentifierDummy', $json['components']['schemas']);
        $this->assertArrayHasKey('ThirdLevel', $json['components']['schemas']);
        $this->assertArrayHasKey('DummyCar', $json['components']['schemas']);
        $this->assertArrayHasKey('DummyWebhook', $json['components']['schemas']);
        $this->assertArrayNotHasKey('ParentDummy', $json['components']['schemas']);
        $this->assertArrayNotHasKey('UnknownDummy', $json['components']['schemas']);

        $this->assertArrayHasKey('/relation_embedders/{id}/custom', $json['paths']);
        $this->assertArrayHasKey('/override/swagger', $json['paths']);
        $this->assertArrayHasKey('/api/custom-call/{id}', $json['paths']);
        $this->assertArrayHasKey('get', $json['paths']['/api/custom-call/{id}']);
        $this->assertArrayHasKey('put', $json['paths']['/api/custom-call/{id}']);

        $this->assertArrayHasKey('id', $json['components']['schemas']['Dummy']['properties']);
        $this->assertSame(['name'], $json['components']['schemas']['Dummy']['required']);
        $this->assertArrayHasKey('genderType', $json['components']['schemas']['Person']['properties']);
        $this->assertEquals([
            'default' => 'male',
            'type' => ['string', 'null'],
            'enum' => [
                'male',
                'female',
                null,
            ],
        ], $json['components']['schemas']['Person']['properties']['genderType']);
        $this->assertArrayHasKey('playMode', $json['components']['schemas']['VideoGame']['properties']);
        $this->assertEquals([
            'default' => 'SinglePlayer',
            'enum' => ['CoOp', 'MultiPlayer', 'SinglePlayer'],
            'type' => 'string',
        ], $json['components']['schemas']['VideoGame']['properties']['playMode']);

        // Filters
        $this->assertSame('dummyBoolean', $json['paths']['/dummies']['get']['parameters'][4]['name']);
        $this->assertSame('query', $json['paths']['/dummies']['get']['parameters'][4]['in']);
        $this->assertFalse($json['paths']['/dummies']['get']['parameters'][4]['required']);
        $this->assertSame('boolean', $json['paths']['/dummies']['get']['parameters'][4]['schema']['type']);

        $this->assertSame('foobar[]', $json['paths']['/dummy_cars']['get']['parameters'][9]['name']);
        $this->assertSame('Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: foobar[]={propertyName}&foobar[]={anotherPropertyName}&foobar[{nestedPropertyParent}][]={nestedProperty}', $json['paths']['/dummy_cars']['get']['parameters'][9]['description']);

        // Webhook
        $this->assertSame('Something else here for example', $json['webhooks']['a']['get']['description']);
        $this->assertSame('Hi! it\'s me, I\'m the problem, it\'s me', $json['webhooks']['b']['post']['description']);

        // Subcollection - check filter on subResource
        $this->assertSame('id', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][0]['name']);
        $this->assertSame('path', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][0]['in']);
        $this->assertTrue($json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][0]['required']);
        $this->assertSame('string', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][0]['schema']['type']);

        $this->assertSame('page', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][1]['name']);
        $this->assertSame('query', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][1]['in']);
        $this->assertFalse($json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][1]['required']);
        $this->assertSame('integer', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][1]['schema']['type']);

        $this->assertSame('itemsPerPage', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][2]['name']);
        $this->assertSame('query', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][2]['in']);
        $this->assertFalse($json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][2]['required']);
        $this->assertSame('integer', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][2]['schema']['type']);

        $this->assertSame('pagination', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][3]['name']);
        $this->assertSame('query', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][3]['in']);
        $this->assertFalse($json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][3]['required']);
        $this->assertSame('boolean', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][3]['schema']['type']);

        $this->assertSame('name', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][5]['name']);
        $this->assertSame('query', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][5]['in']);
        $this->assertFalse($json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][5]['required']);
        $this->assertSame('string', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][5]['schema']['type']);

        $this->assertSame('description', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][6]['name']);
        $this->assertSame('query', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][6]['in']);
        $this->assertFalse($json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters'][6]['required']);

        $this->assertCount(7, $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['parameters']);

        // Subcollection - check schema
        $this->assertSame('#/components/schemas/RelatedToDummyFriend.jsonld-fakemanytomany', $json['paths']['/related_dummies/{id}/related_to_dummy_friends']['get']['responses']['200']['content']['application/ld+json']['schema']['allOf'][1]['properties']['hydra:member']['items']['$ref']);

        // Deprecations
        $this->assertTrue($json['paths']['/deprecated_resources']['get']['deprecated']);
        $this->assertTrue($json['paths']['/deprecated_resources']['post']['deprecated']);
        $this->assertTrue($json['paths']['/deprecated_resources/{id}']['get']['deprecated']);
        $this->assertTrue($json['paths']['/deprecated_resources/{id}']['delete']['deprecated']);
        $this->assertTrue($json['paths']['/deprecated_resources/{id}']['put']['deprecated']);
        $this->assertTrue($json['paths']['/deprecated_resources/{id}']['patch']['deprecated']);

        // Formats
        $this->assertArrayHasKey('Dummy.jsonld', $json['components']['schemas']);
        $this->assertEquals([
            '204' => [
                'description' => 'User activated',
            ],
        ], $json['paths']['/override_open_api_responses']['post']['responses']);
    }

    public function testOpenApiUiIsEnabledForDocsEndpoint(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'text/html'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('My Dummy API', $response->getContent());
        $this->assertStringContainsString('openapi', $response->getContent());
    }

    public function testOpenApiExtensionPropertiesIsEnabledInJsonDocs(): void
    {
        $response = self::createClient()->request('GET', '/docs', ['headers' => ['accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray();

        $this->assertSame('hide', $json['paths']['/dummy_addresses']['get']['x-visibility']);
    }

    public function testOpenApiUiIsEnabledForAnArbitraryEndpoint(): void
    {
        $response = self::createClient()->request('GET', '/dummies', [
            'headers' => ['Accept' => 'text/html'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('openapi', $response->getContent());
    }

    public function testRetrieveTheOpenApiDocumentationWithApiGatewayCompatibility(): void
    {
        $kernel = self::bootKernel();
        if ('mongodb' === $kernel->getEnvironment()) {
            $this->markTestSkipped('Resource not loaded with MongoDB.');
        }

        $response = self::createClient()->request('GET', '/docs?api_gateway=true', ['headers' => ['accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.openapi+json; charset=utf-8');
        $json = $response->toArray();

        $this->assertSame('/', $json['basePath']);
        $this->assertSame('The dummy id.', $json['components']['schemas']['RamseyUuidDummy']['properties']['id']['description']);
        $this->assertArrayNotHasKey('RelatedDummy-barcelona', $json['components']['schemas']);
        $this->assertArrayHasKey('RelatedDummybarcelona', $json['components']['schemas']);
    }

    public function testRetrieveTheOpenApiDocumentationToSeeIfShortNamePropertyIsUsed(): void
    {
        $kernel = self::bootKernel();
        if ('mongodb' === $kernel->getEnvironment()) {
            $this->markTestSkipped('Resource not loaded with MongoDB.');
        }

        $response = self::createClient()->request('GET', '/docs', ['headers' => ['accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray();

        $this->assertArrayHasKey('Resource', $json['components']['schemas']);
        $this->assertArrayHasKey('ResourceRelated', $json['components']['schemas']);
        $this->assertEquals([
            'readOnly' => true,
            'anyOf' => [
                [
                    '$ref' => '#/components/schemas/ResourceRelated',
                ],
                [
                    'type' => 'null',
                ],
            ],
        ], $json['components']['schemas']['Resource']['properties']['resourceRelated']);
    }

    public function testRetrieveTheJsonOpenApiDocumentation(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.openapi+json; charset=utf-8');
        $json = $response->toArray();

        // Context
        $this->assertSame('3.1.0', $json['openapi']);
        // Root properties
        $this->assertSame('My Dummy API', $json['info']['title']);
        $this->assertStringContainsString('This is a test API.', $json['info']['description']);
        $this->assertStringContainsString('Made with love', $json['info']['description']);
        // Security Schemes
        $this->assertEquals([
            'oauth' => [
                'type' => 'oauth2',
                'description' => 'OAuth 2.0 implicit Grant',
                'flows' => [
                    'implicit' => [
                        'authorizationUrl' => 'http://my-custom-server/openid-connect/auth',
                        'scopes' => [],
                    ],
                ],
            ],
            'Some_Authorization_Name' => [
                'type' => 'apiKey',
                'description' => 'Value for the Authorization header parameter.',
                'name' => 'Authorization',
                'in' => 'header',
            ],
        ], $json['components']['securitySchemes']);
    }

    public function testRetrieveTheYamlOpenApiDocumentation(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+yaml'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.openapi+yaml; charset=utf-8');
    }

    public function testRetrieveTheOpenApiDocumentationHtml(): void
    {
        $response = self::createClient()->request('GET', '/', [
            'headers' => ['Accept' => 'text/html'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
    }

    public function testRetrieveTheOpenApiDocumentationForEntityDtoWrappers(): void
    {
        $kernel = self::bootKernel();
        if ('mongodb' === $kernel->getEnvironment()) {
            $this->markTestSkipped('Resource not loaded with MongoDB.');
        }

        $response = self::createClient()->request('GET', '/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray();

        $this->assertArrayHasKey('WrappedResponseEntity-read', $json['components']['schemas']);
        $this->assertArrayHasKey('id', $json['components']['schemas']['WrappedResponseEntity-read']['properties']);
        $this->assertEquals(['type' => 'string'], $json['components']['schemas']['WrappedResponseEntity-read']['properties']['id']);
        $this->assertArrayHasKey('WrappedResponseEntity.CustomOutputEntityWrapperDto-read', $json['components']['schemas']);
        $this->assertArrayHasKey('data', $json['components']['schemas']['WrappedResponseEntity.CustomOutputEntityWrapperDto-read']['properties']);
        $this->assertEquals(['$ref' => '#/components/schemas/WrappedResponseEntity-read'], $json['components']['schemas']['WrappedResponseEntity.CustomOutputEntityWrapperDto-read']['properties']['data']);
    }

    public function testRetrieveTheOpenApiDocumentationWith30Specification(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonopenapi?spec_version=3.0.0', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray();

        $this->assertSame('3.0.0', $json['openapi']);
        $this->assertEquals([
            ['type' => 'integer'],
            ['type' => 'null'],
        ], $json['components']['schemas']['DummyBoolean']['properties']['id']['anyOf']);
        $this->assertEquals([
            ['type' => 'boolean'],
            ['type' => 'null'],
        ], $json['components']['schemas']['DummyBoolean']['properties']['isDummyBoolean']['anyOf']);
        $this->assertArrayNotHasKey('owl:maxCardinality', $json['components']['schemas']['DummyBoolean']['properties']['isDummyBoolean']);
    }

    public function testRetrieveTheOpenApiDocumentationInJson(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonopenapi', [
            'headers' => ['Accept' => 'text/html,*/*;q=0.8'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.openapi+json; charset=utf-8');
    }

    public function testOpenApiUiIsEnabledForDocsEndpointWithDummyObject(): void
    {
        $this->recreateSchema([Dummy::class, RelatedDummy::class, RelatedOwnedDummy::class, RelatedOwningDummy::class]);
        self::createClient()->request('POST', '/dummies', ['json' => ['name' => 'test']]);
        $response = self::createClient()->request('GET', '/dummies/1.html', [
            'headers' => ['Accept' => 'text/html'],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testRetrieveTheEntrypoint(): void
    {
        $response = self::createClient()->request('GET', '/', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.openapi+json; charset=utf-8');
        $this->assertJson($response->getContent());
    }

    public function testRetrieveTheEntrypointWithUrlFormat(): void
    {
        $response = self::createClient()->request('GET', '/index.jsonopenapi', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.openapi+json; charset=utf-8');
        $this->assertJson($response->getContent());
    }
}
