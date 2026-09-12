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

namespace ApiPlatform\Tests\Functional\Hal;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\MaxDepthResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MaxDepthTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [MaxDepthResource::class];
    }

    public function testFirstLevelChildIsEmbedded(): void
    {
        $response = self::createClient()->request('POST', '/hal_max_depth_resources', [
            'headers' => [
                'Accept' => 'application/hal+json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'name' => 'level 1',
                'child' => ['name' => 'level 2'],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertArrayHasKey('_embedded', $body);
        $this->assertArrayHasKey('child', $body['_embedded']);
        $this->assertSame('level 2', $body['_embedded']['child']['name']);
        $this->assertArrayNotHasKey('_embedded', $body['_embedded']['child']);
    }

    public function testSecondLevelChildIsTruncatedByMaxDepth(): void
    {
        $response = self::createClient()->request('POST', '/hal_max_depth_resources', [
            'headers' => [
                'Accept' => 'application/hal+json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'name' => 'level 1',
                'child' => [
                    'name' => 'level 2',
                    'child' => ['name' => 'level 3'],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertArrayHasKey('_embedded', $body);
        $this->assertArrayHasKey('child', $body['_embedded']);
        $this->assertSame('level 2', $body['_embedded']['child']['name']);
        $this->assertArrayNotHasKey('_embedded', $body['_embedded']['child']);
    }

    public function testPutTruncatesSecondLevelChildByMaxDepth(): void
    {
        $response = self::createClient()->request('PUT', '/hal_max_depth_resources/1', [
            'headers' => [
                'Accept' => 'application/hal+json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'child' => [
                    'child' => ['name' => 'level 3'],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/hal+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertArrayHasKey('_embedded', $body);
        $this->assertArrayHasKey('child', $body['_embedded']);
        $this->assertArrayNotHasKey('_embedded', $body['_embedded']['child']);
    }
}
