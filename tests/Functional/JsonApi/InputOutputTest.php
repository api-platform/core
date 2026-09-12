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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\CustomOutputResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InputOutputTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [CustomOutputResource::class];
    }

    public function testItemUsesCustomOutputDto(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_custom_outputs/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'data' => [
                'type' => 'CustomOutputDto',
                'attributes' => [
                    'foo' => 'test',
                    'bar' => 1,
                ],
            ],
        ]);
    }

    public function testCollectionUsesCustomOutputDto(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_custom_outputs', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'data' => [
                ['type' => 'CustomOutputDto', 'attributes' => ['foo' => 'test', 'bar' => 1]],
                ['type' => 'CustomOutputDto', 'attributes' => ['foo' => 'test', 'bar' => 2]],
            ],
        ]);
    }
}
