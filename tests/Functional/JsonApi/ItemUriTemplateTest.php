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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\UriTemplateCar;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ItemUriTemplateTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [UriTemplateCar::class];
    }

    public function testGetCollectionDerivesItemIriFromFirstGetOperation(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_uri_template_cars', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame('/jsonapi_uri_template_cars', $body['links']['self']);
        $this->assertCount(2, $body['data']);
        foreach ($body['data'] as $member) {
            $this->assertMatchesRegularExpression('#^/jsonapi_uri_template_cars/.+$#', $member['id']);
            $this->assertSame('JsonApiUriTemplateCar', $member['type']);
        }
    }

    public function testGetCollectionWithItemUriTemplateUsesIt(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_uri_template_brands/renault/cars', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/jsonapi_uri_template_brands/renault/cars', $body['links']['self']);
        foreach ($body['data'] as $member) {
            $this->assertMatchesRegularExpression('#^/jsonapi_uri_template_brands/renault/cars/.+$#', $member['id']);
        }
    }

    public function testPostWithoutItemUriTemplateUsesFirstGetOperation(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_uri_template_cars', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiUriTemplateCar',
                    'attributes' => ['owner' => 'Vincent'],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertMatchesRegularExpression('#^/jsonapi_uri_template_cars/.+$#', $body['data']['id']);
        $this->assertSame('JsonApiUriTemplateCar', $body['data']['type']);
    }

    public function testPostWithItemUriTemplateUsesIt(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_uri_template_brands/renault/cars', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiUriTemplateCar',
                    'attributes' => ['owner' => 'Vincent'],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertMatchesRegularExpression('#^/jsonapi_uri_template_brands/renault/cars/.+$#', $body['data']['id']);
    }
}
