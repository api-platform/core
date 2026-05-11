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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\MaxDepthResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MaxDepthTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [MaxDepthResource::class];
    }

    public function testFirstLevelChildIsExposed(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_max_depth_resources', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'level 1',
                'child' => ['name' => 'level 2'],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertArrayHasKey('child', $body);
        $this->assertSame('level 2', $body['child']['name']);
    }

    public function testSecondLevelChildIsTruncatedByMaxDepth(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_max_depth_resources', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
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
        $this->assertSame('level 2', $body['child']['name']);
        $this->assertArrayNotHasKey('child', $body['child']);
    }
}
