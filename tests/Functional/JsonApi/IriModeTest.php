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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class IriModeTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [JsonApiDummy::class];
    }

    public function testGetSingleResourceDefaultIriMode(): void
    {
        // Default mode (use_iri_as_id: true) — id is the IRI, no links.self
        self::createClient()->request('GET', '/jsonapi_dummies/10', [
            'headers' => ['accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'data' => [
                'id' => '/jsonapi_dummies/10',
                'type' => 'JsonApiDummy',
            ],
        ]);

        $json = json_decode(self::getClient()->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('links', $json['data']);
    }
}
