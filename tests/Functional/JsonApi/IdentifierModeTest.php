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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiNotExposedRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiRelatedDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class IdentifierModeTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            JsonApiDummy::class,
            JsonApiRelatedDummy::class,
            JsonApiNotExposedRelation::class,
        ];
    }

    public function testGetSingleResourceIdentifierMode(): void
    {
        $this->bootJsonApiKernel();
        self::createClient()->request('GET', '/jsonapi_dummies/10', [
            'headers' => ['accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'data' => [
                'id' => '10',
                'type' => 'JsonApiDummy',
                'links' => [
                    'self' => '/jsonapi_dummies/10',
                ],
                'attributes' => [
                    'name' => 'Dummy #10',
                ],
            ],
        ]);
    }

    public function testGetCollectionIdentifierMode(): void
    {
        $this->bootJsonApiKernel();
        self::createClient()->request('GET', '/jsonapi_dummies', [
            'headers' => ['accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'data' => [
                [
                    'id' => '1',
                    'type' => 'JsonApiDummy',
                    'links' => [
                        'self' => '/jsonapi_dummies/1',
                    ],
                ],
                [
                    'id' => '2',
                    'type' => 'JsonApiDummy',
                    'links' => [
                        'self' => '/jsonapi_dummies/2',
                    ],
                ],
            ],
        ]);
    }

    public function testRelationWithNotExposedOperationIdentifierMode(): void
    {
        $this->bootJsonApiKernel();
        self::createClient()->request('GET', '/jsonapi_dummies/10', [
            'headers' => ['accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'data' => [
                'id' => '10',
                'type' => 'JsonApiDummy',
                'relationships' => [
                    'notExposedRelation' => [
                        'data' => [
                            'id' => '5',
                            'type' => 'JsonApiNotExposedRelation',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testSubresourceNotExposedIdentifierMode(): void
    {
        $this->bootJsonApiKernel();
        self::createClient()->request('GET', '/jsonapi_dummies/10/not_exposed_relation', [
            'headers' => ['accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'data' => [
                'id' => '5',
                'type' => 'JsonApiNotExposedRelation',
                // links.self uses the subresource URI — the only publicly accessible route
                'links' => ['self' => '/jsonapi_dummies/10/not_exposed_relation'],
            ],
        ]);
    }

    private function bootJsonApiKernel(): void
    {
        $baseEnv = $_SERVER['APP_ENV'] ?? 'test';
        $jsonApiEnv = 'mongodb' === $baseEnv ? 'jsonapi_mongodb' : 'jsonapi';

        // AppKernel overrides environment with $_SERVER['APP_ENV'] (behat compat),
        // so we must temporarily set it to our target environment.
        $_SERVER['APP_ENV'] = $jsonApiEnv;

        try {
            self::bootKernel(['environment' => $jsonApiEnv]);
        } finally {
            $_SERVER['APP_ENV'] = $baseEnv;
        }
    }
}
