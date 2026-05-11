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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\InterfaceDtoOutputResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InterfaceDtoOutputTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [InterfaceDtoOutputResource::class];
    }

    public function testCollectionExposesOnlyInterfaceProperties(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_interface_dto_outputs', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $member = $body['hydra:member'] ?? $body['member'];
        $this->assertArrayHasKey('@id', $member[0]);
        $this->assertArrayHasKey('@type', $member[0]);
        $this->assertArrayHasKey('name', $member[0]);
        $this->assertArrayNotHasKey('city', $member[0]);
    }
}
