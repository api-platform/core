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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\JsonLdContextDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\JsonLdContextRelation;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class EntrypointTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [JsonLdContextDummy::class, JsonLdContextRelation::class];
    }

    public function testEntrypointListsRegisteredResources(): void
    {
        $response = self::createClient()->request('GET', '/', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('application/ld+json; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $body = $response->toArray();
        $this->assertSame('/contexts/Entrypoint', $body['@context']);
        $this->assertSame('/', $body['@id']);
        $this->assertSame('Entrypoint', $body['@type']);
        $this->assertSame('/jsonld_context_relations', $body['jsonLdContextRelation']);
        $this->assertArrayHasKey('jsonLdContextDummy', $body);
    }
}
