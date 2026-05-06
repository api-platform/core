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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\DisableIdGenAnonymous;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DisableIdGenerationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [DisableIdGenAnonymous::class];
    }

    public function testNestedAnonymousResourceHasNoIri(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_disable_id_gen_anonymous', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $items = $response->toArray()['items'];
        $this->assertArrayNotHasKey('@id', $items[0]);
        $this->assertArrayNotHasKey('@id', $items[1]);
    }
}
