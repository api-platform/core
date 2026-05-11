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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\CustomOutputResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InputOutputDtoTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [CustomOutputResource::class];
    }

    public function testItemReturnsCustomOutput(): void
    {
        $response = self::createClient()->request('GET', '/hal_custom_outputs/1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/hal+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame('test', $body['foo']);
        $this->assertSame(1, $body['bar']);
    }

    public function testCollectionEmbedsCustomOutputItems(): void
    {
        $response = self::createClient()->request('GET', '/hal_custom_outputs', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertCount(2, $body['_embedded']['item']);
        $this->assertSame('test', $body['_embedded']['item'][0]['foo']);
        $this->assertSame(1, $body['_embedded']['item'][0]['bar']);
        $this->assertSame('test', $body['_embedded']['item'][1]['foo']);
        $this->assertSame(2, $body['_embedded']['item'][1]['bar']);
    }
}
