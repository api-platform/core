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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\NonResourceContainer;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NonResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [NonResourceContainer::class];
    }

    public function testNestedResourceIsEmbeddedAndRawObjectIsInlined(): void
    {
        $response = self::createClient()->request('GET', '/hal_non_resource_containers/1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/hal+json; charset=utf-8');
        $body = $response->toArray();

        $this->assertSame('/hal_non_resource_containers/1', $body['_links']['self']['href']);
        $this->assertSame('/hal_non_resource_containers/1-nested', $body['_links']['nested']['href']);
        $this->assertSame('1', $body['id']);
        $this->assertSame(['foo' => 'f1', 'bar' => 'b1'], $body['notAResource']);

        $nested = $body['_embedded']['nested'];
        $this->assertSame('/hal_non_resource_containers/1-nested', $nested['_links']['self']['href']);
        $this->assertSame('1-nested', $nested['id']);
        $this->assertSame(['foo' => 'f2', 'bar' => 'b2'], $nested['notAResource']);
    }
}
