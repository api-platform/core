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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\ClientGeneratedId;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ClientGeneratedIdTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ClientGeneratedId::class];
    }

    public function testPostWithClientIdSucceedsWhenOptedIn(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_client_generated_ids_opt_in', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiClientGeneratedId',
                    'id' => 'client-uuid-42',
                    'attributes' => ['name' => 'created with client id'],
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        // Default identifier mode emits the IRI as `data.id`. The IRI must reflect the client-supplied id.
        $this->assertSame('/jsonapi_client_generated_ids/client-uuid-42', $body['data']['id']);
        $this->assertSame('created with client id', $body['data']['attributes']['name']);
    }

    public function testPostWithClientIdRejectedByDefault(): void
    {
        self::createClient()->request('POST', '/jsonapi_client_generated_ids', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiClientGeneratedId',
                    'id' => 'client-uuid-43',
                    'attributes' => ['name' => 'should fail'],
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPostWithoutClientIdStillSucceeds(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_client_generated_ids', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiClientGeneratedId',
                    'attributes' => ['name' => 'server-side id'],
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('server-side id', $body['data']['attributes']['name']);
    }
}
