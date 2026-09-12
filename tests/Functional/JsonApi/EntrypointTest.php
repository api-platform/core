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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\EntrypointDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class EntrypointTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [EntrypointDummy::class];
    }

    public function testEntrypointHasSelfAndResourceLinks(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame('http://example.com/', $body['links']['self']);
        $this->assertSame('http://example.com/jsonapi_entrypoint_dummies', $body['links']['jsonApiEntrypointDummy']);
    }

    public function testEmptyCollectionRendersEmptyDataArray(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_entrypoint_dummies', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame([], $body['data']);
    }
}
